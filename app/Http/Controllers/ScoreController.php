<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScoreController extends Controller
{
    public function leaderboard(Request $request)
    {
        $game = $request->query('game', 'atmos-jump');
        $limit = min((int) $request->query('limit', 10), 50);

        $scores = Score::query()
            ->select([
                DB::raw("COALESCE(CAST(user_id AS CHAR), CONCAT('guest:', player_name)) as player_key"),
                'player_name',
                DB::raw('MAX(height) as best_height'),
                DB::raw('MAX(score) as best_score'),
            ])
            ->where('game', $game)
            ->groupBy(
                DB::raw("COALESCE(CAST(user_id AS CHAR), CONCAT('guest:', player_name))"),
                'player_name'
            )
            ->orderByDesc('best_height')
            ->limit($limit)
            ->get();

        $leaderboard = $scores->values()->map(function ($score, $index) {
            return [
                'rank' => $index + 1,
                'player_name' => $score->player_name ?: 'Jugador',
                'best_height' => (int) $score->best_height,
                'score' => (int) $score->best_score,
            ];
        });

        return response()->json([
            'leaderboard' => $leaderboard,
        ]);
    }

    public function me(Request $request)
    {
        $game = $request->query('game', 'atmos-jump');

        if (Auth::check()) {
            $best = Score::query()
                ->where('user_id', Auth::id())
                ->where('game', $game)
                ->orderByDesc('height')
                ->first();
        } else {
            $guestName = $request->session()->get('guest_name');

            $best = $guestName
                ? Score::query()
                ->whereNull('user_id')
                ->where('player_name', $guestName)
                ->where('game', $game)
                ->orderByDesc('height')
                ->first()
                : null;
        }

        return response()->json([
            'best_height' => $best?->height ?? 0,
            'score' => $best?->score ?? 0,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'game' => ['required', 'string', 'max:50'],
            'height' => ['required', 'integer', 'min:0', 'max:1000000'],
            'score' => ['required', 'integer', 'min:0', 'max:1000000'],
            'duration_ms' => ['nullable', 'integer', 'min:0', 'max:86400000'],
            'player_name' => ['nullable', 'string', 'min:2', 'max:20'],
            'client_uuid' => ['nullable', 'string', 'max:120'],
            'game_version' => ['nullable', 'string', 'max:40'],
            'platform' => ['nullable', 'string', 'max:30'],
        ]);

        $user = $request->user();
        $sessionGuestName = trim((string) $request->session()->get('guest_name', ''));
        $requestPlayerName = trim((string) ($validated['player_name'] ?? ''));

        $playerName = $user?->name ?: ($sessionGuestName !== '' ? $sessionGuestName : $requestPlayerName);

        if ($playerName === '') {
            return response()->json([
                'message' => 'Debes indicar un nombre antes de jugar.',
            ], 422);
        }

        $newHeight = (int) $validated['height'];
        $newScore = (int) $validated['score'];

        if ($user) {
            $record = Score::query()->firstOrNew([
                'user_id' => $user->id,
                'game' => $validated['game'],
            ]);
        } else {
            $record = Score::query()->firstOrNew([
                'user_id' => null,
                'game' => $validated['game'],
                'player_name' => $playerName,
            ]);
        }

        $isNew = !$record->exists;
        $currentBest = $isNew ? 0 : (int) $record->height;
        $shouldSave = $isNew || $newHeight > $currentBest;

        if ($shouldSave) {
            $record->user_id = $user?->id;
            $record->game = $validated['game'];
            $record->player_name = $playerName;
            $record->height = $newHeight;
            $record->score = $newScore;
            $record->duration_ms = $validated['duration_ms'] ?? null;
            $record->client_uuid = $validated['client_uuid'] ?? null;
            $record->game_version = $validated['game_version'] ?? null;
            $record->platform = $validated['platform'] ?? null;
            $record->save();
        }

        return response()->json([
            'saved' => $shouldSave,
            'updated' => !$isNew && $shouldSave,
            'personal_best' => $shouldSave ? $newHeight : $currentBest,
            'player_name' => $playerName,
            'is_guest' => !$user,
            'message' => $shouldSave
                ? ($isNew ? 'Récord guardado.' : 'Nuevo récord personal guardado.')
                : 'La puntuación no supera el récord actual.',
        ]);
    }
}
