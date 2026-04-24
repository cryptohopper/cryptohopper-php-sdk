<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->webhooks` — developer webhook registration.
 *
 * Maps to the server's `/api/webhook_*` endpoints; named for clarity.
 */
final class Webhooks
{
    public function __construct(private readonly Transport $transport)
    {
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): mixed
    {
        return $this->transport->request('POST', '/api/webhook_create', body: $data);
    }

    public function delete(int|string $webhookId): mixed
    {
        return $this->transport->request('POST', '/api/webhook_delete', body: ['webhook_id' => $webhookId]);
    }
}
