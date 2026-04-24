<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk;

use Cryptohopper\Sdk\Resources\Ai;
use Cryptohopper\Sdk\Resources\App;
use Cryptohopper\Sdk\Resources\Arbitrage;
use Cryptohopper\Sdk\Resources\Backtest;
use Cryptohopper\Sdk\Resources\Chart;
use Cryptohopper\Sdk\Resources\Exchange;
use Cryptohopper\Sdk\Resources\Hoppers;
use Cryptohopper\Sdk\Resources\Market;
use Cryptohopper\Sdk\Resources\MarketMaker;
use Cryptohopper\Sdk\Resources\Platform;
use Cryptohopper\Sdk\Resources\Signals;
use Cryptohopper\Sdk\Resources\Social;
use Cryptohopper\Sdk\Resources\Strategy;
use Cryptohopper\Sdk\Resources\Subscription;
use Cryptohopper\Sdk\Resources\Template;
use Cryptohopper\Sdk\Resources\Tournaments;
use Cryptohopper\Sdk\Resources\User;
use Cryptohopper\Sdk\Resources\Webhooks;
use GuzzleHttp\ClientInterface;

/**
 * Synchronous Cryptohopper API client.
 *
 * Example:
 * ```php
 * $client = new Cryptohopper\Sdk\Client(apiKey: getenv('CRYPTOHOPPER_TOKEN'));
 * $me     = $client->user->get();
 * $ticker = $client->exchange->ticker(exchange: 'binance', market: 'BTC/USDT');
 * ```
 */
final class Client
{
    public readonly User $user;
    public readonly Hoppers $hoppers;
    public readonly Exchange $exchange;
    public readonly Strategy $strategy;
    public readonly Backtest $backtest;
    public readonly Market $market;
    public readonly Signals $signals;
    public readonly Arbitrage $arbitrage;
    public readonly MarketMaker $marketmaker;
    public readonly Template $template;
    public readonly Ai $ai;
    public readonly Platform $platform;
    public readonly Chart $chart;
    public readonly Subscription $subscription;
    public readonly Social $social;
    public readonly Tournaments $tournaments;
    public readonly Webhooks $webhooks;
    public readonly App $app;

    /**
     * @internal Transport is exposed only for resource classes.
     */
    public readonly Transport $transport;

    /**
     * @param string               $apiKey     40-char OAuth2 bearer token.
     * @param string|null          $appKey     Optional OAuth client_id, sent
     *                                         as `x-api-app-key`.
     * @param string|null          $baseUrl    Override for staging.
     * @param int                  $timeout    Per-request timeout in seconds.
     * @param int                  $maxRetries Retries on HTTP 429 honouring
     *                                         `Retry-After`. 0 disables.
     * @param string|null          $userAgent  Appended after
     *                                         `cryptohopper-sdk-php/<v>`.
     * @param ClientInterface|null $httpClient Bring-your-own Guzzle instance
     *                                         (mainly for tests).
     */
    public function __construct(
        string $apiKey,
        ?string $appKey = null,
        ?string $baseUrl = null,
        int $timeout = Transport::DEFAULT_TIMEOUT,
        int $maxRetries = Transport::DEFAULT_MAX_RETRIES,
        ?string $userAgent = null,
        ?ClientInterface $httpClient = null,
    ) {
        $this->transport = new Transport(
            apiKey: $apiKey,
            appKey: $appKey,
            baseUrl: $baseUrl,
            timeout: $timeout,
            maxRetries: $maxRetries,
            userAgent: $userAgent,
            httpClient: $httpClient,
        );

        $this->user         = new User($this->transport);
        $this->hoppers      = new Hoppers($this->transport);
        $this->exchange     = new Exchange($this->transport);
        $this->strategy     = new Strategy($this->transport);
        $this->backtest     = new Backtest($this->transport);
        $this->market       = new Market($this->transport);
        $this->signals      = new Signals($this->transport);
        $this->arbitrage    = new Arbitrage($this->transport);
        $this->marketmaker  = new MarketMaker($this->transport);
        $this->template     = new Template($this->transport);
        $this->ai           = new Ai($this->transport);
        $this->platform     = new Platform($this->transport);
        $this->chart        = new Chart($this->transport);
        $this->subscription = new Subscription($this->transport);
        $this->social       = new Social($this->transport);
        $this->tournaments  = new Tournaments($this->transport);
        $this->webhooks     = new Webhooks($this->transport);
        $this->app          = new App($this->transport);
    }
}
