<style>
    :root {
  --nav-bg: #ffffff;
  --nav-fg: #1e293b;
  --nav-muted: #64748b;
  --nav-chip: #f1f5f9;
  --nav-border: #e2e8f0;
  --accent-1: #20c997;
  --accent-2: #6f42c1;
  --accent-3: #f43f5e;
  --page-bg: #f5f7fb;
  --card-bg: #ffffff;
  --page-fg: #1e293b;
}

[data-theme="dark"] {
  --nav-bg: #111827;
  --nav-fg: #e5e7eb;
  --nav-muted: #94a3b8;
  --nav-chip: #1f2937;
  --nav-border: #2b3547;
  --page-bg: #0b1220;
  --card-bg: #151e2e;
  --page-fg: #e5e7eb;
}

/* ---------- navbar ---------- */
.app-navbar {
  background: var(--nav-bg) !important;
  color: var(--nav-fg);
  position: relative;
  border-bottom: 1px solid var(--nav-border);
  box-shadow: 0 6px 24px -12px rgba(111, 66, 193, .45);
}
.app-navbar::before {
  content: "";
  position: absolute; inset: 0 0 auto 0; height: 3px;
  background: linear-gradient(90deg, var(--accent-1), var(--accent-2), var(--accent-3), var(--accent-1));
  background-size: 300% 100%;
  animation: navflow 8s linear infinite;
}
@keyframes navflow { to { background-position: 300% 0; } }

.app-navbar .sidebar-toggle .hamburger,
.app-navbar .sidebar-toggle .hamburger::before,
.app-navbar .sidebar-toggle .hamburger::after { background: var(--nav-fg); }

.app-search {
  position: relative; align-items: center; gap: .6rem;
  background: var(--nav-chip); border: 1px solid var(--nav-border);
  border-radius: 999px; padding: .35rem .9rem; min-width: 280px; margin-left: .5rem;
  transition: box-shadow .2s, border-color .2s;
}
.app-search:focus-within {
  border-color: var(--accent-2);
  box-shadow: 0 0 0 4px rgba(111, 66, 193, .18);
}
.app-search i { color: var(--nav-muted); font-size: .85rem; }
.app-search input {
  flex: 1; border: 0; outline: 0; background: transparent; color: var(--nav-fg); font-size: .9rem;
}
.app-search kbd {
  background: var(--card-bg); color: var(--nav-muted); border: 1px solid var(--nav-border);
  border-radius: 6px; padding: 0 .4rem; font-size: .72rem; box-shadow: none;
}

.app-clock {
  display: flex; align-items: center; gap: .5rem;
  padding: .35rem .8rem; border-radius: 999px;
  background: linear-gradient(135deg, rgba(32,201,151,.15), rgba(111,66,193,.15));
  color: var(--nav-fg); font-weight: 600; font-size: .85rem;
  font-variant-numeric: tabular-nums;
}

.app-icon-btn {
  width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;
  border-radius: 12px; border: 1px solid var(--nav-border);
  background: var(--nav-chip); color: var(--nav-fg); cursor: pointer;
  transition: transform .15s, background .2s, color .2s, border-color .2s;
}
.app-icon-btn:hover {
  transform: translateY(-2px) rotate(-4deg);
  background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
  border-color: transparent; color: #fff;
}

.app-user {
  display: flex; align-items: center; gap: .65rem;
  padding: .25rem .8rem .25rem .25rem; border-radius: 999px;
  background: var(--nav-chip); border: 1px solid var(--nav-border);
  color: var(--nav-fg); text-decoration: none;
}
.app-user::after { margin-left: .2rem; }
.app-user img {
  width: 36px; height: 36px; border-radius: 50%; object-fit: cover;
  padding: 2px; background: linear-gradient(135deg, var(--accent-1), var(--accent-2), var(--accent-3));
}
.app-user-name { font-weight: 700; font-size: .85rem; color: var(--nav-fg); }
.app-user-role {
  font-size: .68rem; text-transform: uppercase; letter-spacing: .08em;
  color: var(--accent-2); font-weight: 700; margin-top: 3px;
}
[data-theme="dark"] .app-user-role { color: var(--accent-1); }

.app-dropdown {
  background: var(--card-bg); border: 1px solid var(--nav-border);
  border-radius: 14px; box-shadow: 0 20px 40px -12px rgba(0,0,0,.35); padding: .4rem;
}
.app-dropdown .dropdown-item { color: var(--nav-fg); border-radius: 8px; }
.app-dropdown .dropdown-item:hover { background: var(--nav-chip); }
.app-dropdown .dropdown-divider { border-color: var(--nav-border); }
.app-dropdown-head {
  padding: .6rem .9rem; font-size: .78rem; font-weight: 700;
  letter-spacing: .04em; color: var(--nav-muted);
}

