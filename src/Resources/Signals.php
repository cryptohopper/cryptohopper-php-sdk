<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->signals` — signal-provider analytics.
 *
 * Distinct from the marketplace browse at `$client->market->signals()`.
 */
final class Signals
{
    public function __construct(private readonly Transport $transport)
    {
    }

    /** @param array<string, mixed> $params */
    public function list(array $params = []): mixed
    {
        return $this->transport->request('GET', '/signals/signals', query: $params !== [] ? $params : null);
    }

    /** @param array<string, mixed> $params */
    public function performance(array $params = []): mixed
    {
        return $this->transport->request('GET', '/signals/performance', query: $params !== [] ? $params : null);
    }

    public function stats(): mixed
    {
        return $this->transport->request('GET', '/signals/stats');
    }

    public function distribution(): mixed
    {
        return $this->transport->request('GET', '/signals/distribution');
    }

    /** @param array<string, mixed> $params */
    public function chartData(array $params = []): mixed
    {
        return $this->transport->request('GET', '/signals/chartdata', query: $params !== [] ? $params : null);
    }
}
