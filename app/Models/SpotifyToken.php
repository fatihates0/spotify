<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpotifyToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
    ];

    // Süre bitmişse yeni access token almak için yardımcı fonksiyon
    public function isAccessTokenExpired()
    {
        return now()->greaterThan($this->access_token_expires_at);
    }
}
