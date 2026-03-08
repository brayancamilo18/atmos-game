<?php

use App\Http\Controllers\ScoreController;
use App\Http\Controllers\GuestPlayController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/play', [GuestPlayController::class, 'play'])->name('game');
Route::post('/play/guest', [GuestPlayController::class, 'storeGuest'])->name('guest.play');

Route::post('/scores', [ScoreController::class, 'store'])->middleware('throttle:20,1');
Route::get('/my-scores', [ScoreController::class, 'me']);

require __DIR__.'/auth.php';

