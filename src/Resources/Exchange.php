<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->exchange` — public market data (no auth required for most endpoints).
 */
final class Exchange
{
    public function __construct(private readonly Transport $transport)
    {
    }

    public function ticker(string $exchange, string $market): mixed
    {
        return $this->transport->request('GET', '/exchange/ticker', query: [
            'exchange' => $exchange,
            'market'   => $market,
        ]);
    }

    public function candles(
        string $exchange,
        string $market,
        string $timeframe,
        ?int $from = null,
        ?int $to = null,
    ): mixed {
        return $this->transport->request('GET', '/exchange/candle', query: [
            'exchange'  => $exchange,
            'market'    => $market,
            'timeframe' => $timeframe,
            'from'      => $from,
            'to'        => $to,
        ]);
    }

    public function orderbook(string $exchange, string $market): mixed
    {
        return $this->transport->request('GET', '/exchange/orderbook', query: [
            'exchange' => $exchange,
            'market'   => $market,
        ]);
    }

    public function markets(string $exchange): mixed
    {
        return $this->transport->request('GET', '/exchange/markets', query: ['exchange' => $exchange]);
    }

    public function currencies(string $exchange): mixed
    {
        return $this->transport->request('GET', '/exchange/currencies', query: ['exchange' => $exchange]);
    }

    public function exchanges(): mixed
    {
        return $this->transport->request('GET', '/exchange/exchanges');
    }

    public function forexRates(): mixed
    {
        return $this->transport->request('GET', '/exchange/forex-rates');
    }
}
