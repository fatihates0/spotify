<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpotifyController;

Route::get('/spotify/login', [SpotifyController::class, 'login']);
Route::get('/spotify/callback', [SpotifyController::class, 'callback']);
Route::get('/spotify/playing', [SpotifyController::class, 'currentlyPlaying']);