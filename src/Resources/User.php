<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->user` — authenticated user profile.
 */
final class User
{
    public function __construct(private readonly Transport $transport)
    {
    }

    /** Fetch the authenticated user's profile. Requires `user` scope. */
    public function get(): mixed
    {
        return $this->transport->request('GET', '/user/get');
    }
}
