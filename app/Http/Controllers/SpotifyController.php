<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class SpotifyController extends Controller
{
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

        $data = $response->json();

        // Tokenları session'da tutuyoruz (test amaçlı)
        Session::put('spotify_access_token', $data['access_token']);
        Session::put('spotify_refresh_token', $data['refresh_token']);

        return redirect('/spotify/playing');
    }

    public function currentlyPlaying()
    {
        $token = Session::get('spotify_access_token');

        if (!$token) {
            return redirect('/spotify/login');
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer $token"
        ])->get('https://api.spotify.com/v1/me/player/currently-playing');

        if ($response->status() == 204) {
            return 'Şu anda çalan bir şarkı yok.';
        }

        $data = $response->json();

        if (isset($data['item'])) {
            $song = $data['item']['name'];
            $artist = $data['item']['artists'][0]['name'];
            return "🎵 Şu anda çalan şarkı: $artist - $song";
        } else {
            return 'Şarkı bilgisi alınamadı.';
        }
    }
}
