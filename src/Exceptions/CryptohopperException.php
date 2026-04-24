<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Exceptions;

use RuntimeException;

/**
 * Single exception raised by every SDK call on non-2xx responses and on
 * network/timeout failures.
 *
 * Unknown server codes pass through as-is on {@see getCode()} so callers can
 * handle new codes without waiting for an SDK update.
 */
final class CryptohopperException extends RuntimeException
{
    public const KNOWN_CODES = [
        'VALIDATION_ERROR',
        'UNAUTHORIZED',
        'FORBIDDEN',
        'NOT_FOUND',
        'CONFLICT',
        'RATE_LIMITED',
        'SERVER_ERROR',
        'SERVICE_UNAVAILABLE',
        'DEVICE_UNAUTHORIZED',
        'NETWORK_ERROR',
        'TIMEOUT',
        'UNKNOWN',
    ];

    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly int $status,
        private readonly ?int $serverCode = null,
        private readonly ?string $ipAddress = null,
        private readonly ?int $retryAfterMs = null,
    ) {
        parent::__construct($message);
    }

    /**
     * Error code from the shared SDK taxonomy (see KNOWN_CODES), or a raw
     * server-provided string when the server returns something unrecognised.
     *
     * Shadowed by PHP's Exception::getCode() which is an int — we keep the
     * string form on a dedicated method to match the contract every other
     * Cryptohopper SDK exposes.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * HTTP status code. 0 for network/timeout failures where no response was
     * received.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Cryptohopper's numeric `code` field from the JSON error envelope, when
     * present. Null otherwise.
     */
    public function getServerCode(): ?int
    {
        return $this->serverCode;
    }

    /**
     * Caller IP as the server saw it, extracted from the error envelope's
     * `ip_address` field when the server includes it. Null otherwise.
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * Milliseconds to wait before retrying, parsed from the `Retry-After`
     * header on a 429. Null on any other error.
     */
    public function getRetryAfterMs(): ?int
    {
        return $this->retryAfterMs;
    }

    public function __toString(): string
    {
        $extras = [];
        if ($this->serverCode !== null) {
            $extras[] = "server_code={$this->serverCode}";
        }
        if ($this->ipAddress !== null) {
            $extras[] = "ip={$this->ipAddress}";
        }
        if ($this->retryAfterMs !== null) {
            $extras[] = "retry_after_ms={$this->retryAfterMs}";
        }
        $extra = $extras === [] ? '' : ' (' . implode(', ', $extras) . ')';

        return "CryptohopperException[{$this->errorCode}/{$this->status}]{$extra}: {$this->getMessage()}";
    }
}
