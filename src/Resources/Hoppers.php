<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->hoppers` — user trading bots (CRUD, positions, orders, trade, config).
 */
final class Hoppers
{
    public function __construct(private readonly Transport $transport)
    {
    }

    public function list(?string $exchange = null): mixed
    {
        return $this->transport->request('GET', '/hopper/list', query: $exchange !== null ? ['exchange' => $exchange] : null);
    }

    public function get(int|string $hopperId): mixed
    {
        return $this->transport->request('GET', '/hopper/get', query: ['hopper_id' => $hopperId]);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): mixed
    {
        return $this->transport->request('POST', '/hopper/create', body: $data);
    }

    /** @param array<string, mixed> $data */
    public function update(int|string $hopperId, array $data): mixed
    {
        return $this->transport->request('POST', '/hopper/update', body: ['hopper_id' => $hopperId] + $data);
    }

    public function delete(int|string $hopperId): mixed
    {
        return $this->transport->request('POST', '/hopper/delete', body: ['hopper_id' => $hopperId]);
    }

    public function positions(int|string $hopperId): mixed
    {
        return $this->transport->request('GET', '/hopper/positions', query: ['hopper_id' => $hopperId]);
    }

    public function position(int|string $hopperId, int|string $positionId): mixed
    {
        return $this->transport->request('GET', '/hopper/position', query: [
            'hopper_id'   => $hopperId,
            'position_id' => $positionId,
        ]);
    }

    /** @param array<string, mixed> $extra */
    public function orders(int|string $hopperId, array $extra = []): mixed
    {
        return $this->transport->request('GET', '/hopper/orders', query: ['hopper_id' => $hopperId] + $extra);
    }

    /** @param array<string, mixed> $data */
    public function buy(array $data): mixed
    {
        return $this->transport->request('POST', '/hopper/buy', body: $data);
    }

    /** @param array<string, mixed> $data */
    public function sell(array $data): mixed
    {
        return $this->transport->request('POST', '/hopper/sell', body: $data);
    }

    public function configGet(int|string $hopperId): mixed
    {
        return $this->transport->request('GET', '/hopper/configget', query: ['hopper_id' => $hopperId]);
    }

    /** @param array<string, mixed> $config */
    public function configUpdate(int|string $hopperId, array $config): mixed
    {
        return $this->transport->request('POST', '/hopper/configupdate', body: ['hopper_id' => $hopperId] + $config);
    }

    public function configPools(int|string $hopperId): mixed
    {
        return $this->transport->request('GET', '/hopper/configpools', query: ['hopper_id' => $hopperId]);
    }

    public function panic(int|string $hopperId): mixed
    {
        return $this->transport->request('POST', '/hopper/panic', body: ['hopper_id' => $hopperId]);
    }
}
