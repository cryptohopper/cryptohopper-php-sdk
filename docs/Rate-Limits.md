# Rate Limits

Cryptohopper applies per-bucket rate limits on the server. When you hit one, you get a `429` with a `Retry-After` header. The SDK handles this for you.

## The default behaviour

On every `429`, the SDK:

1. Parses `Retry-After` (either seconds-as-integer or HTTP-date form) into milliseconds.
2. Sleeps that long via `usleep` (falling back to exponential back-off if the header is missing).
3. Retries the request.
4. Repeats up to `maxRetries:` (default 3).

If retries exhaust, the call throws `CryptohopperException` with `getErrorCode() === 'RATE_LIMITED'` and `getRetryAfterMs()` set to the last seen retry hint.

## Configuring it

```php
$client = new Client(
    apiKey:     $token,
    maxRetries: 10,    // default 3
    timeout:    60,    // bump if 10 retries push past 30s total
);
```

To **disable** retries entirely (e.g. you want to do your own back-off):

```php
$client = new Client(apiKey: $token, maxRetries: 0);
```

With `maxRetries: 0`, a 429 throws immediately as `RATE_LIMITED`. Inspect `$e->getRetryAfterMs()` and schedule the retry on your own timeline.

## Buckets

Cryptohopper has three named buckets:

| Bucket | Scope | Example endpoints |
|---|---|---|
| `normal` | Most reads + writes | `/user/get`, `/hopper/list`, `/hopper/update`, `/exchange/ticker` |
| `order` | Anything that places or modifies orders | `/hopper/buy`, `/hopper/sell`, `/hopper/panic` |
| `backtest` | The (expensive) backtest subsystem | `/backtest/new`, `/backtest/get` |

The SDK doesn't know which bucket a call hits — it only sees the 429. You don't need to either; the server tells you when you're limited.

## Backfill jobs (own back-off)

If you're ingesting historical data and need to fetch many pages, take ownership of the back-off:

```php
use Cryptohopper\Sdk\Client;
use Cryptohopper\Sdk\Exceptions\CryptohopperException;

$client = new Client(apiKey: $token, maxRetries: 0);

foreach ($allHopperIds as $hopperId) {
    while (true) {
        try {
            $orders = $client->hoppers->orders($hopperId);
            $this->process($orders);
            break;
        } catch (CryptohopperException $e) {
            if ($e->getErrorCode() !== 'RATE_LIMITED') {
                throw $e;
            }
            $waitMs = $e->getRetryAfterMs() ?? 1000;
            usleep($waitMs * 1000);
        }
    }
}
```

This pattern lets a long-running job honour rate limits without stalling other work, because you decide the pacing.

## Concurrency in PHP

Vanilla PHP is single-process-per-request. For concurrent outbound calls within a single request, Guzzle's async pool primitives work — but most PHP apps benefit more from queue-based concurrency than in-process parallelism.

### Symfony Messenger / Laravel Horizon

For backfill or batch jobs, dispatch one message/job per resource and let your queue worker pool process them with bounded concurrency. The SDK's per-call retry handles the 429 case within each worker; the queue's retry-on-throw handles persistent failures.

```php
// Laravel job
final class FetchHopperOrders implements ShouldQueue {
    public int $tries = 5;
    public function backoff(): array {
        return [10, 30, 60, 120, 300];
    }

    public function handle(CryptohopperGateway $gw): void {
        $orders = $gw->call(fn($c) => $c->hoppers->orders($this->hopperId));
        $this->process($orders);
    }
}
```

Configure your worker concurrency at **4–8** to be comfortable for most accounts. Higher is feasible with `appKey:` set (which gives your OAuth app its own quota) but plan to back off explicitly.

### Guzzle async pool (single-process)

```php
use GuzzleHttp\Promise;

$promises = [];
foreach ($hopperIds as $id) {
    $promises[$id] = Promise\Coroutine::of(function () use ($client, $id) {
        yield $client->hoppers->get($id);
    });
}

$results = Promise\Utils::settle($promises)->wait();
```

Note: while Guzzle's promise pool runs requests "concurrently" in a single PHP process, PHP isn't truly parallel — it just multiplexes I/O. For real CPU concurrency you need workers/queues. Test before relying on this for high-throughput scenarios.

## Multi-process Apache / PHP-FPM

If you're running PHP behind Apache+mod_php or PHP-FPM with many worker processes, every process creates its own Guzzle client and its own retry budget. The Cryptohopper rate-limit quota is **shared** across all of them. So:

- 50 PHP-FPM workers each calling `$client->user->get()` simultaneously will likely trip `normal` bucket limits.
- Each worker's local SDK retry won't help because they all see the same 429.

Mitigation:

- Cache hot reads per-process (Laravel `Cache::remember`, APCu, Redis) so the same endpoint isn't hammered by every request.
- For bursty workloads, queue background work instead of doing it inline in request handlers.
- For high-volume integrations, set `appKey:` so each app gets its own quota — even if multiple environments share a single token.

## What the SDK does NOT do

- **No global semaphore.** Multiple PHP-FPM workers each get their own retry budget; they don't coordinate.
- **No adaptive slow-down.** After a 429, the SDK waits and retries that one call. It doesn't throttle future calls.
- **No client-side bucket tracking.** The server is the source of truth.
- **No cross-process rate-limit coordination.** If you need that, layer Redis-backed `rate-limit/laravel-throttle` / `symfony/rate-limiter` on top of your own queue.

## Diagnosing "always rate-limited"

If every request throws `RATE_LIMITED` even at low volume:

1. Check that your app hasn't been flagged for abuse in the Cryptohopper dashboard.
2. Confirm your retry logic doesn't accidentally retry on non-429 errors too — `$e->getErrorCode() === 'RATE_LIMITED'` is the canonical guard.
3. Inspect `$e->getServerCode()` — Cryptohopper sometimes includes a numeric detail there that clarifies which bucket you've tripped.
4. Confirm you're not sharing one token across many machines/environments. If you have multiple environments, give each a distinct token + `appKey:` for clean attribution.
5. If you have many PHP-FPM workers, see "Multi-process" above — the rate limit is per-quota, not per-process.

## `usleep` vs `sleep`

The SDK uses `usleep($waitMs * 1000)` to sleep in microseconds — this lets it honour sub-second `Retry-After: 0.5` values from the server. If your worker process can't tolerate brief blocking sleeps (cooperative-multitasking frameworks like ReactPHP, Amp, Swoole), wrap the SDK call in your framework's coroutine primitives:

```php
// Amp
use Amp\Future;
use function Amp\async;

$future = async(fn() => $client->hoppers->list());
$result = $future->await();
```

The SDK's blocking `usleep` will yield to the framework's event loop only if the framework patches stream functions globally (Swoole's `--enable-coroutine`, ReactPHP via `clue/blocking-coroutine`). Otherwise the worker is briefly blocked — fine for synchronous setups, careful for high-concurrency frameworks.
