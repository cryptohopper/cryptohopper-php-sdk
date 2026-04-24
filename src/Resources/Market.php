<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->market` — marketplace browse (public).
 */
final class Market
{
    public function __construct(private readonly Transport $transport)
    {
    }

    /** @param array<string, mixed> $params */
    public function signals(array $params = []): mixed
    {
        return $this->transport->request('GET', '/market/signals', query: $params !== [] ? $params : null);
    }

    public function signal(int|string $signalId): mixed
    {
        return $this->transport->request('GET', '/market/signal', query: ['signal_id' => $signalId]);
    }

    /** @param array<string, mixed> $params */
    public function items(array $params = []): mixed
    {
        return $this->transport->request('GET', '/market/marketitems', query: $params !== [] ? $params : null);
    }

    public function item(int|string $itemId): mixed
    {
        return $this->transport->request('GET', '/market/marketitem', query: ['item_id' => $itemId]);
    }

    public function homepage(): mixed
    {
        return $this->transport->request('GET', '/market/homepage');
    }
}
