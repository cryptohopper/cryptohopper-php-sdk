<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->chart` — saved chart layouts + shared chart links.
 */
final class Chart
{
    public function __construct(private readonly Transport $transport)
    {
    }

    public function list(): mixed
    {
        return $this->transport->request('GET', '/chart/list');
    }

    public function get(int|string $chartId): mixed
    {
        return $this->transport->request('GET', '/chart/get', query: ['chart_id' => $chartId]);
    }

    /** @param array<string, mixed> $data */
    public function save(array $data): mixed
    {
        return $this->transport->request('POST', '/chart/save', body: $data);
    }

    public function delete(int|string $chartId): mixed
    {
        return $this->transport->request('POST', '/chart/delete', body: ['chart_id' => $chartId]);
    }

    /** @param array<string, mixed> $data */
    public function shareSave(array $data): mixed
    {
        return $this->transport->request('POST', '/chart/share-save', body: $data);
    }

    public function shareGet(int|string $shareId): mixed
    {
        return $this->transport->request('GET', '/chart/share-get', query: ['share_id' => $shareId]);
    }
}
