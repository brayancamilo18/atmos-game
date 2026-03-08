<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Atmos Jump</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}" />
</head>

<body>
    <div
        id="gameApp"
        data-game-name="atmos-jump"
        data-game-version="1.0.0"
        data-authenticated="{{ $isAuthenticated ? '1' : '0' }}"
        data-player-name="{{ $playerName }}"
        data-is-guest="{{ $isGuest ? '1' : '0' }}">
        <div class="page-shell">
            <div class="game-shell">
                <div class="topbar">
                    <h1>Atmos Jump</h1>
                    <button id="startBtn">Jugar</button>
                </div>

                <div class="hint">
                    PC: ← → o A D · Pausa: P · Reiniciar: R
                </div>

                <canvas id="game" width="420" height="720"></canvas>

                <div class="mobile-controls">
                    <button id="leftBtn" class="touch-btn">⬅</button>
                    <button id="rightBtn" class="touch-btn">➡</button>
                </div>
            </div>

            <aside class="leaderboard-panel">
                <h2>Clasificación</h2>

                <div class="leaderboard-user">
                    <div>Jugador</div>
                    <strong>{{ $playerName }}</strong>
                </div>

                <div class="leaderboard-user">
                    <div>Mi récord online</div>
                    <strong id="personalBest">0 m</strong>
                </div>

                <div id="leaderboardList" class="leaderboard-list">
                    <div class="leaderboard-empty">Cargando ranking...</div>
                </div>
            </aside>
        </div>
    </div>

    <script src="{{ asset('game.js') }}"></script>
</body>

</html>