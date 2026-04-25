<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Tests;

use Cryptohopper\Sdk\Client;
use Cryptohopper\Sdk\Exceptions\CryptohopperException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ErrorsTest extends TestCase
{
    /**
     * @return list<array{0: int, 1: string}>
     */
    public static function statusToCode(): array
    {
        return [
            [400, 'VALIDATION_ERROR'],
            [401, 'UNAUTHORIZED'],
            [402, 'DEVICE_UNAUTHORIZED'],
            [403, 'FORBIDDEN'],
            [404, 'NOT_FOUND'],
            [409, 'CONFLICT'],
            [422, 'VALIDATION_ERROR'],
            [429, 'RATE_LIMITED'],
            [500, 'SERVER_ERROR'],
            [503, 'SERVICE_UNAVAILABLE'],
            [418, 'UNKNOWN'],
        ];
    }

    /**
     * @dataProvider statusToCode
     */
    public function testMapsHttpStatusToErrorCode(int $status, string $expected): void
    {
        $backend = new MockBackend(
            responses: [new Response($status, [], '{"code":0,"message":"bad"}')],
            maxRetries: 0,
        );

        try {
            $backend->client->user->get();
            self::fail('Expected CryptohopperException');
        } catch (CryptohopperException $e) {
            self::assertSame($expected, $e->getErrorCode());
            self::assertSame($status, $e->getStatus());
            self::assertSame('bad', $e->getMessage());
        }
    }

    public function testExposesServerCodeAndIpAddress(): void
    {
        $body    = '{"code":4210,"message":"ip not allowed","ip_address":"203.0.113.5"}';
        $backend = new MockBackend(
            responses: [new Response(403, [], $body)],
            maxRetries: 0,
        );

        try {
            $backend->client->hoppers->list();
            self::fail('Expected CryptohopperException');
        } catch (CryptohopperException $e) {
            self::assertSame('FORBIDDEN', $e->getErrorCode());
            self::assertSame(4210, $e->getServerCode());
            self::assertSame('203.0.113.5', $e->getIpAddress());
        }
    }

    public function testParsesRetryAfterNumericSeconds(): void
    {
        $backend = new MockBackend(
            responses: [new Response(429, ['Retry-After' => '2'], '{"message":"slow down"}')],
            maxRetries: 0,
        );

        try {
            $backend->client->user->get();
            self::fail('Expected CryptohopperException');
        } catch (CryptohopperException $e) {
            self::assertSame('RATE_LIMITED', $e->getErrorCode());
            self::assertSame(2000, $e->getRetryAfterMs());
        }
    }

    public function testGracefullyHandlesUnparseableBody(): void
    {
        $backend = new MockBackend(
            responses: [new Response(500, [], 'not-json')],
            maxRetries: 0,
        );

        try {
            $backend->client->user->get();
            self::fail('Expected CryptohopperException');
        } catch (CryptohopperException $e) {
            self::assertSame('SERVER_ERROR', $e->getErrorCode());
            self::assertSame(500, $e->getStatus());
        }
    }

    /**
     * Guzzle wraps cURL's CURLE_OPERATION_TIMEDOUT (errno 28) in a
     * ConnectException, not a separate timeout exception type. The transport
     * must inspect the handler context to discriminate TIMEOUT from
     * NETWORK_ERROR — without this discrimination, every timeout was being
     * reported as a generic NETWORK_ERROR.
     */
    public function testConnectExceptionWithCurlTimeoutErrnoMapsToTimeout(): void
    {
        $handler = new MockHandler([
            new ConnectException(
                'cURL error 28: Operation timed out after 30000 milliseconds',
                new Request('GET', 'https://api.cryptohopper.com/v1/user/get'),
                null,
                ['errno' => 28, 'error' => 'Operation timed out'],
            ),
        ]);
        $client = new Client(
            apiKey: 'test-token',
            maxRetries: 0,
            httpClient: new GuzzleClient(['handler' => $handler, 'http_errors' => false]),
        );

        try {
            $client->user->get();
            self::fail('Expected CryptohopperException');
        } catch (CryptohopperException $e) {
            self::assertSame('TIMEOUT', $e->getErrorCode());
            self::assertSame(0, $e->getStatus());
        }
    }

    /**
     * A non-timeout ConnectException — connection refused, DNS failure, etc.
     * — must continue to surface as NETWORK_ERROR so callers can distinguish
     * recoverable timeouts from genuine connectivity problems.
     */
    public function testNonTimeoutConnectExceptionMapsToNetworkError(): void
    {
        $handler = new MockHandler([
            new ConnectException(
                'cURL error 7: Failed to connect to host',
                new Request('GET', 'https://api.cryptohopper.com/v1/user/get'),
                null,
                ['errno' => 7, 'error' => 'Couldn\'t connect to server'],
            ),
        ]);
        $client = new Client(
            apiKey: 'test-token',
            maxRetries: 0,
            httpClient: new GuzzleClient(['handler' => $handler, 'http_errors' => false]),
        );

        try {
            $client->user->get();
            self::fail('Expected CryptohopperException');
        } catch (CryptohopperException $e) {
            self::assertSame('NETWORK_ERROR', $e->getErrorCode());
            self::assertSame(0, $e->getStatus());
        }
    }
}
