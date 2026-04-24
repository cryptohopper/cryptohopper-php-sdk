# Cryptohopper PHP SDK

[![Packagist](https://img.shields.io/packagist/v/cryptohopper/sdk?logo=packagist&logoColor=white&include_prereleases)](https://packagist.org/packages/cryptohopper/sdk)
[![Packagist downloads](https://img.shields.io/packagist/dt/cryptohopper/sdk?logo=packagist&logoColor=white&label=downloads)](https://packagist.org/packages/cryptohopper/sdk)
[![PHP version](https://img.shields.io/packagist/php-v/cryptohopper/sdk?logo=php&logoColor=white)](composer.json)
[![CI](https://github.com/cryptohopper/cryptohopper-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/cryptohopper/cryptohopper-php-sdk/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/github/license/cryptohopper/cryptohopper-php-sdk?color=blue)](LICENSE)

Official PHP SDK for the Cryptohopper API — synchronous client, typed exceptions, auto-retry on 429, full coverage of all 18 public API domains.

## Requirements

- PHP 8.1+
- `guzzlehttp/guzzle` 7.8+ (installed as a dependency)

## Install

```bash
composer require cryptohopper/sdk
```

## Quick start

```php
<?php

use Cryptohopper\Sdk\Client;
use Cryptohopper\Sdk\Exceptions\CryptohopperException;

$client = new Client(apiKey: getenv('CRYPTOHOPPER_TOKEN'));

try {
    $me      = $client->user->get();
    $hoppers = $client->hoppers->list();
    $ticker  = $client->exchange->ticker(exchange: 'binance', market: 'BTC/USDT');
} catch (CryptohopperException $e) {
    fwrite(STDERR, "{$e->getCode()} / {$e->getStatus()}: {$e->getMessage()}\n");
    if ($e->getCode() === 'RATE_LIMITED' && $e->getRetryAfterMs() !== null) {
        fwrite(STDERR, "Retry after {$e->getRetryAfterMs()}ms\n");
    }
}
```

## Authentication

Every request (except endpoints explicitly marked `security: []` in the OpenAPI spec) requires a bearer token, which you issue through the Cryptohopper developer dashboard.

```php
$client = new Client(
    apiKey: 'your-40-char-oauth-token',
    appKey: 'your-oauth-client-id', // optional, sent as x-api-app-key
);
```

The optional `appKey` attaches your OAuth client_id to every request so Cryptohopper can attribute per-app rate limits.

## Configuration

```php
$client = new Client(
    apiKey:     getenv('CRYPTOHOPPER_TOKEN'),
    appKey:     getenv('CRYPTOHOPPER_APP_KEY'),
    baseUrl:    'https://api.cryptohopper.com/v1', // override for staging
    timeout:    30,                                 // per-request, seconds
    maxRetries: 3,                                  // HTTP 429 backoff
    userAgent:  'my-app/1.0',                       // appended after 'cryptohopper-sdk-php/<v>'
);
```

Pass `maxRetries: 0` to opt out of the automatic 429 backoff.

## Resources

| Resource                  | Example call                                      |
|---------------------------|---------------------------------------------------|
| `$client->user`           | `$client->user->get()`                            |
| `$client->hoppers`        | `$client->hoppers->list()`                        |
| `$client->exchange`       | `$client->exchange->ticker(exchange: 'binance', market: 'BTC/USDT')` |
| `$client->strategy`       | `$client->strategy->list()`                       |
| `$client->backtest`       | `$client->backtest->create($data)`                |
| `$client->market`         | `$client->market->signals()`                      |
| `$client->signals`        | `$client->signals->chartData()`                   |
| `$client->arbitrage`      | `$client->arbitrage->history()`                   |
| `$client->marketmaker`    | `$client->marketmaker->get()`                     |
| `$client->template`       | `$client->template->list()`                       |
| `$client->ai`             | `$client->ai->availableModels()`                  |
| `$client->platform`       | `$client->platform->countries()`                  |
| `$client->chart`          | `$client->chart->list()`                          |
| `$client->subscription`   | `$client->subscription->plans()`                  |
| `$client->social`         | `$client->social->getFeed()`                      |
| `$client->tournaments`    | `$client->tournaments->active()`                  |
| `$client->webhooks`       | `$client->webhooks->create($data)`                |
| `$client->app`            | `$client->app->receipt($data)`                    |

## Errors

Every non-2xx response (and every network/timeout failure) raises `Cryptohopper\Sdk\Exceptions\CryptohopperException`:

```php
try {
    $client->hoppers->get('not-a-real-id');
} catch (CryptohopperException $e) {
    $e->getCode();          // string: 'NOT_FOUND', 'UNAUTHORIZED', 'RATE_LIMITED', ...
    $e->getStatus();        // int: HTTP status (0 on network error)
    $e->getServerCode();    // int|null: Cryptohopper numeric code when present
    $e->getIpAddress();     // string|null: server-reported caller IP, if any
    $e->getRetryAfterMs();  // int|null: parsed Retry-After (only on 429)
}
```

Error codes are stable across every official Cryptohopper SDK (Node, Python, Go, Ruby, Rust, PHP).

## Rate limits

On HTTP 429 the client sleeps for `Retry-After` seconds (or an exponential-backoff fallback) and retries up to `maxRetries` times. The final failure — whether retries were exhausted or `maxRetries` was zero — surfaces as `CryptohopperException` with `code` = `RATE_LIMITED` and `retryAfterMs` populated.

## Versioning

Semantic versioning. While on `0.x`, minor releases can ship breaking changes — we call them out in [CHANGELOG.md](CHANGELOG.md). See the [versioning policy](https://github.com/cryptohopper/cryptohopper-resources/blob/main/VERSIONING.md) for the full SDK-wide policy.

## Official SDKs and CLI

| Language              | Install                           | Repository                                                                   |
|-----------------------|-----------------------------------|------------------------------------------------------------------------------|
| Node.js / TypeScript  | `npm i @cryptohopper/sdk`         | [`cryptohopper-node-sdk`](https://github.com/cryptohopper/cryptohopper-node-sdk) |
| Python                | `pip install cryptohopper`        | [`cryptohopper-python-sdk`](https://github.com/cryptohopper/cryptohopper-python-sdk) |
| Go                    | `go get github.com/cryptohopper/cryptohopper-go-sdk` | [`cryptohopper-go-sdk`](https://github.com/cryptohopper/cryptohopper-go-sdk) |
| Ruby                  | `gem install cryptohopper --pre`  | [`cryptohopper-ruby-sdk`](https://github.com/cryptohopper/cryptohopper-ruby-sdk) |
| Rust                  | `cargo add cryptohopper`          | [`cryptohopper-rust-sdk`](https://github.com/cryptohopper/cryptohopper-rust-sdk) |
| PHP                   | `composer require cryptohopper/sdk` | [`cryptohopper-php-sdk`](https://github.com/cryptohopper/cryptohopper-php-sdk) |
| CLI                   | `npm i -g @cryptohopper/cli` or binaries | [`cryptohopper-cli`](https://github.com/cryptohopper/cryptohopper-cli) |

## License

MIT — see [LICENSE](LICENSE).
