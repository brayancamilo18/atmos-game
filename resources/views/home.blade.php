<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atmos Jump</title>
    <link rel="stylesheet" href="{{ asset('home.css') }}">
</head>

<body>
    <main class="home-shell">
        <section class="hero-card">
            <p class="eyebrow">Arcade vertical</p>
            <h1>Atmos Jump</h1>
            <p class="lead">
                Sube por las capas de la atmósfera, esquiva obstáculos y guarda tu mejor récord
                tanto si juegas con cuenta como si juegas como invitado.
            </p>

            <div class="home-grid">
                <div class="home-box">
                    <h2>Jugar como invitado</h2>
                    <p>Introduce un nombre para registrar tu puntuación.</p>

                    <form action="{{ route('guest.play') }}" method="POST" class="guest-form">
                        @csrf

                        <input
                            type="text"
                            name="guest_name"
                            placeholder="Tu nombre"
                            maxlength="20"
                            value="{{ old('guest_name', session('guest_name')) }}"
                            required>

                        @error('guest_name')
                        <div class="form-error">{{ $message }}</div>
                        @enderror

                        <button type="submit" class="btn btn-primary">Entrar a jugar</button>
                    </form>
                </div>

                <div class="home-box">
                    <h2>Jugar con cuenta</h2>
                    <p>Crea tu cuenta o entra para guardar tu récord con tu usuario.</p>

                    <div class="actions">
                        @guest
                        <a class="btn btn-secondary" href="{{ route('login') }}">Iniciar sesión</a>
                        <a class="btn btn-primary" href="{{ route('register') }}">Registrarse</a>
                        @endguest

                        @auth
                        <a class="btn btn-primary" href="{{ route('game') }}">Ir al juego</a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="text-link button-link" type="submit">Cerrar sesión</button>
                        </form>
                        @endauth
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>

</html>