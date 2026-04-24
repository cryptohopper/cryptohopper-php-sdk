<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->tournaments` — trading competitions.
 */
final class Tournaments
{
    public function __construct(private readonly Transport $transport)
    {
    }

    /** @param array<string, mixed> $params */
    public function list(array $params = []): mixed
    {
        return $this->transport->request('GET', '/tournaments/gettournaments', query: $params !== [] ? $params : null);
    }

    public function active(): mixed
    {
        return $this->transport->request('GET', '/tournaments/active');
    }

    public function get(int|string $tournamentId): mixed
    {
        return $this->transport->request('GET', '/tournaments/gettournament', query: ['tournament_id' => $tournamentId]);
    }

    public function search(string $query): mixed
    {
        return $this->transport->request('GET', '/tournaments/search', query: ['q' => $query]);
    }

    public function trades(int|string $tournamentId): mixed
    {
        return $this->transport->request('GET', '/tournaments/trades', query: ['tournament_id' => $tournamentId]);
    }

    public function stats(int|string $tournamentId): mixed
    {
        return $this->transport->request('GET', '/tournaments/stats', query: ['tournament_id' => $tournamentId]);
    }

    public function activity(int|string $tournamentId): mixed
    {
        return $this->transport->request('GET', '/tournaments/activity', query: ['tournament_id' => $tournamentId]);
    }

    /** @param array<string, mixed> $params */
    public function leaderboard(array $params = []): mixed
    {
        return $this->transport->request('GET', '/tournaments/leaderboard', query: $params !== [] ? $params : null);
    }

    public function tournamentLeaderboard(int|string $tournamentId): mixed
    {
        return $this->transport->request('GET', '/tournaments/leaderboard_tournament', query: ['tournament_id' => $tournamentId]);
    }

    /** @param array<string, mixed> $data */
    public function join(int|string $tournamentId, array $data = []): mixed
    {
        return $this->transport->request('POST', '/tournaments/join', body: ['tournament_id' => $tournamentId] + $data);
    }

    public function leave(int|string $tournamentId): mixed
    {
        return $this->transport->request('POST', '/tournaments/leave', body: ['tournament_id' => $tournamentId]);
    }
}
