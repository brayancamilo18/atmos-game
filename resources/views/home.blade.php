<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atmos Jump</title>

    <!-- Bootstrap 5 (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Optional: Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Your theme tweaks -->
    <link rel="stylesheet" href="{{ asset('home.css') }}">
</head>

<body class="home-bg">
    <nav class="navbar navbar-expand-lg navbar-dark py-3">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('home') }}">
                <span class="brand-dot"></span>
                Atmos Jump
            </a>

            <div class="d-flex align-items-center gap-2">
                @guest
                    <a class="btn btn-outline-light btn-sm" href="{{ route('login') }}">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión
                    </a>
                    <a class="btn btn-primary btn-sm" href="{{ route('register') }}">
                        <i class="bi bi-person-plus me-1"></i> Registrarse
                    </a>
                @endguest

                @auth
                    <a class="btn btn-primary btn-sm" href="{{ route('game') }}">
                        <i class="bi bi-controller me-1"></i> Ir al juego
                    </a>

                    <!-- NUEVO: Mi perfil -->
                    <a class="btn btn-outline-light btn-sm" href="{{ route('my.profile') }}">
                        <i class="bi bi-person-circle me-1"></i> Mi perfil
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button class="btn btn-outline-light btn-sm" type="submit">
                            <i class="bi bi-box-arrow-right me-1"></i> Cerrar sesión
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </nav>

    <main class="container pb-5">
        <!-- Hero -->
        <section class="hero-card p-4 p-md-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <p class="text-uppercase small text-info-emphasis mb-2 fw-semibold letter-space">
                        Arcade vertical
                    </p>
                    <h1 class="display-5 fw-bold mb-3">
                        Sube, esquiva y rompe tu récord
                    </h1>
                    <p class="lead mb-0 text-white-75">
                        Sube por las capas de la atmósfera, esquiva obstáculos y guarda tu mejor récord
                        tanto si juegas con cuenta como si juegas como invitado.
                    </p>
                </div>

                <div class="col-lg-4">
                    <div class="d-grid gap-2">
                        <a class="btn btn-primary btn-lg" href="{{ route('game') }}">
                            <i class="bi bi-play-fill me-1"></i> Jugar ahora
                        </a>
                        <a class="btn btn-outline-light btn-lg" href="#modes">
                            <i class="bi bi-trophy me-1"></i> Ver modos
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Two cards -->
        <section id="modes" class="row g-4">
            <!-- Guest -->
            <div class="col-lg-6">
                <div class="card glass-card h-100">
                    <div class="card-body p-4 p-md-4">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="icon-badge">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <h2 class="h4 mb-1">Jugar como invitado</h2>
                                <p class="text-white-75 mb-0">
                                    Elige un nombre y guarda tu récord online como invitado.
                                </p>
                            </div>
                        </div>

                        <form action="{{ route('guest.play') }}" method="POST" class="mt-3">
                            @csrf

                            <label for="guest_name" class="form-label text-white-75">Nombre</label>
                            <input id="guest_name" type="text" name="guest_name"
                                class="form-control form-control-lg @error('guest_name') is-invalid @enderror"
                                placeholder="Tu nombre" maxlength="20"
                                value="{{ old('guest_name', session('guest_name')) }}" required>

                            @error('guest_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="d-grid mt-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-controller me-1"></i> Entrar a jugar
                                </button>
                            </div>

                            <p class="small text-white-50 mt-3 mb-0">
                                Tip: si creas una cuenta podrás guardar tu récord asociado a tu usuario.
                            </p>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Account -->
            <div class="col-lg-6">
                <div class="card glass-card h-100">
                    <div class="card-body p-4 p-md-4">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="icon-badge">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <div>
                                <h2 class="h4 mb-1">Jugar con cuenta</h2>
                                <p class="text-white-75 mb-0">
                                    Regístrate o inicia sesión para guardar tu récord con tu usuario.
                                </p>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-3">
                            @guest
                                <a class="btn btn-outline-light btn-lg" href="{{ route('login') }}">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión
                                </a>
                                <a class="btn btn-primary btn-lg" href="{{ route('register') }}">
                                    <i class="bi bi-person-plus me-1"></i> Registrarse
                                </a>
                            @endguest

                            @auth
                                <a class="btn btn-primary btn-lg" href="{{ route('game') }}">
                                    <i class="bi bi-controller me-1"></i> Ir al juego
                                </a>

                                <!-- NUEVO: Mi perfil -->
                                <a class="btn btn-outline-light btn-lg" href="{{ route('my.profile') }}">
                                    <i class="bi bi-person-circle me-1"></i> Mi perfil
                                </a>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="btn btn-outline-light btn-lg w-100" type="submit">
                                        <i class="bi bi-box-arrow-right me-1"></i> Cerrar sesión
                                    </button>
                                </form>
                            @endauth
                        </div>

                        <hr class="border-white border-opacity-10 my-4">

                        <div class="row g-3">
                            <div class="col-sm-4">
                                <div class="mini-feature">
                                    <div class="mini-feature-title">Ranking</div>
                                    <div class="mini-feature-text">Top 7 online</div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="mini-feature">
                                    <div class="mini-feature-title">Invitado</div>
                                    <div class="mini-feature-text">Con nombre</div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="mini-feature">
                                    <div class="mini-feature-title">Cuenta</div>
                                    <div class="mini-feature-text">Mi perfil</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>

        <footer class="pt-4 mt-4 text-center text-white-50 small">
            Hecho con Laravel · Atmos Jump
        </footer>
    </main>

    <!-- Bootstrap JS (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>

</html>