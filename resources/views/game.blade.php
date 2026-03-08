<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Atmos Jump</title>

    <!-- Bootstrap 5 (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Optional: Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Game theme -->
    <link rel="stylesheet" href="{{ asset('style.css') }}" />
</head>

<body class="game-bg">
    <div id="gameApp" data-game-name="atmos-jump" data-game-version="1.0.0"
        data-authenticated="{{ $isAuthenticated ? '1' : '0' }}" data-player-name="{{ $playerName }}"
        data-is-guest="{{ $isGuest ? '1' : '0' }}">

        <header class="py-3">
            <div class="container">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <a href="{{ route('home') }}" class="text-decoration-none text-white d-flex align-items-center gap-2">
                        <span class="brand-dot"></span>
                        <span class="fw-bold">Atmos Jump</span>
                    </a>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button id="startBtn" class="btn btn-primary">
                            <i class="bi bi-play-fill me-1"></i> Jugar
                        </button>

                        <a class="btn btn-outline-light" href="{{ route('home') }}">
                            <i class="bi bi-house-door me-1"></i> Inicio
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="container pb-4">
            <div class="row g-4 align-items-start">
                <!-- Game column -->
                <div class="col-12 col-lg-7 col-xl-6">
                    <div class="game-card p-3 p-md-4">
                        <div class="d-flex flex-column gap-2">
                            <div class="small text-white-50">
                                PC: ← → o A D · Pausa: P · Reiniciar: R
                            </div>

                            <div class="canvas-wrap">
                                <canvas id="game" width="420" height="720"></canvas>
                            </div>

                            <!-- Mobile controls (shown on small screens) -->
                            <div class="mobile-controls mt-2">
                                <button id="leftBtn" class="btn btn-outline-light touch-btn" type="button">⬅</button>
                                <button id="rightBtn" class="btn btn-outline-light touch-btn" type="button">➡</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right panel -->
                <div class="col-12 col-lg-5 col-xl-4">
                    <aside class="glass-panel p-3 p-md-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h2 class="h4 mb-0">Clasificación</h2>
                            <span class="badge text-bg-dark border border-white border-opacity-10">
                                {{ $isGuest ? 'Invitado' : 'Cuenta' }}
                            </span>
                        </div>

                        <div class="stat-card mb-3">
                            <div class="stat-label">Jugador</div>
                            <div class="stat-value">{{ $playerName }}</div>
                        </div>

                        <div class="stat-card mb-3">
                            <div class="stat-label">Mi récord online</div>
                            <div class="stat-value" id="personalBest">0 m</div>
                        </div>

                        <div class="leaderboard-list" id="leaderboardList">
                            <div class="leaderboard-empty">Cargando ranking...</div>
                        </div>

                        <p class="small text-white-50 mt-3 mb-0">
                            Tip: tu récord se guarda cuando superas tu mejor altura.
                        </p>
                    </aside>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script src="{{ asset('game.js') }}"></script>
</body>

</html>