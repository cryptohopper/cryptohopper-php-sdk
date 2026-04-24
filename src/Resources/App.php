<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->app` — mobile app store receipts + in-app purchases.
 */
final class App
{
    public function __construct(private readonly Transport $transport)
    {
    }

    /** @param array<string, mixed> $data */
    public function receipt(array $data): mixed
    {
        return $this->transport->request('POST', '/app/receipt', body: $data);
    }

    /** @param array<string, mixed> $data */
    public function inAppPurchase(array $data): mixed
    {
        return $this->transport->request('POST', '/app/in_app_purchase', body: $data);
    }
}
