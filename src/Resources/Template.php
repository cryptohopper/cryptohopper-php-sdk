<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->template` — bot templates (reusable hopper configurations).
 */
final class Template
{
    public function __construct(private readonly Transport $transport)
    {
    }

    public function list(): mixed
    {
        return $this->transport->request('GET', '/template/templates');
    }

    public function get(int|string $templateId): mixed
    {
        return $this->transport->request('GET', '/template/get', query: ['template_id' => $templateId]);
    }

    public function basic(int|string $templateId): mixed
    {
        return $this->transport->request('GET', '/template/basic', query: ['template_id' => $templateId]);
    }

    /** @param array<string, mixed> $data */
    public function save(array $data): mixed
    {
        return $this->transport->request('POST', '/template/save-template', body: $data);
    }

    /** @param array<string, mixed> $data */
    public function update(int|string $templateId, array $data): mixed
    {
        return $this->transport->request('POST', '/template/update', body: ['template_id' => $templateId] + $data);
    }

    public function load(int|string $templateId, int|string $hopperId): mixed
    {
        return $this->transport->request('POST', '/template/load', body: [
            'template_id' => $templateId,
            'hopper_id'   => $hopperId,
        ]);
    }

    public function delete(int|string $templateId): mixed
    {
        return $this->transport->request('POST', '/template/delete', body: ['template_id' => $templateId]);
    }
}
