<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->arbitrage` — exchange + market arbitrage + shared backlog.
 */
final class Arbitrage
{
    public function __construct(private readonly Transport $transport)
    {
    }

    // ─── Cross-exchange arbitrage ─────────────────────────────────────

    /** @param array<string, mixed> $data */
    public function exchangeStart(array $data): mixed
    {
        return $this->transport->request('POST', '/arbitrage/exchange', body: $data);
    }

    /** @param array<string, mixed> $data */
    public function exchangeCancel(array $data = []): mixed
    {
        return $this->transport->request('POST', '/arbitrage/cancel', body: $data);
    }

    /** @param array<string, mixed> $params */
    public function exchangeResults(array $params = []): mixed
    {
        return $this->transport->request('GET', '/arbitrage/results', query: $params !== [] ? $params : null);
    }

    /** @param array<string, mixed> $params */
    public function exchangeHistory(array $params = []): mixed
    {
        return $this->transport->request('GET', '/arbitrage/history', query: $params !== [] ? $params : null);
    }

    public function exchangeTotal(): mixed
    {
        return $this->transport->request('GET', '/arbitrage/total');
    }

    public function exchangeResetTotal(): mixed
    {
        return $this->transport->request('POST', '/arbitrage/resettotal', body: []);
    }

    // ─── Intra-exchange market arbitrage ──────────────────────────────

    /** @param array<string, mixed> $data */
    public function marketStart(array $data): mixed
    {
        return $this->transport->request('POST', '/arbitrage/market', body: $data);
    }

    /** @param array<string, mixed> $data */
    public function marketCancel(array $data = []): mixed
    {
        return $this->transport->request('POST', '/arbitrage/market-cancel', body: $data);
    }

    /** @param array<string, mixed> $params */
    public function marketResult(array $params = []): mixed
    {
        return $this->transport->request('GET', '/arbitrage/market-result', query: $params !== [] ? $params : null);
    }

    /** @param array<string, mixed> $params */
    public function marketHistory(array $params = []): mixed
    {
        return $this->transport->request('GET', '/arbitrage/market-history', query: $params !== [] ? $params : null);
    }

    // ─── Backlog (shared) ─────────────────────────────────────────────

    /** @param array<string, mixed> $params */
    public function backlogs(array $params = []): mixed
    {
        return $this->transport->request('GET', '/arbitrage/get-backlogs', query: $params !== [] ? $params : null);
    }

    public function backlog(int|string $backlogId): mixed
    {
        return $this->transport->request('GET', '/arbitrage/get-backlog', query: ['backlog_id' => $backlogId]);
    }

    public function deleteBacklog(int|string $backlogId): mixed
    {
        return $this->transport->request('POST', '/arbitrage/delete-backlog', body: ['backlog_id' => $backlogId]);
    }
}
