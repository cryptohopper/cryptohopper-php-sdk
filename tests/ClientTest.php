<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Tests;

use Cryptohopper\Sdk\Client;
use Cryptohopper\Sdk\Version;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function testRejectsEmptyApiKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Client(apiKey: '');
    }

    public function testSendsAccessTokenAndUserAgent(): void
    {
        $backend = new MockBackend([new Response(200, [], '{"data":{"id":1}}')]);

        $backend->client->user->get();

        $req = $backend->last();
        self::assertSame('test-token', $req->getHeaderLine('access-token'));
        self::assertSame('', $req->getHeaderLine('Authorization'), 'Authorization header must NOT be set on v1 API calls');
        self::assertStringStartsWith('cryptohopper-sdk-php/' . Version::VERSION, $req->getHeaderLine('User-Agent'));
        self::assertSame('application/json', $req->getHeaderLine('Accept'));
        self::assertSame('', $req->getHeaderLine('x-api-app-key'));
    }

    public function testIncludesAppKeyHeaderWhenConfigured(): void
    {
        $backend = new MockBackend(
            responses: [new Response(200, [], '{"data":{}}')],
            appKey: 'my-client-id',
        );

        $backend->client->user->get();

        self::assertSame('my-client-id', $backend->last()->getHeaderLine('x-api-app-key'));
    }

    public function testUnwrapsDataEnvelope(): void
    {
        $backend = new MockBackend([new Response(200, [], '{"data":{"id":42,"name":"bot"}}')]);

        $result = $backend->client->user->get();

        self::assertSame(['id' => 42, 'name' => 'bot'], $result);
    }

    public function testReturnsParsedJsonWhenNoEnvelope(): void
    {
        $backend = new MockBackend([new Response(200, [], '[1,2,3]')]);

        $result = $backend->client->exchange->exchanges();

        self::assertSame([1, 2, 3], $result);
    }

    public function testBuildsQueryStringFromParams(): void
    {
        $backend = new MockBackend([new Response(200, [], '{"data":[]}')]);

        $backend->client->exchange->ticker(exchange: 'binance', market: 'BTC/USDT');

        $uri = $backend->last()->getUri();
        self::assertStringEndsWith('/exchange/ticker', $uri->getPath());
        self::assertStringContainsString('exchange=binance', $uri->getQuery());
        self::assertStringContainsString('market=BTC', $uri->getQuery());
    }

    public function testDropsNullQueryParams(): void
    {
        $backend = new MockBackend([new Response(200, [], '{"data":[]}')]);

        $backend->client->exchange->candles(
            exchange: 'binance',
            market: 'BTC/USDT',
            timeframe: '1h',
            from: null,
            to: null,
        );

        $query = $backend->last()->getUri()->getQuery();
        self::assertStringNotContainsString('from=', $query);
        self::assertStringNotContainsString('to=', $query);
    }

    public function testPostBodyIsJsonEncoded(): void
    {
        $backend = new MockBackend([new Response(200, [], '{"data":{"ok":true}}')]);

        $backend->client->hoppers->create(['name' => 'test-bot', 'exchange' => 'binance']);

        $req = $backend->last();
        self::assertSame('POST', $req->getMethod());
        self::assertSame('application/json', $req->getHeaderLine('Content-Type'));
        $body = json_decode((string) $req->getBody(), true);
        self::assertSame(['name' => 'test-bot', 'exchange' => 'binance'], $body);
    }
}
