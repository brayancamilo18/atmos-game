<?php

namespace App\Http\Controllers;

use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $game = $request->query('game', 'atmos-jump');

        // Best record
        $best = Score::query()
            ->where('user_id', $user->id)
            ->where('game', $game)
            ->orderByDesc('height')
            ->first();

        // "Partidas": historial de registros (últimos 20)
        $recent = Score::query()
            ->where('user_id', $user->id)
            ->where('game', $game)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('profile.show', [
            'user' => $user,
            'game' => $game,
            'best' => $best,
            'recent' => $recent,
        ]);
    }
}