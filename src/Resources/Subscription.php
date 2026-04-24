<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->subscription` — plans, per-hopper state, credits, billing.
 */
final class Subscription
{
    public function __construct(private readonly Transport $transport)
    {
    }

    public function hopper(int|string $hopperId): mixed
    {
        return $this->transport->request('GET', '/subscription/hopper', query: ['hopper_id' => $hopperId]);
    }

    public function get(): mixed
    {
        return $this->transport->request('GET', '/subscription/get');
    }

    public function plans(): mixed
    {
        return $this->transport->request('GET', '/subscription/plans');
    }

    /** @param array<string, mixed> $data */
    public function remap(array $data): mixed
    {
        return $this->transport->request('POST', '/subscription/remap', body: $data);
    }

    /** @param array<string, mixed> $data */
    public function assign(array $data): mixed
    {
        return $this->transport->request('POST', '/subscription/assign', body: $data);
    }

    public function getCredits(): mixed
    {
        return $this->transport->request('GET', '/subscription/getcredits');
    }

    /** @param array<string, mixed> $data */
    public function orderSub(array $data): mixed
    {
        return $this->transport->request('POST', '/subscription/ordersub', body: $data);
    }

    /** @param array<string, mixed> $data */
    public function stopSubscription(array $data = []): mixed
    {
        return $this->transport->request('POST', '/subscription/stopsubscription', body: $data);
    }
}
