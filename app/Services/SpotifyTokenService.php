<?php

namespace App\Services;

use App\Jobs\RefreshSpotifyAccessToken;
use App\Models\SpotifyToken;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SpotifyTokenService
{
    public function refreshAccessToken($refreshToken)
    {
        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Basic ' . base64_encode(
                    env('SPOTIFY_CLIENT_ID') . ':' . env('SPOTIFY_CLIENT_SECRET')
                ),
        ])->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function storeTokens($userId, $accessToken, $refreshToken, $expiresIn)
    {
        SpotifyToken::updateOrCreate(
            ['user_id' => $userId],
            [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'access_token_expires_at' => Carbon::now()->addSeconds($expiresIn),
            ]
        );
    }

    /**
     * 🔁 Tüm kullanıcıların token süresini kontrol et ve gerekirse yenile
     */
    public function refreshExpiringTokens()
    {
        SpotifyToken::where('access_token_expires_at', '<', now()->addMinutes(1))
            ->chunk(100, function ($tokens) {
                foreach ($tokens as $token) {
                    dispatch(new RefreshSpotifyAccessToken($token))->delay(rand(0, 60)); // rastgele gecikme
                }
            });
    }

}
