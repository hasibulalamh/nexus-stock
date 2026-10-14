<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ config('app.name', 'Nexus Stock') }}</title>

    <script>
        (function () {
            var t = null;
            try { t = localStorage.getItem('theme'); } catch (e) {}
            if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    <link rel="shortcut icon" href="{{ asset('assets/img/icons/icon-48x48.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/nexus.css') }}" rel="stylesheet">
</head>
<body class="nx-auth">

    <button type="button" class="app-icon-btn nx-auth-theme" id="themeToggle"
            title="Toggle dark mode" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="themeIcon"></i>
    </button>

    <main class="nx-auth-card">
        <div class="nx-auth-brand">
            <span class="ns-logo"><i class="fas fa-cubes"></i></span>
            <h1>{{ config('app.name', 'Nexus Stock') }}</h1>
            <p>Inventory, warehouse and branch management</p>
        </div>

        <div class="d-grid gap-2">
            @auth
                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                    <i class="fas fa-gauge-high"></i> Go to dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">
                    <i class="fas fa-right-to-bracket"></i> Sign in
                </a>
            @endauth
        </div>

        <p class="nx-auth-foot">
            <i class="fas fa-shield-halved"></i> Authorised users only
        </p>
    </main>

    <script src="{{ asset('assets/js/nexus.js') }}"></script>
    <script>
        (function () {
            var root = document.documentElement;
            var icon = document.getElementById('themeIcon');
            function paint() { icon.className = root.getAttribute('data-theme') === 'dark' ? 'fas fa-sun' : 'fas fa-moon'; }
            paint();
            document.getElementById('themeToggle').addEventListener('click', function () {
                var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                try { localStorage.setItem('theme', next); } catch (e) {}
                paint();
            });
        })();
    </script>
</body>
</html>
