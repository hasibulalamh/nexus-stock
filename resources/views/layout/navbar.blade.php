<nav class="navbar navbar-expand navbar-light navbar-bg app-navbar">
    <a class="sidebar-toggle js-sidebar-toggle" aria-label="Toggle sidebar">
        <i class="hamburger align-self-center"></i>
    </a>

    {{-- search: goes to the users list --}}
    @can('users.view')
        <form class="app-search d-none d-md-flex" method="GET" action="{{ route('admin.users.list') }}" role="search">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search users..." aria-label="Search users">
            <kbd>/</kbd>
        </form>
    @endcan

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
                <button type="button" class="app-icon-btn" id="fullscreenBtn" title="Fullscreen" aria-label="Toggle fullscreen">
                    <i class="fas fa-expand"></i>
                </button>
            </li>

            {{-- theme toggle --}}
            <li class="nav-item">
                <button type="button" class="app-icon-btn" id="themeToggle" title="Toggle dark mode" aria-label="Toggle dark mode">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
            </li>

            {{-- notifications (real data comes later) --}}
            <li class="nav-item dropdown">
                <a class="app-icon-btn" href="#" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end app-dropdown app-dropdown--wide p-0">
                    <div class="app-dropdown-head">Notifications</div>
                    <div class="text-center text-muted py-4 small">
                        <i class="far fa-bell-slash d-block mb-2 fs-4"></i>
                        No new notifications
                    </div>
                </div>
            </li>

            {{-- user --}}
            <li class="nav-item dropdown ms-2">
                <a class="app-user dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                    <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->full_name }}">
                    <span class="d-none d-sm-flex flex-column lh-1 text-start">
                        <span class="app-user-name">{{ auth()->user()->full_name }}</span>
                        <span class="app-user-role">{{ auth()->user()->getRoleNames()->first() ?? 'No role' }}</span>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end app-dropdown">
                    <div class="app-dropdown-user">
                        <span class="name">{{ auth()->user()->full_name }}</span>
                        <span class="mail">{{ auth()->user()->email }}</span>
                    </div>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt"></i> Log out
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
        icon.className = root.getAttribute('data-theme') === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
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

    // "/" focuses the search box (only if it exists on this page)
    document.addEventListener('keydown', function (e) {
        var tag = (e.target.tagName || '').toLowerCase();
        var search = document.querySelector('.app-search input');
        if (e.key === '/' && search && tag !== 'input' && tag !== 'textarea' && tag !== 'select') {
            e.preventDefault();
            search.focus();
        }
    });
})();
</script>
