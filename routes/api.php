<?php

use App\Http\Controllers\ScoreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/leaderboard', [ScoreController::class, 'leaderboard']);

Route::middleware(['auth:sanctum', 'throttle:20,1'])->group(function () {
    Route::post('/scores', [ScoreController::class, 'store']);
    Route::get('/my-scores', [ScoreController::class, 'me']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