/* ---------- dark mode: page surfaces ---------- */
[data-theme="dark"] body,
[data-theme="dark"] .main,
[data-theme="dark"] .content { background: var(--page-bg) !important; color: var(--page-fg); }
[data-theme="dark"] .card { background: var(--card-bg); color: var(--page-fg); border-color: var(--nav-border); }
[data-theme="dark"] .table { color: var(--page-fg); --bs-table-bg: transparent; --bs-table-hover-bg: rgba(255,255,255,.04); border-color: var(--nav-border); }
[data-theme="dark"] .table > :not(caption) > * > * { border-bottom-color: var(--nav-border); }
[data-theme="dark"] .bg-light { background: var(--nav-chip) !important; color: var(--page-fg); }
[data-theme="dark"] .form-control,
[data-theme="dark"] .form-select { background: var(--nav-chip); color: var(--page-fg); border-color: var(--nav-border); }
[data-theme="dark"] .footer { background: var(--nav-bg); color: var(--nav-muted); }
[data-theme="dark"] .footer a { color: var(--nav-muted); }
[data-theme="dark"] .page-link { background: var(--card-bg); border-color: var(--nav-border); color: var(--page-fg); }
[data-theme="dark"] .text-dark { color: var(--page-fg) !important; }
[data-theme="dark"] .modal-content { background: var(--card-bg); color: var(--page-fg); }
</style>


<nav class="navbar navbar-expand navbar-light navbar-bg app-navbar">
    <a class="sidebar-toggle js-sidebar-toggle">
        <i class="hamburger align-self-center"></i>
    </a>

    {{-- search --}}
    <form class="app-search d-none d-md-flex" onsubmit="return false;">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search anything..." aria-label="Search">
        <kbd>/</kbd>
    </form>

    <div class="navbar-collapse collapse">
        <ul class="navbar-nav navbar-align align-items-center gap-1">

            {{-- clock --}}
            <li class="nav-item d-none d-lg-block">
                <div class="app-clock">
                    <i class="far fa-clock"></i>
                    <span id="navClock">--:--</span>
                </div>
            </li>

            {{-- fullscreen --}}
            <li class="nav-item d-none d-md-block">
                <button type="button" class="app-icon-btn" id="fullscreenBtn" title="Fullscreen">
                    <i class="fas fa-expand"></i>
                </button>
            </li>

            {{-- theme toggle --}}
            <li class="nav-item">
                <button type="button" class="app-icon-btn" id="themeToggle" title="Toggle dark mode">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
            </li>

            {{-- notifications (real data comes later) --}}
            <li class="nav-item dropdown">
                <a class="app-icon-btn" href="#" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                    <i class="fas fa-bell"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end app-dropdown p-0" style="min-width: 280px;">
                    <div class="app-dropdown-head">Notifications</div>
                    <div class="text-center text-muted py-4 small">
                        <i class="far fa-bell-slash d-block mb-2 fs-4"></i>
                        No new notifications
                    </div>
                </div>
            </li>

            {{-- user --}}
            <li class="nav-item dropdown ms-2">
                <a class="app-user dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->full_name }}">
                    <span class="d-none d-sm-flex flex-column lh-1 text-start">
                        <span class="app-user-name">{{ auth()->user()->full_name }}</span>
                        <span class="app-user-role">{{ auth()->user()->getRoleNames()->first() ?? 'No role' }}</span>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end app-dropdown">
                    <div class="app-dropdown-head">
                        {{ auth()->user()->email }}
                    </div>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Log out
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </div>
</nav>

<script>
(function () {
    // theme toggle
    var root = document.documentElement;
    var icon = document.getElementById('themeIcon');
    function paint() {
        var dark = root.getAttribute('data-theme') === 'dark';
        icon.className = dark ? 'fas fa-sun' : 'fas fa-moon';
    }
    paint();
    document.getElementById('themeToggle').addEventListener('click', function () {
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('theme', next); } catch (e) {}
        paint();
    });

    // clock
    var clock = document.getElementById('navClock');
    function tick() {
        clock.textContent = new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
    }
    tick(); setInterval(tick, 30000);

    // fullscreen
    document.getElementById('fullscreenBtn').addEventListener('click', function () {
        if (!document.fullscreenElement) document.documentElement.requestFullscreen();
        else document.exitFullscreen();
    });

    // "/" focuses search
    document.addEventListener('keydown', function (e) {
        var tag = (e.target.tagName || '').toLowerCase();
        if (e.key === '/' && tag !== 'input' && tag !== 'textarea') {
            e.preventDefault();
            document.querySelector('.app-search input').focus();
        }
    });
})();
</script>
