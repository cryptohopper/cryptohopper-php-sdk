<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Tests;

use Cryptohopper\Sdk\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Test utilities — builds a Client bound to a Guzzle MockHandler and exposes
 * the recorded requests so assertions can inspect path, query, and body.
 */
final class MockBackend
{
    public MockHandler $handler;
    public GuzzleClient $guzzle;
    /**
     * Entries are appended by GuzzleHttp\Middleware::history, which types its
     * container parameter as `array|ArrayAccess`. We keep a narrower shape
     * for use by assertions — phpstan.neon silences the reference-width
     * mismatch that PHPStan 2.0 otherwise reports on this file.
     *
     * @var list<array{request: RequestInterface, options: array<string, mixed>}>
     */
    public array $history = [];
    public Client $client;

    /**
     * @param list<Response> $responses
     */
    public function __construct(
        array $responses,
        int $maxRetries = 3,
        ?string $appKey = null,
    ) {
        $this->handler = new MockHandler($responses);
        $stack         = HandlerStack::create($this->handler);
        $stack->push(Middleware::history($this->history));
        $this->guzzle = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);

        $this->client = new Client(
            apiKey: 'test-token',
            appKey: $appKey,
            maxRetries: $maxRetries,
            httpClient: $this->guzzle,
        );
    }

    public function last(): RequestInterface
    {
        $entry = end($this->history);
        if ($entry === false) {
            throw new \RuntimeException('No requests recorded');
        }

        return $entry['request'];
    }
}
