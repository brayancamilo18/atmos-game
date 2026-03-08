<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuestPlayController extends Controller
{
    public function play(Request $request)
    {
        $playerName = Auth::check()
            ? Auth::user()->name
            : $request->session()->get('guest_name', 'Invitado');

        $isAuthenticated = Auth::check();
        $isGuest = !$isAuthenticated;

        return view('game', [
            'playerName' => $playerName,
            'isAuthenticated' => $isAuthenticated,
            'isGuest' => $isGuest,
        ]);
    }

    public function storeGuest(Request $request)
    {
        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'min:2', 'max:20'],
        ]);

        $request->session()->put('guest_name', trim($validated['guest_name']));

        return redirect()->route('game');
    }
}
