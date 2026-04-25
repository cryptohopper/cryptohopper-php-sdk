# Getting Started

## Install

```bash
composer require cryptohopper/sdk
```

Requires PHP 8.1 or newer. The package depends on `guzzlehttp/guzzle` ^7.8 — Composer will pull it in automatically.

## First call

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Cryptohopper\Sdk\Client;
use Cryptohopper\Sdk\Exceptions\CryptohopperException;

$client = new Client(apiKey: getenv('CRYPTOHOPPER_TOKEN'));

try {
    $me = $client->user->get();
    echo "Logged in as: {$me['email']}\n";

    $ticker = $client->exchange->ticker(exchange: 'binance', market: 'BTC/USDT');
    echo "BTC/USDT: {$ticker['last']}\n";
} catch (CryptohopperException $e) {
    fwrite(STDERR, "{$e->getErrorCode()} ({$e->getStatus()}): {$e->getMessage()}\n");
}
```

The `Client` is a plain PHP object — no `dispose()`, no context-manager equivalent needed. Construct it once at the top of your application and reuse for the lifetime of the request (in a typical PHP-FPM setup) or the lifetime of the process (in a long-running worker).

## Getting a token

Every request (except a handful of public endpoints like `/exchange/ticker`) needs an OAuth2 bearer token. Create one via **Developer → Create App** on [cryptohopper.com](https://www.cryptohopper.com) and complete the consent flow. The token is a 40-character opaque string.

For local dev:

```bash
export CRYPTOHOPPER_TOKEN=<your-token>
```

In production, load from your secret store of choice (Laravel `config/services.php` + Vault, Symfony `secrets:set`, AWS Secrets Manager via the AWS SDK, etc.) at boot.

## Idiomatic patterns

### Exception handling

The SDK throws `Cryptohopper\Sdk\Exceptions\CryptohopperException` for every API failure. The class extends `RuntimeException`, so an unqualified `catch (\Exception)` will catch it — but prefer the typed catch:

```php
use Cryptohopper\Sdk\Exceptions\CryptohopperException;

try {
    $client->hoppers->get(999_999);
} catch (CryptohopperException $e) {
    match ($e->getErrorCode()) {
        'NOT_FOUND' => null, // expected
        'UNAUTHORIZED' => $this->refreshToken(),
        'RATE_LIMITED' => $this->backoff($e->getRetryAfterMs()),
        'FORBIDDEN' => $this->logger->warning("Blocked from {$e->getIpAddress()}"),
        default => throw $e,
    };
}
```

Compare error codes with `===` against the strings in `CryptohopperException::KNOWN_CODES` — they're stable across SDK versions.

### Named arguments (PHP 8.0+)

The SDK uses named-argument call style throughout. It's especially useful for methods with many optional parameters:

```php
$client->exchange->candles(
    exchange: 'binance',
    market: 'BTC/USDT',
    timeframe: '1h',
    from: 1_700_000_000,
    to: 1_700_864_000,
);
```

### Customising the client

```php
use Cryptohopper\Sdk\Client;

$client = new Client(
    apiKey:     getenv('CRYPTOHOPPER_TOKEN'),
    appKey:     getenv('CRYPTOHOPPER_APP_KEY') ?: null,  // optional
    baseUrl:    'https://api.cryptohopper.com/v1',        // default
    timeout:    30,                                       // seconds
    maxRetries: 3,                                        // 429 backoff; 0 disables
    userAgent:  'my-app/1.0',                             // appended to UA
);
```

All constructor arguments except `apiKey:` are optional.

### Bringing your own HTTP client

The `httpClient:` constructor argument accepts any `GuzzleHttp\ClientInterface`. Useful for proxies, custom CA bundles, middleware (mocking, request signing, tracing):

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

$stack = HandlerStack::create();
$stack->push(Middleware::log($logger, new MessageFormatter('{method} {uri} → {code}')));

$guzzle = new GuzzleClient([
    'handler' => $stack,
    'http_errors' => false,           // SDK already handles non-2xx
    'verify' => '/path/to/corp-ca.pem', // corporate CA bundle
    'proxy' => 'http://corp-proxy:3128',
]);

$client = new Client(
    apiKey: getenv('CRYPTOHOPPER_TOKEN'),
    httpClient: $guzzle,
);
```

The SDK won't override `http_errors` on a bring-your-own client — set it on yours if you want consistent behaviour.

## Common pitfalls

**`InvalidArgumentException: apiKey must not be empty`** — the `apiKey:` argument is empty. Most often: `getenv('CRYPTOHOPPER_TOKEN')` returns `false` (when unset) which casts to empty string. Use a defensive load:

```php
$token = getenv('CRYPTOHOPPER_TOKEN');
if (!is_string($token) || $token === '') {
    throw new RuntimeException('CRYPTOHOPPER_TOKEN is not set');
}
$client = new Client(apiKey: $token);
```

**`CryptohopperException [UNAUTHORIZED/401]` on every call** — token is wrong, expired, or revoked. Visit the app's status in the Cryptohopper dashboard.

**`CryptohopperException [FORBIDDEN/403]` on endpoints that used to work** — IP allowlisting on the OAuth app blocked your current IP. The error includes the IP Cryptohopper saw:

```php
catch (CryptohopperException $e) {
    if ($e->getErrorCode() === 'FORBIDDEN') {
        echo "Blocked from {$e->getIpAddress()}\n";
    }
}
```

**`cURL error 60: SSL certificate problem`** — corporate proxy or self-signed root CA in the chain. Don't disable verification globally; supply a custom Guzzle client with `verify` pointing at the right CA bundle (see "Bringing your own HTTP client" above).

**`cURL error 28: Operation timed out`** — your server's outbound connection to Cryptohopper is slow. The SDK now classifies this as `TIMEOUT` (was `NETWORK_ERROR` before iter-16). Retry with a longer `timeout:` if the slowness is legitimate, or check your firewall / outbound NAT.

**Memory exhaustion on huge JSON responses** — the SDK reads the entire body into memory via `(string) $response->getBody()`. The Cryptohopper API doesn't return responses large enough to matter (no streaming endpoints), but if you hit a wall, supply a custom Guzzle client with stream-parsing middleware.

## Type signatures

Response shapes are returned as `array<string, mixed>` because the Cryptohopper API hasn't been frozen into stable models. PHPStan + the docblocks recognise this. To layer typed parsing on top, use a hydrator (Symfony Serializer, custom DTO mapper, etc.):

```php
final class Hopper {
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $exchange,
        public readonly bool $enabled,
    ) {}
}

$raw = $client->hoppers->get(42);
$hopper = new Hopper(
    id: (int) $raw['id'],
    name: (string) $raw['name'],
    exchange: (string) $raw['exchange'],
    enabled: (bool) $raw['enabled'],
);
```

## Next steps

- [Authentication](Authentication.md) — bearer flow, app keys, IP whitelisting, custom HTTP clients
- [Error Handling](Error-Handling.md) — every error code, recovery patterns, structured logging
- [Rate Limits](Rate-Limits.md) — auto-retry, customising back-off, concurrent workers
