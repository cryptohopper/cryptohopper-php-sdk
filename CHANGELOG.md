# Changelog

All notable changes to `cryptohopper/sdk` are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project
adheres to [Semantic Versioning](https://semver.org/).

## [0.1.0-alpha.1] - 2026-04-24

Initial alpha release. Full coverage of the 18 public API domains from day one.

### Added

- `Cryptohopper\Sdk\Client` — synchronous client built on Guzzle 7.
- `Cryptohopper\Sdk\Exceptions\CryptohopperException` — single exception type; `code` follows the shared SDK taxonomy (`UNAUTHORIZED`, `FORBIDDEN`, `NOT_FOUND`, `RATE_LIMITED`, `VALIDATION_ERROR`, `DEVICE_UNAUTHORIZED`, `CONFLICT`, `SERVER_ERROR`, `SERVICE_UNAVAILABLE`, `NETWORK_ERROR`, `TIMEOUT`, `UNKNOWN`).
- Auto-retry on HTTP 429 honouring `Retry-After` (default `max_retries: 3`, disableable).
- Resource classes: `hoppers`, `exchange`, `user`, `strategy`, `backtest`, `market`, `signals`, `arbitrage`, `marketmaker`, `template`, `ai`, `platform`, `chart`, `subscription`, `social`, `tournaments`, `webhooks`, `app`.
- PHPUnit test suite covering error mapping, retry behaviour, and resource path/body wiring.
