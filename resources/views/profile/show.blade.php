<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mi perfil - Atmos Jump</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Reutilizamos el fondo/tema del juego -->
    <link rel="stylesheet" href="{{ asset('style.css') }}" />
</head>

<body class="game-bg">
    <header class="py-3">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <a href="{{ route('home') }}" class="text-decoration-none text-white d-flex align-items-center gap-2">
                    <span class="brand-dot"></span>
                    <span class="fw-bold">Atmos Jump</span>
                </a>

                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary" href="{{ route('game') }}">
                        <i class="bi bi-controller me-1"></i> Ir al juego
                    </a>
                    <a class="btn btn-outline-light" href="">
                        <i class="bi bi-gear me-1"></i> Configuración
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container pb-4">
        <div class="row g-4">
            <div class="col-12">
                <div class="glass-panel p-3 p-md-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h1 class="h3 mb-1">Mi perfil</h1>
                            <div class="text-white-50 small">
                                Usuario: <span class="text-white">{{ $user->name }}</span> · Juego: <span
                                    class="text-white">{{ $game }}</span>
                            </div>
                        </div>

                        <span class="badge text-bg-dark border border-white border-opacity-10 px-3 py-2">
                            <i class="bi bi-person-check me-1"></i> Cuenta
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="col-12 col-lg-4">
                <div class="glass-panel p-3 p-md-4 h-100">
                    <h2 class="h5 mb-3"><i class="bi bi-graph-up me-1"></i> Mis puntos</h2>

                    <div class="stat-card mb-3">
                        <div class="stat-label">Mejor altura</div>
                        <div class="stat-value">
                            {{ (int) ($best?->height ?? 0) }} m
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Mejor score</div>
                        <div class="stat-value">
                            {{ (int) ($best?->score ?? 0) }}
                        </div>
                    </div>

                    <p class="small text-white-50 mt-3 mb-0">
                        *Se basa en tu mejor récord guardado online.
                    </p>
                </div>
            </div>

            <!-- Matches / Games -->
            <div class="col-12 col-lg-8">
                <div class="glass-panel p-3 p-md-4 h-100">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h2 class="h5 mb-0"><i class="bi bi-clock-history me-1"></i> Mis partidas</h2>
                        <span class="text-white-50 small">
                            Últimas {{ $recent->count() }} (máx. 20)
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0"
                            style="--bs-table-bg: transparent; --bs-table-border-color: rgba(255,255,255,0.08);">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th class="text-end">Altura</th>
                                    <th class="text-end">Score</th>
                                    <th class="text-end d-none d-md-table-cell">Duración</th>
                                    <th class="d-none d-md-table-cell">Plataforma</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recent as $row)
                                    <tr>
                                        <td class="text-white-75">
                                            {{ optional($row->created_at)->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="text-end fw-semibold">{{ (int) $row->height }} m</td>
                                        <td class="text-end fw-semibold">{{ (int) $row->score }}</td>
                                        <td class="text-end d-none d-md-table-cell text-white-75">
                                            @php
                                                $ms = (int) ($row->duration_ms ?? 0);
                                                $sec = (int) floor($ms / 1000);
                                            @endphp
                                            {{ $sec > 0 ? $sec . 's' : '—' }}
                                        </td>
                                        <td class="d-none d-md-table-cell text-white-75">
                                            {{ $row->platform ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-white-50 py-4">
                                            Aún no hay partidas guardadas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <p class="small text-white-50 mt-3 mb-0">
                        Nota: por ahora se listan los registros guardados en la tabla de scores.
                    </p>
                </div>
            </div>

            <!-- Achievements -->
            <div class="col-12">
                <div class="glass-panel p-3 p-md-4">
                    <h2 class="h5 mb-2"><i class="bi bi-award me-1"></i> Logros</h2>
                    <div class="leaderboard-empty">
                        Coming soon
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>

</html>