<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk;

use Cryptohopper\Sdk\Exceptions\CryptohopperException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal Transport is called by resource classes, not by SDK users. The
 *           public interface lives on {@see Client}.
 */
final class Transport
{
    public const DEFAULT_BASE_URL    = 'https://api.cryptohopper.com/v1';
    public const DEFAULT_TIMEOUT     = 30;
    public const DEFAULT_MAX_RETRIES = 3;

    private readonly string $baseUrl;
    private readonly ClientInterface $httpClient;

    public function __construct(
        private readonly string $apiKey,
        private readonly ?string $appKey = null,
        ?string $baseUrl = null,
        private readonly int $timeout = self::DEFAULT_TIMEOUT,
        private readonly int $maxRetries = self::DEFAULT_MAX_RETRIES,
        private readonly ?string $userAgent = null,
        ?ClientInterface $httpClient = null,
    ) {
        if ($this->apiKey === '') {
            throw new \InvalidArgumentException('apiKey must not be empty');
        }
        $this->baseUrl    = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/');
        $this->httpClient = $httpClient ?? new GuzzleClient(['http_errors' => false]);
    }

    /**
     * Perform a request against the Cryptohopper API. Unwraps the standard
     * `{data: ...}` envelope and retries on HTTP 429 up to `maxRetries`.
     *
     * @param array<string, mixed>|null $query
     * @param array<string, mixed>|null $body
     *
     * @return mixed Whatever sits under the `data` key on success, or the raw
     *               decoded JSON body when there is no envelope.
     *
     * @throws CryptohopperException on any non-2xx response or transport
     *                               failure once retries are exhausted.
     */
    public function request(string $method, string $path, ?array $query = null, ?array $body = null): mixed
    {
        $attempt = 0;
        while (true) {
            try {
                return $this->send($method, $path, $query, $body);
            } catch (CryptohopperException $e) {
                if ($e->getErrorCode() !== 'RATE_LIMITED' || $attempt >= $this->maxRetries) {
                    throw $e;
                }

                $waitMs = $e->getRetryAfterMs() ?? (int) (1000 * (2 ** $attempt));
                if ($waitMs > 0) {
                    usleep($waitMs * 1000);
                }
                $attempt++;
            }
        }
    }

    /**
     * @param array<string, mixed>|null $query
     * @param array<string, mixed>|null $body
     */
    private function send(string $method, string $path, ?array $query, ?array $body): mixed
    {
        $url     = $this->buildUrl($path, $query);
        $headers = $this->buildHeaders($body !== null);
        $payload = $body !== null ? (string) json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

        try {
            $response = $this->httpClient->send(
                new Request(strtoupper($method), $url, $headers, $payload),
                ['timeout' => $this->timeout, 'http_errors' => false],
            );
        } catch (ConnectException $e) {
            // Guzzle's ConnectException is the umbrella for all cURL transport
            // failures, *including* timeouts (CURLE_OPERATION_TIMEDOUT,
            // errno 28). Discriminate so callers see the correct error code:
            // a TIMEOUT is recoverable by raising the timeout, a NETWORK_ERROR
            // is not.
            $context = $e->getHandlerContext();
            $errno   = (isset($context['errno']) && is_int($context['errno'])) ? $context['errno'] : 0;
            $msgLower  = strtolower($e->getMessage());
            $isTimeout = $errno === 28
                || str_contains($msgLower, 'timed out')
                || str_contains($msgLower, 'timeout');

            throw new CryptohopperException(
                $isTimeout ? 'TIMEOUT' : 'NETWORK_ERROR',
                $isTimeout
                    ? "Request timed out after {$this->timeout}s ({$e->getMessage()})"
                    : "Could not reach {$this->baseUrl} ({$e->getMessage()})",
                0,
            );
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                /** @var ResponseInterface $response */
                $response = $e->getResponse();
            } else {
                throw new CryptohopperException(
                    'NETWORK_ERROR',
                    $e->getMessage(),
                    0,
                );
            }
        } catch (\Throwable $e) {
            $message = strtolower($e->getMessage());
            $code    = str_contains($message, 'timed out') || str_contains($message, 'timeout')
                ? 'TIMEOUT'
                : 'NETWORK_ERROR';
            throw new CryptohopperException($code, $e->getMessage(), 0);
        }

