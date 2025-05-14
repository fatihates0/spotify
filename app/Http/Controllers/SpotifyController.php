<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\SpotifyTokenService;

class SpotifyController extends Controller
{
    protected $tokenService;

    public function __construct(SpotifyTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function login()
    {
        $query = http_build_query([
            'client_id' => env('SPOTIFY_CLIENT_ID'),
            'response_type' => 'code',
            'redirect_uri' => env('SPOTIFY_REDIRECT_URI'),
            'scope' => 'user-read-currently-playing user-read-playback-state',
        ]);

        return redirect("https://accounts.spotify.com/authorize?$query");
    }

    public function callback(Request $request)
    {
        $code = $request->input('code');

        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Basic ' . base64_encode(env('SPOTIFY_CLIENT_ID') . ':' . env('SPOTIFY_CLIENT_SECRET')),
        ])->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => env('SPOTIFY_REDIRECT_URI'),
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Token alınamadı', 'details' => $response->json()], 400);
        }

        $data = $response->json();

        // Örnek olarak kullanıcı ID'sini 1 kabul ediyoruz
        $userId = auth()->id() ?? 1;

        $this->tokenService->storeTokens(
            $userId,
            $data['access_token'],
            $data['refresh_token'],
            $data['expires_in']
        );

        return redirect('/spotify/playing');
    }

    public function currentlyPlaying(Request $request)
    {
        $userId = auth()->id() ?? 1;

        $tokenRecord = $this->tokenService->getTokens($userId);

        if (!$tokenRecord) {
            return response()->json(['error' => 'Spotify token not found.'], 401);
        }

        // Token süresi kontrolü
        if ($tokenRecord->isAccessTokenExpired()) {
            $newToken = $this->tokenService->refreshAccessToken($tokenRecord->refresh_token);
            if (!$newToken) {
                return response()->json(['error' => 'Access token yenilenemedi.'], 401);
            }

            $this->tokenService->storeTokens($userId, $newToken, $tokenRecord->refresh_token, 3600);
            $tokenRecord->access_token = $newToken;
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$tokenRecord->access_token}"
        ])->get('https://api.spotify.com/v1/me/player/currently-playing');

        if ($response->status() === 204) {
            return response()->json(['message' => 'Şu anda çalan şarkı yok.']);
        }

        if ($response->failed()) {
            return response()->json(['error' => 'Spotify API hatası.', 'details' => $response->json()], $response->status());
        }

        $data = $response->json();

        $result = [
            'track' => $data['item'] ?? null,
            'is_playing' => $data['is_playing'] ?? false,
            'progress_ms' => $data['progress_ms'] ?? null,
            'timestamp' => $data['timestamp'] ?? null,
        ];

        if (isset($data['context']['type']) && $data['context']['type'] === 'playlist') {
            $playlistUri = $data['context']['uri'];
            $playlistId = explode(':', $playlistUri)[2];

            $playlistResponse = Http::withHeaders([
                'Authorization' => "Bearer {$tokenRecord->access_token}"
            ])->get("https://api.spotify.com/v1/playlists/{$playlistId}");

            $result['playlist'] = $playlistResponse->ok()
                ? $playlistResponse->json()
                : ['error' => 'Playlist bilgisi alınamadı'];
        } else {
            $result['playlist'] = null;
        }

        return response()->json($result);
    }
}
