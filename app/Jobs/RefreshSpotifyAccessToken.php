<?php

namespace App\Jobs;

use App\Models\SpotifyToken;
use App\Services\SpotifyTokenService;

class RefreshSpotifyAccessToken extends Job
{
    protected $token;

    public function __construct(SpotifyToken $token)
    {
        $this->token = $token;
    }

    public function handle()
    {
        (new SpotifyTokenService())->refreshAccessToken($this->token);
    }
}
