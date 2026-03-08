<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('game')->default('atmos-jump');
            $table->unsignedInteger('height');
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('player_name', 40);
            $table->string('client_uuid', 100)->nullable();
            $table->string('game_version', 20)->default('1.0.0');
            $table->string('platform', 20)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index(['game', 'height']);
            $table->index(['user_id', 'game']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
