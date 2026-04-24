<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->ai` — AI credits + LLM analysis.
 */
final class Ai
{
    public function __construct(private readonly Transport $transport)
    {
    }

    /** @param array<string, mixed> $params */
    public function list(array $params = []): mixed
    {
        return $this->transport->request('GET', '/ai/list', query: $params !== [] ? $params : null);
    }

    public function get(int|string $id): mixed
    {
        return $this->transport->request('GET', '/ai/get', query: ['id' => $id]);
    }

    public function availableModels(): mixed
    {
        return $this->transport->request('GET', '/ai/availablemodels');
    }

    // ─── Credits ─────────────────────────────────────────────────────

    public function getCredits(): mixed
    {
        return $this->transport->request('GET', '/ai/getaicredits');
    }

    /** @param array<string, mixed> $params */
    public function creditInvoices(array $params = []): mixed
    {
        return $this->transport->request('GET', '/ai/aicreditinvoices', query: $params !== [] ? $params : null);
    }

    /** @param array<string, mixed> $params */
    public function creditTransactions(array $params = []): mixed
    {
        return $this->transport->request('GET', '/ai/aicredittransactions', query: $params !== [] ? $params : null);
    }

    /** @param array<string, mixed> $data */
    public function buyCredits(array $data): mixed
    {
        return $this->transport->request('POST', '/ai/buyaicredits', body: $data);
    }

    // ─── LLM analysis ────────────────────────────────────────────────

    public function llmAnalyzeOptions(): mixed
    {
        return $this->transport->request('GET', '/ai/aillmanalyzeoptions');
    }

    /** @param array<string, mixed> $data */
    public function llmAnalyze(array $data): mixed
    {
        return $this->transport->request('POST', '/ai/doaillmanalyze', body: $data);
    }

    /** @param array<string, mixed> $params */
    public function llmAnalyzeResults(array $params = []): mixed
    {
        return $this->transport->request('GET', '/ai/aillmanalyzeresults', query: $params !== [] ? $params : null);
    }

    /** @param array<string, mixed> $params */
    public function llmResults(array $params = []): mixed
    {
        return $this->transport->request('GET', '/ai/aillmresults', query: $params !== [] ? $params : null);
    }
}
