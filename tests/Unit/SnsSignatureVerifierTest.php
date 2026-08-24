<?php

namespace Tiash\LaravelBkash\Tests\Unit;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;
use Tiash\LaravelBkash\Exceptions\SignatureException;
use Tiash\LaravelBkash\Security\SnsCertificateStore;
use Tiash\LaravelBkash\Security\SnsSignatureVerifier;

class SnsSignatureVerifierTest extends TestCase
{
    private $privateKey;
    private $publicKeyPem;
    private $cache;
    private $verifier;

    protected function setUp(): void
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($key, $this->privateKey);
        $details = openssl_pkey_get_details($key);
        $this->publicKeyPem = $details['key'];

        $this->cache = new Repository(new ArrayStore());

        $certStore = $this->createMock(SnsCertificateStore::class);
        // Real SNS serves an X.509 cert; any PEM payload openssl_pkey_get_public parses works here.
        $certStore->method('get')->willReturn($this->publicKeyPem);

        $this->verifier = new SnsSignatureVerifier($certStore, $this->cache);
    }

    private function sign(array $payload): string
    {
        openssl_sign($this->stringToSign($payload), $signature, $this->privateKey, OPENSSL_ALGO_SHA1);
        return base64_encode($signature);
    }

    private function stringToSign(array $payload): string
    {
        return $payload['Type'] . "\n" . $payload['MessageId'] . "\n" . $payload['TopicArn'] . "\n"
            . $payload['Message'] . "\n" . $payload['Timestamp'] . "\n";
    }

    private function notificationPayload(): array
    {
        return [
            'Type'      => 'Notification',
            'MessageId' => 'msg-' . uniqid(),
            'TopicArn'  => 'arn:aws:sns:ap-southeast-1:123:bpt',
            'Message'   => json_encode([
                'trxID'             => 'TRX1',
                'transactionStatus' => 'Completed',
                'amount'            => '100.00',
            ]),
            'Timestamp' => '2026-08-24T12:00:00.000Z',
        ];
    }

    public function test_valid_signed_notification_passes(): void
    {
        $payload             = $this->notificationPayload();
        $payload['SigningCertURL'] = 'https://sns.ap-southeast-1.amazonaws.com/cert.pem';
        $payload['Signature']      = $this->sign($payload);

        $result = $this->verifier->verify(json_encode($payload));

        $this->assertSame('notification', $result['type']);
        $this->assertSame('TRX1', $result['data']['trxID']);
    }

    public function test_tampered_message_fails_verification(): void
    {
        $payload                   = $this->notificationPayload();
        $payload['SigningCertURL'] = 'https://sns.ap-southeast-1.amazonaws.com/cert.pem';
        $payload['Signature']      = $this->sign($payload);

        // Attacker flips the amount after signing
        $decoded           = json_decode($payload['Message'], true);
        $decoded['amount'] = '999999.00';
        $payload['Message'] = json_encode($decoded);

        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('verification failed');

        $this->verifier->verify(json_encode($payload));
    }

    public function test_untrusted_cert_url_is_rejected(): void
    {
        $payload                   = $this->notificationPayload();
        $payload['SigningCertURL'] = 'https://evil.example.com/cert.pem';
        $payload['Signature']      = 'whatever';

        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('Untrusted');

        $this->verifier->verify(json_encode($payload));
    }

    public function test_duplicate_message_id_is_rejected(): void
    {
        $payload                   = $this->notificationPayload();
        $payload['SigningCertURL'] = 'https://sns.ap-southeast-1.amazonaws.com/cert.pem';
        $payload['Signature']      = $this->sign($payload);

        $body = json_encode($payload);
        $this->verifier->verify($body); // first delivery OK

        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('Duplicate');
        $this->verifier->verify($body); // SNS redelivery
    }

    public function test_missing_fields_are_rejected(): void
    {
        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('Missing Signature');

        $this->verifier->verify(json_encode(['Type' => 'Notification']));
    }
}