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
                'errorCode'    => '9999',
                'errorMessage' => 'Invalid app secret',
            ])));
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame('9999', $e->getErrorCode());
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

    public function test_client_error_400_raises_api_exception(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid request body');

        $this->normalizer->normalize(new Response(400, [], json_encode([
            'errorCode'    => '2025',
            'errorMessage' => 'Invalid request body',
        ])));
    }

    public function test_client_error_401_raises_api_exception(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid app Token');

        $this->normalizer->normalize(new Response(401, [], json_encode([
            'errorCode'    => '2079',
            'errorMessage' => 'Invalid app Token',
        ])));
    }

    public function test_client_error_404_raises_api_exception(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid Payment ID');

        $this->normalizer->normalize(new Response(404, [], json_encode([
            'errorCode'    => '2002',
            'errorMessage' => 'Invalid Payment ID',
        ])));
    }

    public function test_client_error_429_raises_api_exception(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $this->normalizer->normalize(new Response(429, [], json_encode([
            'errorCode'    => '2104',
            'errorMessage' => 'Rate limit exceeded',
        ])));
    }

    public function test_client_error_without_error_code_uses_http_code(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionCode(400);

        $this->normalizer->normalize(new Response(400, [], json_encode([
            'errorMessage' => 'Some error',
        ])));
    }
}