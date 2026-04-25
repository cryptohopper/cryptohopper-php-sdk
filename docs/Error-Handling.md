# Error Handling

Every non-2xx response and every transport failure throws `Cryptohopper\Sdk\Exceptions\CryptohopperException`. Same shape as the Node/Python/Go/Ruby/Rust/Dart/Swift SDKs but laid out idiomatically as a PHP class with getter methods.

```php
use Cryptohopper\Sdk\Exceptions\CryptohopperException;

try {
    $client->hoppers->get(999_999);
} catch (CryptohopperException $e) {
    $e->getErrorCode();    // 'NOT_FOUND'
    $e->getStatus();       // 404
    $e->getMessage();      // human-readable, from the server
    $e->getServerCode();   // ?int — numeric Cryptohopper code (or null)
    $e->getIpAddress();    // ?string — server-reported caller IP (or null)
    $e->getRetryAfterMs(); // ?int — only set on 429
}
```

`CryptohopperException` extends `RuntimeException`, so any catch on `\Exception` or `\Throwable` will catch it too — but typed `catch (CryptohopperException $e)` is clearer and lets you handle SDK errors separately from your own.

## Why `getErrorCode()` and not `getCode()`?

PHP's built-in `Exception::getCode()` is typed as `int` and reserved for legacy numeric error codes. The shared SDK taxonomy is string-based (`'UNAUTHORIZED'`, `'RATE_LIMITED'`, etc.) so we expose it on `getErrorCode()`. `getCode()` on a `CryptohopperException` returns `0` (the parent's default) — don't read it.

## Error code catalog

| `getErrorCode()` | HTTP | When you'll see it | Recover by |
|---|---|---|---|
| `VALIDATION_ERROR` | 400, 422 | Missing or malformed parameter | Fix the request; the message says which parameter |
| `UNAUTHORIZED` | 401 | Token missing, wrong, or revoked | Re-auth |
| `DEVICE_UNAUTHORIZED` | 402 | Internal Cryptohopper device-auth flow rejected you | Shouldn't happen via the public API; contact support |
| `FORBIDDEN` | 403 | Scope missing, or IP not allowlisted | Check `$e->getIpAddress()`; add to allowlist or grant the scope |
| `NOT_FOUND` | 404 | Resource or endpoint doesn't exist | Check the ID; check you're using the latest SDK |
| `CONFLICT` | 409 | Resource is in a conflicting state | Cancel the existing job or wait |
| `RATE_LIMITED` | 429 | Bucket exhausted | The SDK auto-retries; see [Rate Limits](Rate-Limits.md) |
| `SERVER_ERROR` | 500–502, 504 | Cryptohopper's end | Retry with back-off |
| `SERVICE_UNAVAILABLE` | 503 | Planned maintenance or downstream outage | Respect `getRetryAfterMs()`; retry |
| `NETWORK_ERROR` | — | DNS failure, TCP reset, TLS handshake failure | Retry; check your network |
| `TIMEOUT` | — | Hit the client-side `timeout:`, including cURL's `CURLE_OPERATION_TIMEDOUT` | Retry; bump timeout if legitimately slow |
| `UNKNOWN` | any | Anything else the SDK didn't recognise | Inspect `$e->getStatus()` and `$e->getMessage()` |

These strings are stable across SDK versions and are also exposed as `CryptohopperException::KNOWN_CODES` — compare with `===`, never substring-match.

## TIMEOUT vs NETWORK_ERROR

Iter-16 fixed a subtle bug where Guzzle's `ConnectException` covered both real timeouts (cURL errno 28) and connection failures, but the SDK was labelling all of them `NETWORK_ERROR`. After the fix, errno 28 maps to `TIMEOUT` and other connect failures (errno 7 = couldn't connect, errno 6 = couldn't resolve host) map to `NETWORK_ERROR`.

The practical difference: a `TIMEOUT` is recoverable by **raising your `timeout:` value** and retrying; a `NETWORK_ERROR` typically requires **fixing your network** (DNS, firewall, outbound NAT). Treat them differently in your retry logic.

## Discriminating with `match`

```php
catch (CryptohopperException $e) {
    match ($e->getErrorCode()) {
        'UNAUTHORIZED', 'FORBIDDEN', 'DEVICE_UNAUTHORIZED' => $this->handleAuth($e),
        'VALIDATION_ERROR' => $this->logger->warning('bad request', ['msg' => $e->getMessage()]),
        'NOT_FOUND' => null, // expected
        'CONFLICT' => $this->retryOrSkip(),
        'RATE_LIMITED' => $this->backoffHard($e->getRetryAfterMs()),
        'SERVER_ERROR', 'SERVICE_UNAVAILABLE' => $this->scheduleRetry(),
        'NETWORK_ERROR', 'TIMEOUT' => $this->retryNow(),
        default => throw $e, // future-proof: re-throw unknown codes
    };
}
```

