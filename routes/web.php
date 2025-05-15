<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpotifyController;

Route::get('/spotify/login', [SpotifyController::class, 'login']);
Route::get('/spotify/logout', [SpotifyController::class, 'logout'])->name('spotify.logout');
Route::get('/spotify/callback', [SpotifyController::class, 'callback']);
Route::get('/spotify/playing', [SpotifyController::class, 'currentlyPlaying']);
Route::get('/spotify/show/{uuid?}', function ($uuid = null) {
    if (!$uuid){
        echo "Cafe bulunamadı!";
    }
    return view('spotify.show');
});
