<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->marketmaker` — market-maker bot ops + market-trend overrides + backlog.
 */
final class MarketMaker
{
    public function __construct(private readonly Transport $transport)
    {
    }

    /** @param array<string, mixed> $params */
    public function get(array $params = []): mixed
    {
        return $this->transport->request('GET', '/marketmaker/get', query: $params !== [] ? $params : null);
    }

    /** @param array<string, mixed> $data */
    public function cancel(array $data = []): mixed
    {
        return $this->transport->request('POST', '/marketmaker/cancel', body: $data);
    }

    /** @param array<string, mixed> $params */
    public function history(array $params = []): mixed
    {
        return $this->transport->request('GET', '/marketmaker/history', query: $params !== [] ? $params : null);
    }

    // Market-trend overrides

    /** @param array<string, mixed> $params */
    public function getMarketTrend(array $params = []): mixed
    {
        return $this->transport->request('GET', '/marketmaker/get-market-trend', query: $params !== [] ? $params : null);
    }

    /** @param array<string, mixed> $data */
    public function setMarketTrend(array $data): mixed
    {
        return $this->transport->request('POST', '/marketmaker/set-market-trend', body: $data);
    }

    /** @param array<string, mixed> $data */
    public function deleteMarketTrend(array $data = []): mixed
    {
        return $this->transport->request('POST', '/marketmaker/delete-market-trend', body: $data);
    }

    // Backlog

    /** @param array<string, mixed> $params */
    public function backlogs(array $params = []): mixed
    {
        return $this->transport->request('GET', '/marketmaker/get-backlogs', query: $params !== [] ? $params : null);
    }

    public function backlog(int|string $backlogId): mixed
    {
        return $this->transport->request('GET', '/marketmaker/get-backlog', query: ['backlog_id' => $backlogId]);
    }

    public function deleteBacklog(int|string $backlogId): mixed
    {
        return $this->transport->request('POST', '/marketmaker/delete-backlog', body: ['backlog_id' => $backlogId]);
    }
}
