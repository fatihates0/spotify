<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SpotifyTokenService;

class RefreshSpotifyTokens extends Command
{
    protected $signature = 'spotify:refresh-tokens';
    protected $description = 'Refresh all Spotify tokens that are about to expire';

    public function handle()
    {
        $this->info('Spotify token yenileme başlatıldı...');
        (new SpotifyTokenService())->refreshExpiringTokens();
        $this->info('Spotify token yenileme tamamlandı.');
    }
}