        return $this->handleResponse($response);
    }

    /**
     * @param array<string, mixed>|null $query
     */
    private function buildUrl(string $path, ?array $query): string
    {
        $full = str_starts_with($path, '/') ? $path : "/{$path}";
        $url  = "{$this->baseUrl}{$full}";

        if ($query !== null && $query !== []) {
            $clean = array_filter($query, static fn ($v) => $v !== null);
            if ($clean !== []) {
                $qs  = http_build_query($clean, '', '&', PHP_QUERY_RFC3986);
                $url .= (str_contains($url, '?') ? '&' : '?') . $qs;
            }
        }

        return $url;
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(bool $hasBody): array
    {
        $headers = [
            'Authorization' => "Bearer {$this->apiKey}",
            'Accept'        => 'application/json',
            'User-Agent'    => $this->buildUserAgent(),
        ];
        if ($this->appKey !== null && $this->appKey !== '') {
            $headers['x-api-app-key'] = $this->appKey;
        }
        if ($hasBody) {
            $headers['Content-Type'] = 'application/json';
        }

        return $headers;
    }

    private function buildUserAgent(): string
    {
        $base = 'cryptohopper-sdk-php/' . Version::VERSION;

        return $this->userAgent !== null && $this->userAgent !== ''
            ? "{$base} {$this->userAgent}"
            : $base;
    }

    private function handleResponse(ResponseInterface $response): mixed
    {
        $status = $response->getStatusCode();
        $raw    = (string) $response->getBody();
        $parsed = $this->parseJson($raw);

        if ($status >= 400) {
            throw $this->buildError($status, $parsed, $response);
        }

        if (is_array($parsed) && array_key_exists('data', $parsed)) {
            return $parsed['data'];
        }

        return $parsed;
    }

    private function parseJson(string $raw): mixed
    {
        if ($raw === '') {
            return null;
        }
        try {
            return json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }

    private function buildError(int $status, mixed $parsed, ResponseInterface $response): CryptohopperException
    {
        $body       = is_array($parsed) ? $parsed : [];
        $message    = is_string($body['message'] ?? null) ? $body['message'] : "Request failed ({$status})";
        $rawCode    = $body['code'] ?? null;
        $serverCode = is_int($rawCode) && $rawCode > 0 ? $rawCode : null;
        $ipAddress  = is_string($body['ip_address'] ?? null) ? $body['ip_address'] : null;
        $retryAfter = $this->parseRetryAfter($response->getHeaderLine('Retry-After'));

        return new CryptohopperException(
            $this->defaultCodeForStatus($status),
            $message,
            $status,
            $serverCode,
            $ipAddress,
            $retryAfter,
        );
    }

    private function defaultCodeForStatus(int $status): string
    {
        return match (true) {
            $status === 400, $status === 422 => 'VALIDATION_ERROR',
            $status === 401                  => 'UNAUTHORIZED',
            $status === 402                  => 'DEVICE_UNAUTHORIZED',
            $status === 403                  => 'FORBIDDEN',
            $status === 404                  => 'NOT_FOUND',
            $status === 409                  => 'CONFLICT',
            $status === 429                  => 'RATE_LIMITED',
            $status === 503                  => 'SERVICE_UNAVAILABLE',
            $status >= 500                   => 'SERVER_ERROR',
            default                          => 'UNKNOWN',
        };
    }

    private function parseRetryAfter(string $header): ?int
    {
        if ($header === '') {
            return null;
        }

        if (is_numeric($header)) {
            $seconds = (float) $header;
            if ($seconds < 0) {
                return null;
            }

            return (int) round($seconds * 1000);
        }

        $timestamp = strtotime($header);
        if ($timestamp === false) {
            return null;
        }
        $delta = ($timestamp - time()) * 1000;

        return max(0, $delta);
    }
}
