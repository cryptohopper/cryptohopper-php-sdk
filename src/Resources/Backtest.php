<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->backtest` — run and inspect backtests.
 */
final class Backtest
{
    public function __construct(private readonly Transport $transport)
    {
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): mixed
    {
        return $this->transport->request('POST', '/backtest/new', body: $data);
    }

    public function get(int|string $backtestId): mixed
    {
        return $this->transport->request('GET', '/backtest/get', query: ['backtest_id' => $backtestId]);
    }

    /** @param array<string, mixed> $params */
    public function list(array $params = []): mixed
    {
        return $this->transport->request('GET', '/backtest/list', query: $params !== [] ? $params : null);
    }

    public function cancel(int|string $backtestId): mixed
    {
        return $this->transport->request('POST', '/backtest/cancel', body: ['backtest_id' => $backtestId]);
    }

    public function restart(int|string $backtestId): mixed
    {
        return $this->transport->request('POST', '/backtest/restart', body: ['backtest_id' => $backtestId]);
    }

    public function limits(): mixed
    {
        return $this->transport->request('GET', '/backtest/limits');
    }
}
