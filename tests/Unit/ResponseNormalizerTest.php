<?php

namespace Tiash\LaravelBkash\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Tiash\LaravelBkash\Exceptions\ApiException;
use Tiash\LaravelBkash\Exceptions\NetworkException;
use Tiash\LaravelBkash\Http\ResponseNormalizer;

class ResponseNormalizerTest extends TestCase
{
    private $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ResponseNormalizer();
    }

    /** Regression: empty body must throw a clear exception, never crash array_key_exists(null). */
    public function test_empty_body_throws_network_exception(): void
    {
        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('empty response');

        $this->normalizer->normalize(new Response(200, [], ''));
    }

    /** Regression: garbage non-JSON body must not silently return null. */
    public function test_non_json_body_throws(): void
    {
        $this->expectException(NetworkException::class);

        $this->normalizer->normalize(new Response(200, [], '<html>Gateway timeout</html>'));
    }

    public function test_error_payload_raises_api_exception_with_code(): void
    {
        try {
            $this->normalizer->normalize(new Response(200, [], json_encode([
                'errorCode'    => '2051',
                'errorMessage' => 'Invalid app secret',
            ])));
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame('2051', $e->getErrorCode());
            $this->assertSame('Invalid app secret', $e->getMessage());
        }
    }

    public function test_success_status_code_passes_through(): void
    {
        $data = $this->normalizer->normalize(new Response(200, [], json_encode([
            'statusCode' => '0000',
            'paymentID'  => 'PAY123',
        ])));

        $this->assertSame('PAY123', $data['paymentID']);
    }

    public function test_grant_token_response_without_status_code_passes_through(): void
    {
        $data = $this->normalizer->normalize(new Response(200, [], json_encode([
            'id_token'      => 'jwt...',
            'refresh_token' => 'r',
            'expires_in'    => 3600,
        ])));

        $this->assertArrayHasKey('id_token', $data);
    }

    public function test_server_error_raises_network_exception(): void
    {
        $this->expectException(NetworkException::class);

        $this->normalizer->normalize(new Response(503, [], 'unavailable'));
    }
}