Future-proof your handler with a `default => throw $e` arm — the server can return new codes the SDK predates, and they pass through as raw strings on `getErrorCode()`.

## A robust retry wrapper

```php
use Cryptohopper\Sdk\Exceptions\CryptohopperException;

final class WithRetry {
    /** @var array<string, true> */
    private const TRANSIENT = [
        'SERVER_ERROR' => true,
        'SERVICE_UNAVAILABLE' => true,
        'NETWORK_ERROR' => true,
        'TIMEOUT' => true,
    ];

    public static function call(callable $fn, int $maxAttempts = 5, int $baseMs = 500): mixed {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $fn();
            } catch (CryptohopperException $e) {
                if (!isset(self::TRANSIENT[$e->getErrorCode()]) || $attempt === $maxAttempts) {
                    throw $e;
                }
                $waitMs = $e->getRetryAfterMs() ?? $baseMs * (2 ** ($attempt - 1));
                usleep($waitMs * 1000);
            }
        }
        throw new RuntimeException('unreachable'); // pacify static analysers
    }
}

WithRetry::call(fn() => $client->hoppers->list());
```

Don't include `RATE_LIMITED` in `TRANSIENT` — the SDK already retries 429s internally. Wrapping it here would multiply attempts unhelpfully.

## Structured logging

For Monolog (Laravel, Symfony, plain PHP), pull individual fields:

```php
use Psr\Log\LoggerInterface;

catch (CryptohopperException $e) {
    $logger->error('Cryptohopper request failed', [
        'code' => $e->getErrorCode(),
        'status' => $e->getStatus(),
        'server_code' => $e->getServerCode(),
        'ip' => $e->getIpAddress(),
        'retry_after_ms' => $e->getRetryAfterMs(),
        'message' => $e->getMessage(),
    ]);
}
```

### Sentry / Bugsnag

When reporting to an exception tracker, attach the SDK metadata as context so it's queryable in the dashboard:

```php
catch (CryptohopperException $e) {
    \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($e): void {
        $scope->setContext('cryptohopper', [
            'code' => $e->getErrorCode(),
            'status' => $e->getStatus(),
            'server_code' => $e->getServerCode(),
            'ip_address' => $e->getIpAddress(),
        ]);
        $scope->setTag('cryptohopper.code', $e->getErrorCode());
    });
    \Sentry\captureException($e);
    throw $e;
}
```

The `tag` lets you filter Sentry events by `cryptohopper.code` to see, e.g., a spike of `RATE_LIMITED` after a deploy.

### Laravel exception renderer

If you're surfacing SDK errors to API clients, register a custom renderer in `app/Exceptions/Handler.php`:

```php
public function register(): void {
    $this->renderable(function (CryptohopperException $e, Request $request) {
        return response()->json([
            'error' => [
                'code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ],
        ], match (true) {
            $e->getStatus() >= 400 && $e->getStatus() < 500 => 400,
            default => 502, // upstream failure
        });
    });
}
```

This propagates Cryptohopper's error semantics (4xx vs 5xx vs transport) without leaking server-side detail.

## Distinguishing transient from fatal

A common pattern is "retry only what's worth retrying":

```php
use Cryptohopper\Sdk\Exceptions\CryptohopperException;

$retryable = ['SERVER_ERROR', 'SERVICE_UNAVAILABLE', 'NETWORK_ERROR', 'TIMEOUT'];
$fatal = ['UNAUTHORIZED', 'FORBIDDEN', 'VALIDATION_ERROR', 'NOT_FOUND', 'CONFLICT'];

catch (CryptohopperException $e) {
    if (in_array($e->getErrorCode(), $retryable, true)) {
        $this->dispatchRetryJob($payload, after: 30);
    } elseif (in_array($e->getErrorCode(), $fatal, true)) {
        $this->logger->warning('fatal cryptohopper error', ['code' => $e->getErrorCode()]);
        $this->notifyUser($e->getMessage());
    } else {
        // RATE_LIMITED reaches here only if SDK retries exhausted.
        // UNKNOWN / new codes also fall through — log and re-throw.
        $this->logger->error('unexpected cryptohopper error', ['code' => $e->getErrorCode()]);
        throw $e;
    }
}
```

## Comparison with PHP's `Exception::getCode()`

`CryptohopperException::getCode()` (inherited) returns `0` always. The string error code is on `getErrorCode()`. Reading `getCode()` won't break anything, it just won't tell you what you want — easy to typo on autocomplete. The two existing because PHP's `Exception` constructor wants an `int $code`, and the parent class is `\RuntimeException` from PHP's stdlib.
