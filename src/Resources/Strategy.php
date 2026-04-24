<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->strategy` — user-defined trading strategies.
 */
final class Strategy
{
    public function __construct(private readonly Transport $transport)
    {
    }

    public function list(): mixed
    {
        return $this->transport->request('GET', '/strategy/strategies');
    }

    public function get(int|string $strategyId): mixed
    {
        return $this->transport->request('GET', '/strategy/get', query: ['strategy_id' => $strategyId]);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): mixed
    {
        return $this->transport->request('POST', '/strategy/create', body: $data);
    }

    /** @param array<string, mixed> $data */
    public function update(int|string $strategyId, array $data): mixed
    {
        return $this->transport->request('POST', '/strategy/edit', body: ['strategy_id' => $strategyId] + $data);
    }

    public function delete(int|string $strategyId): mixed
    {
        return $this->transport->request('POST', '/strategy/delete', body: ['strategy_id' => $strategyId]);
    }
}
