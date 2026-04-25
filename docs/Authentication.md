# Authentication

Every SDK request (except a handful of public endpoints) requires an OAuth2 bearer token:

```
Authorization: Bearer <40-char token>
```

## Obtaining a token

1. Log in to [cryptohopper.com](https://www.cryptohopper.com).
2. **Developer → Create App** — gives you a `client_id` + `client_secret`.
3. Complete the OAuth consent flow for your app, which returns a bearer token.

Options to automate step 3:

- **The official CLI**: `cryptohopper login` opens the consent page, runs a loopback listener, and persists the token to `~/.cryptohopper/config.json`. Read that JSON from PHP and pull out `token`.
- **Your own code**: call the server's `/oauth2/authorize` + `/oauth2/token` endpoints directly. The CLI's implementation is short (~300 lines of TypeScript) and a reasonable reference.

## Client construction

```php
use Cryptohopper\Sdk\Client;

$client = new Client(
    apiKey:     getenv('CRYPTOHOPPER_TOKEN'),
    appKey:     getenv('CRYPTOHOPPER_APP_KEY') ?: null,
    baseUrl:    'https://api.cryptohopper.com/v1',
    timeout:    30,
    maxRetries: 3,
    userAgent:  'my-app/1.0',
);
```

Only `apiKey:` is required.

### `appKey:`

Cryptohopper lets OAuth apps identify themselves on every request via the `x-api-app-key` header (value = your OAuth `client_id`). When set, the SDK adds the header automatically. Reasons to set it:

- Shows up in Cryptohopper's server-side telemetry — you can attribute your own traffic.
- Drives per-app rate limits — if two apps share a token, they get independent quotas.
- Harmless to omit; the server accepts unattributed requests.

The SDK treats empty strings as "not set," so passing `getenv('CRYPTOHOPPER_APP_KEY') ?: null` is safe even when the env var is unset.

### `baseUrl:`

Override for staging or a local dev server. The default is `https://api.cryptohopper.com/v1`. The trailing `/v1` is part of the base; resource paths are relative to it.

```php
$client = new Client(
    apiKey: $token,
    baseUrl: 'https://api.staging.cryptohopper.com/v1',
);
```

### `httpClient:` — bring your own Guzzle client

If you need custom transport behaviour — proxies, custom CA bundles, connection-pool tuning, middleware (logging, tracing, request signing) — pass your own `GuzzleHttp\ClientInterface`:

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

$stack = HandlerStack::create();

// Add logging middleware
$stack->push(Middleware::log(
    $logger,
    new \GuzzleHttp\MessageFormatter('{method} {uri} → {code} {res_header_X-RateLimit-Remaining}')
));

// Add a request-signing middleware (custom; see Guzzle docs)
$stack->push(MyRequestSigner::middleware($signingKey));

$guzzle = new GuzzleClient([
    'handler' => $stack,
    'http_errors' => false,                   // SDK already handles non-2xx; keep this off
    'verify' => '/path/to/corp-ca-bundle.pem', // corporate root CA
    'proxy' => 'http://corp-proxy:3128',
    'connect_timeout' => 5,                    // separate from total timeout
]);

$client = new Client(
    apiKey: $token,
    httpClient: $guzzle,
);
```

The SDK doesn't override your client's options — set them as you need them. The SDK's per-request `timeout:` argument is passed via Guzzle's `['timeout' => ...]` request option, which works alongside any defaults you set on the client.

### `timeout:` and `maxRetries:`

`timeout:` is the per-request total timeout in seconds (Guzzle's `timeout` option, which maps to `CURLOPT_TIMEOUT` — covers the entire request including body). Defaults to 30.

`maxRetries:` is the number of automatic retries on HTTP 429. Default 3. Set to 0 to disable. See [Rate Limits](Rate-Limits.md).

### `userAgent:`

Appended after the SDK's own User-Agent (`cryptohopper-sdk-php/<version>`). Set this to identify your client to Cryptohopper support if you ever need to debug something with them.

## IP allowlisting

If your Cryptohopper app has IP allowlisting enabled, requests from unlisted IPs return `403 FORBIDDEN`. The SDK surfaces this as `CryptohopperException` with `getErrorCode() === 'FORBIDDEN'` and `getIpAddress()` populated:

```php
catch (CryptohopperException $e) {
    if ($e->getErrorCode() === 'FORBIDDEN') {
        $logger->warning('Cryptohopper blocked us', ['ip' => $e->getIpAddress()]);
    }
}
```

For CI where the runner IP isn't stable, either disable IP allowlisting for that app or route outbound traffic through a stable IP (NAT gateway, dedicated proxy).

## Rotating tokens

Cryptohopper bearer tokens are long-lived but can be revoked:

- Manually from the dashboard.
- When the user revokes consent.

The SDK surfaces revocation as `UNAUTHORIZED` on the next call. There is no automatic refresh-token handling in the SDK today — if your app uses refresh tokens, handle the `UNAUTHORIZED` branch by exchanging your refresh token for a new access token and constructing a fresh client. Wrap with a service-class to keep callers clean:

```php
final class CryptohopperGateway {
    private Client $client;

    public function __construct(
        private readonly TokenStore $tokens,
    ) {
        $this->client = $this->build();
    }

    public function call(callable $fn): mixed {
        try {
            return $fn($this->client);
        } catch (CryptohopperException $e) {
            if ($e->getErrorCode() !== 'UNAUTHORIZED') {
                throw $e;
            }
            $this->tokens->refresh();
            $this->client = $this->build();
            return $fn($this->client); // single retry with fresh client
        }
    }

    private function build(): Client {
        return new Client(apiKey: $this->tokens->current());
    }
}
```

The `Client` is cheap to construct — it's primarily a coordinator for resource classes plus a Guzzle reference. Constructing a fresh one on token refresh is the right call.

## Concurrency

`Client` is safe to share across processes/threads in standard PHP setups (PHP-FPM, Apache+mod_php) where each request gets its own process. Inside a single PHP process, it's not strictly thread-safe — but PHP's threading model means you typically don't need it to be.

For concurrent outbound calls within a single request, Guzzle's async pool primitives work directly:

```php
use GuzzleHttp\Promise;
use GuzzleHttp\Pool;

// Build many promises in parallel, bounded at 5 concurrent
$promises = function () use ($hopperIds, $client) {
    foreach ($hopperIds as $id) {
        yield function () use ($client, $id) {
            // Wrap a sync call in an async promise
            return Promise\Coroutine::of(function () use ($client, $id) {
                yield $client->hoppers->get($id);
            });
        };
    }
};
```

In practice, most PHP apps keep things synchronous. See [Rate Limits](Rate-Limits.md) for capping concurrency at the API quota.

## Public-only access (no token)

A handful of endpoints accept anonymous calls:

- `/market/*` — marketplace browse
- `/platform/*` — i18n, country list, blog feed
- `/exchange/ticker`, `/exchange/candle`, `/exchange/orderbook`, `/exchange/markets`, `/exchange/exchanges`, `/exchange/forex-rates` — public market data

The SDK still requires a non-empty `apiKey:` at construction; pass any placeholder if you only intend to hit public endpoints. The server ignores the bearer header on whitelisted routes.

```php
$client = new Client(apiKey: 'anonymous');
$btc = $client->exchange->ticker(exchange: 'binance', market: 'BTC/USDT');
```
