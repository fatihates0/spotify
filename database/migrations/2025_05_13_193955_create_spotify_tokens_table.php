<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpotifyTokensTable extends Migration
{
    public function up()
    {
        Schema::create('spotify_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('uniq_id');
            $table->string('access_token');
            $table->string('refresh_token');
            $table->timestamp('access_token_expires_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('spotify_tokens');
    }
}
