<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpotifyController;

Route::get('/login', [SpotifyController::class, 'login']);
Route::get('/callback', [SpotifyController::class, 'callback']);
Route::get('/playing', [SpotifyController::class, 'currentlyPlaying']);
Route::get('/spotify/playing', [SpotifyController::class, 'currentlyPlaying']);
Route::get('/spotify/show', function () {
    return view('spotify.show');
});
