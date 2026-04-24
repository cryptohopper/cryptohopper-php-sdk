<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->platform` — marketing / i18n / discovery reads (all public).
 */
final class Platform
{
    public function __construct(private readonly Transport $transport)
    {
    }

    /** @param array<string, mixed> $params */
    public function latestBlog(array $params = []): mixed
    {
        return $this->transport->request('GET', '/platform/latestblog', query: $params !== [] ? $params : null);
    }

    /** @param array<string, mixed> $params */
    public function documentation(array $params = []): mixed
    {
        return $this->transport->request('GET', '/platform/documentation', query: $params !== [] ? $params : null);
    }

    public function promoBar(): mixed
    {
        return $this->transport->request('GET', '/platform/promobar');
    }

    public function searchDocumentation(string $query): mixed
    {
        return $this->transport->request('GET', '/platform/searchdocumentation', query: ['q' => $query]);
    }

    public function countries(): mixed
    {
        return $this->transport->request('GET', '/platform/countries');
    }

    public function countryAllowlist(): mixed
    {
        return $this->transport->request('GET', '/platform/countryallowlist');
    }

    public function ipCountry(): mixed
    {
        return $this->transport->request('GET', '/platform/ipcountry');
    }

    public function languages(): mixed
    {
        return $this->transport->request('GET', '/platform/languages');
    }

    public function botTypes(): mixed
    {
        return $this->transport->request('GET', '/platform/bottypes');
    }
}
