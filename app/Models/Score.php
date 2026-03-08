<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    protected $fillable = [
        'user_id',
        'game',
        'height',
        'score',
        'duration_ms',
        'player_name',
        'client_uuid',
        'game_version',
        'platform',
        'ip_address',
    ];

    protected $casts = [
        'height' => 'integer',
        'score' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
