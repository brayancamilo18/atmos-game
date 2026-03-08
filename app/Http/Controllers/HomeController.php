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
        $game = $request->query('game', 'atmos-jump');

        // Si NO está logueado, no mostramos stats (solo dejamos la home normal)
        if (!Auth::check()) {
            return view('home', [
                'topNumber' => null,
                'partidasJugadas' => 0,
                'mejorPartida' => null,
            ]);
        }

        $userId = Auth::id();

        $partidasJugadas = Score::query()
            ->where('game', $game)
            ->where('user_id', $userId)
            ->count();
        

        $mejorPartida = Score::query()
            ->where('game', $game)
            ->where('user_id', $userId)
            ->orderByDesc('height')
            ->first();

        $myBestHeight = (int) ($mejorPartida?->height ?? 0);

        $topNumber = null;
        if ($myBestHeight > 0) {
            // rank = 1 + cantidad de players con best_height mayor al mío
            $topNumber = DB::query()
                ->fromSub(function ($q) use ($game) {
                    $q->from('scores')
                        ->selectRaw("COALESCE(CAST(user_id AS CHAR), CONCAT('guest:', player_name)) as player_key")
                        ->selectRaw("MAX(height) as best_height")
                        ->where('game', $game)
                        ->groupBy('player_key');
                }, 'lb')
                ->where('best_height', '>', $myBestHeight)
                ->count() + 1;
        }

        return view('home', [
            'topNumber' => $topNumber,
            'partidasJugadas' => $partidasJugadas,
            'mejorPartida' => $mejorPartida,
        ]);
    }
}
