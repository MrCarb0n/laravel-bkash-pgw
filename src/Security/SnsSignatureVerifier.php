<?php

namespace Tiash\LaravelBkash\Security;

use Tiash\LaravelBkash\Exceptions\SignatureException;

/**
 * Verifies AWS SNS message signatures (SignatureVersion 1) used by bKash webhooks.
 *
 * @see https://docs.aws.amazon.com/sns/latest/dg/sns-verify-signature-of-message.html
 */
class SnsSignatureVerifier
{
    private const ALLOWED_CERT_HOST_SUFFIX = '.amazonaws.com';

    private $certStore;
    private $cache;
    private $dedupTtl;

    public function __construct(SnsCertificateStore $certStore, $cache, int $dedupTtl = 86400)
    {
        $this->certStore = $certStore;
        $this->cache     = $cache;
        $this->dedupTtl  = $dedupTtl;
    }

    /**
     * Verify an inbound SNS request body.
     * Returns ['type' => 'subscription_confirmation'] or ['type' => 'notification', 'data' => [...]].
     *
     * @throws SignatureException
     */
    public function verify(string $body): array
    {
        $payload = json_decode($body, true);

        if (!is_array($payload)) {
            throw new SignatureException('Invalid JSON payload');
        }

        $this->verifySignature($payload);
        $this->assertNotDuplicate($payload['MessageId'] ?? '');

        if (($payload['Type'] ?? '') === 'SubscriptionConfirmation') {
            return ['type' => 'subscription_confirmation', 'subscribe_url' => $payload['SubscribeURL'] ?? ''];
        }

        if (($payload['Type'] ?? '') === 'Notification') {
            $message = json_decode($payload['Message'] ?? '', true);

            if (!is_array($message)) {
                throw new SignatureException('Invalid Notification Message');
            }

            return ['type' => 'notification', 'data' => $message];
        }

        throw new SignatureException('Unknown SNS message type');
    }

    private function verifySignature(array $payload): void
    {
        foreach (['Signature', 'SigningCertURL', 'MessageId', 'Type', 'Timestamp'] as $field) {
            if (empty($payload[$field])) {
                throw new SignatureException("Missing {$field} in SNS message");
            }
        }

        $certUrl = $payload['SigningCertURL'];

        if (!str_starts_with($certUrl, 'https://') || !str_ends_with(parse_url($certUrl, PHP_URL_HOST), self::ALLOWED_CERT_HOST_SUFFIX)) {
            throw new SignatureException('Untrusted SigningCertURL host');
        }

        $publicKey = openssl_pkey_get_public($this->certStore->get($certUrl));

        if ($publicKey === false) {
            throw new SignatureException('Invalid SNS signing certificate');
        }

        $verified = openssl_verify(
            $this->buildStringToSign($payload),
            base64_decode($payload['Signature'], true) ?: '',
            $publicKey,
            OPENSSL_ALGO_SHA1
        );

        if ($verified !== 1) {
            throw new SignatureException('SNS signature verification failed');
        }
    }

    private function buildStringToSign(array $payload): string
    {
        $fields = ['Type', 'MessageId', 'TopicArn'];

        if (isset($payload['Subject'])) {
            $fields[] = 'Subject';
        }

        $fields[] = 'Message';
        $fields[] = 'Timestamp';

        $signable = '';
        foreach ($fields as $field) {
            $signable .= ($payload[$field] ?? '') . "\n";
        }

        return $signable;
    }

    /** SNS delivers at-least-once; drop redelivered MessageIds so listeners fire once. */
    private function assertNotDuplicate(string $messageId): void
    {
        $key = "bkash_sns_dedup_{$messageId}";

        if ($this->cache->has($key)) {
            throw new SignatureException('Duplicate MessageId');
        }

        $this->cache->put($key, true, $this->dedupTtl);
    }
}