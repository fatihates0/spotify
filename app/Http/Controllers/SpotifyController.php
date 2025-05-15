<?php

namespace App\Http\Controllers;

use App\Models\SpotifyToken;
use Carbon\Carbon;
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

        return redirect('/spotify/show');
    }

    public function logout()
    {
        $userId = auth()->id() ?? 1;

        // Tokenları veritabanından sil
        \App\Models\SpotifyToken::where('user_id', $userId)->delete();

        // Kullanıcıyı sistemden de çıkarmak istersen:
        // auth()->logout();

        return redirect('/spotify/login')->with('message', 'Spotify bağlantısı başarıyla kaldırıldı.');
    }

    public function currentlyPlaying($uniq_id)
    {
        $userId = SpotifyToken::where('uniq_id',$uniq_id)->first()->user_id ?? 1;

        $tokenRecord = \App\Models\SpotifyToken::where('user_id', $userId)->first();

        if (!$tokenRecord) {
            return response()->json(['error' => 'Spotify token kaydı bulunamadı.'], 401);
        }

        // ✅ Token'ın kalan süresini kontrol et
        $now = Carbon::now();
        $expiresAt = $tokenRecord->access_token_expires_at;
        $remainingSeconds = $now->diffInSeconds($expiresAt, false);

        if ($remainingSeconds < 10) { // 59 dakikadan az kaldıysa yenile
            $spotifyService = new \App\Services\SpotifyTokenService();
            $newAccessToken = $spotifyService->refreshAccessToken($tokenRecord->refresh_token);

            if (!$newAccessToken) {
                return response()->json(['error' => 'Access token yenilenemedi.']);
            }

            $expiresIn = 3600; // Spotify token süresi
            $spotifyService->storeTokens($userId, $newAccessToken, $tokenRecord->refresh_token, $expiresIn);

            // Token'ı tekrar çek
            $tokenRecord = \App\Models\SpotifyToken::where('user_id', $userId)->first();
            $remainingSeconds = 3600;
        }

        $accessToken = $tokenRecord->access_token;

        // Spotify API'den o an çalan şarkıyı al
        $response = Http::withHeaders([
            'Authorization' => "Bearer $accessToken"
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
            'token_expires_in_seconds' => $remainingSeconds,
        ];

        // Playlist bilgisi varsa al
        if (isset($data['context']['type']) && $data['context']['type'] === 'playlist') {
            $playlistUri = $data['context']['uri'];
            $playlistId = last(explode(':', $playlistUri));

            $playlistResponse = Http::withHeaders([
                'Authorization' => "Bearer $accessToken"
            ])->get("https://api.spotify.com/v1/playlists/$playlistId");

            if ($playlistResponse->ok()) {
                $result['playlist'] = $playlistResponse->json();
            } else {
                $result['playlist'] = ['error' => 'Playlist bilgisi alınamadı'];
            }
        } else {
            $result['playlist'] = null;
        }

        return response()->json($result);
    }


}
