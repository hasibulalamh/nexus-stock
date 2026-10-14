<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in - Nexus Stock</title>

    <script>
        (function () {
            var t = null;
            try { t = localStorage.getItem('theme'); } catch (e) {}
            if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --bg1: #eef2ff;
            --bg2: #f5f3ff;
            --ink: #0f172a;
            --muted: #64748b;
            --glass: rgba(255, 255, 255, .55);
            --glass-border: rgba(255, 255, 255, .85);
            --field: rgba(255, 255, 255, .7);
            --field-border: rgba(15, 23, 42, .12);
            --accent: #6366f1;
            --accent2: #14b8a6;
            --danger: #e11d48;
            --orb1: rgba(99, 102, 241, .45);
            --orb2: rgba(20, 184, 166, .40);
            --orb3: rgba(244, 114, 182, .35);
        }
        [data-theme="dark"] {
            --bg1: #070b18;
            --bg2: #0f1230;
            --ink: #f1f5f9;
            --muted: #94a3b8;
            --glass: rgba(20, 26, 55, .45);
            --glass-border: rgba(255, 255, 255, .12);
            --field: rgba(255, 255, 255, .06);
            --field-border: rgba(255, 255, 255, .14);
            --danger: #fb7185;
            --orb1: rgba(99, 102, 241, .55);
            --orb2: rgba(20, 184, 166, .35);
            --orb3: rgba(168, 85, 247, .35);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            color: var(--ink);
            min-height: 100vh;
            display: grid; place-items: center;
            padding: 24px;
            background: linear-gradient(135deg, var(--bg1), var(--bg2));
            position: relative; overflow: hidden;
        }

        /* blurred colour orbs behind the glass */
        .orb { position: fixed; border-radius: 50%; filter: blur(80px); pointer-events: none; z-index: 0; }
        .orb.o1 { width: 420px; height: 420px; background: var(--orb1); top: -120px; left: -100px; animation: float 14s ease-in-out infinite alternate; }
        .orb.o2 { width: 380px; height: 380px; background: var(--orb2); bottom: -120px; right: -80px; animation: float 17s ease-in-out infinite alternate-reverse; }
        .orb.o3 { width: 260px; height: 260px; background: var(--orb3); top: 55%; left: 55%; animation: float 12s ease-in-out infinite alternate; }
        @keyframes float { to { transform: translate(40px, -30px) scale(1.1); } }

        .theme-toggle {
            position: fixed; top: 20px; right: 20px; z-index: 5;
            width: 42px; height: 42px; border-radius: 12px; cursor: pointer;
            display: grid; place-items: center; color: var(--ink);
            background: var(--glass); border: 1px solid var(--glass-border);
            backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
            transition: transform .2s;
        }
        .theme-toggle:hover { transform: rotate(20deg); }

        .card {
            position: relative; z-index: 1;
            width: 100%; max-width: 420px;
            padding: 38px 34px 30px;
            border-radius: 24px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(24px) saturate(160%);
            -webkit-backdrop-filter: blur(24px) saturate(160%);
            box-shadow: 0 25px 60px -20px rgba(15, 23, 42, .35), inset 0 1px 0 rgba(255, 255, 255, .35);
            animation: rise .6s ease both;
        }
        @keyframes rise { from { opacity: 0; transform: translateY(18px); } }

        .brand { display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 26px; }
        .logo {
            width: 56px; height: 56px; border-radius: 16px; display: grid; place-items: center;
            font-size: 1.4rem; color: #fff; margin-bottom: 14px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            box-shadow: 0 10px 24px -8px var(--accent);
        }
        .brand h1 { font-family: 'Space Grotesk', sans-serif; font-size: 1.6rem; letter-spacing: -.01em; }
        .brand h1 span {
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .brand p { margin-top: 6px; color: var(--muted); font-size: .9rem; }

        .alert { padding: 11px 14px; border-radius: 12px; margin-bottom: 16px; font-size: .86rem; border: 1px solid; }
        .alert.error { color: var(--danger); background: rgba(225, 29, 72, .1); border-color: rgba(225, 29, 72, .35); }
        .alert.ok { color: #0d9488; background: rgba(20, 184, 166, .12); border-color: rgba(20, 184, 166, .4); }

        .field { margin-bottom: 16px; }
        .field label { display: block; margin-bottom: 6px; font-size: .8rem; font-weight: 600; color: var(--muted); }
        .control {
            display: flex; align-items: center; gap: 10px; padding: 0 14px;
            background: var(--field); border: 1px solid var(--field-border); border-radius: 12px;
            transition: border-color .2s, box-shadow .2s;
        }
        .control:focus-within { border-color: var(--accent); box-shadow: 0 0 0 4px rgba(99, 102, 241, .18); }
        .control.invalid { border-color: var(--danger); }
        .control > i { color: var(--muted); font-size: .9rem; width: 16px; text-align: center; }
        .control input {
            flex: 1; min-width: 0; height: 48px; border: 0; outline: 0; background: transparent;
            font: inherit; color: var(--ink);
        }
        .control input::placeholder { color: var(--muted); opacity: .75; }
        .eye { border: 0; background: transparent; color: var(--muted); cursor: pointer; padding: 6px; }
        .eye:hover { color: var(--accent); }
        .field-error { margin-top: 5px; font-size: .78rem; color: var(--danger); }
        .caps { display: none; margin-top: 5px; font-size: .78rem; color: #d97706; font-weight: 600; }

        .remember { display: flex; align-items: center; gap: 9px; margin: 4px 0 20px; font-size: .86rem; color: var(--muted); cursor: pointer; user-select: none; }
        .remember input { accent-color: var(--accent); width: 16px; height: 16px; }

        .btn {
            width: 100%; height: 50px; border: 0; border-radius: 12px; cursor: pointer;
            color: #fff; font: inherit; font-weight: 600; font-size: .98rem;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            box-shadow: 0 12px 26px -10px var(--accent);
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: transform .15s, box-shadow .15s, filter .15s;
        }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.06); box-shadow: 0 16px 30px -10px var(--accent); }
        .btn:active { transform: translateY(0); }
        .btn[disabled] { opacity: .75; cursor: progress; }
        .spin { display: none; width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(255, 255, 255, .45); border-top-color: #fff; animation: turn .7s linear infinite; }
        .btn.loading .spin { display: inline-block; }
        .btn.loading .arrow { display: none; }
        @keyframes turn { to { transform: rotate(360deg); } }

        .foot { margin-top: 22px; text-align: center; font-size: .78rem; color: var(--muted); }
        .foot i { color: var(--accent2); margin-right: 4px; }

        @media (max-width: 480px) { .card { padding: 30px 22px 24px; } }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }
    </style>
</head>
<body>
    <span class="orb o1"></span>
    <span class="orb o2"></span>
    <span class="orb o3"></span>

    <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="themeIcon"></i>
    </button>

    <main class="card">
        <div class="brand">
            <div class="logo"><i class="fas fa-cubes"></i></div>
            <h1>Nexus <span>Stock</span></h1>
            <p>Sign in to your account</p>
        </div>

        @if(session('status'))
            <div class="alert ok">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}" id="loginForm" novalidate>
            @csrf

            <div class="field">
                <label for="email">Email</label>
                <div class="control @error('email') invalid @enderror">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           placeholder="you@company.com" autocomplete="username" required autofocus>
                </div>
                @error('email')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="control @error('password') invalid @enderror">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password"
                           placeholder="Your password" autocomplete="current-password" required>
                    <button type="button" class="eye" id="togglePassword" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                @error('password')
                    <div class="field-error">{{ $message }}</div>
                @enderror
                <div class="caps" id="capsWarn"><i class="fas fa-arrow-up"></i> Caps Lock is on</div>
            </div>

            <label class="remember">
                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                Keep me signed in
            </label>

            <button type="submit" class="btn" id="submitBtn">
                <span class="spin"></span>
                <span class="label">Sign in</span>
                <i class="fas fa-arrow-right arrow"></i>
            </button>
        </form>

        <p class="foot"><i class="fas fa-shield-halved"></i> Secure area. Sign-ins are logged.</p>
    </main>

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
            function caps(e) { warn.style.display = e.getModifierState && e.getModifierState('CapsLock') ? 'block' : 'none'; }
            input.addEventListener('keyup', caps);
            input.addEventListener('keydown', caps);
            input.addEventListener('blur', function () { warn.style.display = 'none'; });

            document.getElementById('loginForm').addEventListener('submit', function () {
                var b = document.getElementById('submitBtn');
                b.classList.add('loading');
                b.querySelector('.label').textContent = 'Signing in...';
                setTimeout(function () { b.disabled = true; }, 0);
            });
        })();
    </script>
</body>
</html>
