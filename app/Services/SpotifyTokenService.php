<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\SpotifyToken;
use Carbon\Carbon;

class SpotifyTokenService
{
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

    public function getTokens($userId)
    {
        return SpotifyToken::where('user_id', $userId)->first();
    }

    public function refreshAccessToken($refreshToken)
    {
        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Basic ' . base64_encode(env('SPOTIFY_CLIENT_ID') . ':' . env('SPOTIFY_CLIENT_SECRET')),
        ])->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        return $data['access_token'] ?? null;
    }
}
