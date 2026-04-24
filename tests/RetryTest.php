<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Tests;

use Cryptohopper\Sdk\Exceptions\CryptohopperException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class RetryTest extends TestCase
{
    public function testRetriesOn429AndSucceeds(): void
    {
        $backend = new MockBackend([
            new Response(429, ['Retry-After' => '0'], '{"message":"slow"}'),
            new Response(429, ['Retry-After' => '0'], '{"message":"slow"}'),
            new Response(200, [], '{"data":{"ok":true}}'),
        ], maxRetries: 3);

        $result = $backend->client->user->get();

        self::assertSame(['ok' => true], $result);
        self::assertCount(3, $backend->history);
    }

    public function testStopsRetryingOnceMaxRetriesExceeded(): void
    {
        $backend = new MockBackend(
            responses: [
                new Response(429, ['Retry-After' => '0'], '{"message":"slow"}'),
                new Response(429, ['Retry-After' => '0'], '{"message":"slow"}'),
            ],
            maxRetries: 1,
        );

        try {
            $backend->client->user->get();
            self::fail('Expected CryptohopperException');
        } catch (CryptohopperException $e) {
            self::assertSame('RATE_LIMITED', $e->getErrorCode());
            self::assertCount(2, $backend->history);
        }
    }

    public function testZeroRetriesDisablesBackoff(): void
    {
        $backend = new MockBackend(
            responses: [new Response(429, ['Retry-After' => '0'], '{"message":"slow"}')],
            maxRetries: 0,
        );

        try {
            $backend->client->user->get();
            self::fail('Expected CryptohopperException');
        } catch (CryptohopperException $e) {
            self::assertSame('RATE_LIMITED', $e->getErrorCode());
            self::assertCount(1, $backend->history);
        }
    }
}
