<?php

namespace App\Http\Controllers;

use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $stats = null;
        $game = 'atmos-jump';

        if (Auth::check()) {
            $userId = Auth::id();

            // Mejor registro del usuario
            $best = Score::query()
                ->where('user_id', $userId)
                ->where('game', $game)
                ->orderByDesc('height')
                ->first();

            // "Partidas jugadas" (según registros guardados)
            $gamesPlayed = Score::query()
                ->where('user_id', $userId)
                ->where('game', $game)
                ->count();

            // Ranking total: 1 + cantidad de jugadores con mejor best_height que el tuyo
            $myBestHeight = (int) ($best?->height ?? 0);

            if ($myBestHeight > 0) {
                // Subquery: best height por player_key (usuarios y guests)
                $rank = DB::query()
                    ->fromSub(function ($q) use ($game) {
                        $q->from('scores')
                            ->selectRaw("COALESCE(CAST(user_id AS CHAR), CONCAT('guest:', player_name)) as player_key")
                            ->selectRaw("MAX(height) as best_height")
                            ->where('game', $game)
                            ->groupBy('player_key');
                    }, 'lb')
                    ->where('best_height', '>', $myBestHeight)
                    ->count() + 1;
            } else {
                $rank = null; // si no tienes récord todavía
            }

            $stats = [
                'rank' => $rank, // null si no hay score
                'games_played' => $gamesPlayed,
                'best_height' => (int) ($best?->height ?? 0),
                'best_score' => (int) ($best?->score ?? 0),
            ];
        }

        return view('home', [
            'homeStats' => $stats,
        ]);
    }
}
