<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sign in - Nexus Stock</title>

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
            <h1>Nexus Stock</h1>
            <p>Sign in to your account</p>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}" id="loginForm" novalidate>
            @csrf

            <div class="mb-3">
                <label class="form-label required" for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="you@company.com" autocomplete="username" required autofocus>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label required" for="password">Password</label>
                <div class="nx-field">
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Your password" autocomplete="current-password" required>
                    <button type="button" class="nx-field-action" id="togglePassword" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="nx-auth-caps" id="capsWarn"><i class="fas fa-arrow-up"></i> Caps Lock is on</div>
            </div>

            <div class="form-check nx-auth-remember">
                <input class="form-check-input nx-check-input" type="checkbox" id="remember" name="remember"
                       {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">Keep me signed in</label>
            </div>

            <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                <i class="fas fa-arrow-right-to-bracket"></i> Sign in
            </button>
        </form>

        <p class="nx-auth-foot"><i class="fas fa-shield-halved"></i> Secure area. Sign-ins are logged.</p>
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

            var input = document.getElementById('password');
            var eye = document.getElementById('togglePassword');
            eye.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                eye.querySelector('i').className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
                eye.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });

            var warn = document.getElementById('capsWarn');
            function caps(e) {
                var on = !!(e.getModifierState && e.getModifierState('CapsLock'));
                warn.classList.toggle('is-visible', on);
            }
            input.addEventListener('keyup', caps);
            input.addEventListener('keydown', caps);
            input.addEventListener('blur', function () { warn.classList.remove('is-visible'); });
        })();
    </script>
</body>
</html>
