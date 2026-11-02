<nav id="sidebar" class="sidebar js-sidebar ns-sidebar">
    <div class="sidebar-content js-simplebar">

        <a class="ns-brand" href="{{ route('admin.dashboard') }}">
            <span class="ns-logo"><i class="fas fa-cubes"></i></span>
            <span class="ns-brand-text">
                <b>Nexus Stock</b>
                <small>Inventory</small>
            </span>
        </a>

        {{-- ===== main ===== --}}
        @can('dashboard.view')
            <span class="ns-group">Main</span>
            <ul class="ns-nav">
                <li>
                    <a class="ns-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                       href="{{ route('admin.dashboard') }}">
                        <span class="ns-ico"><i class="fas fa-gauge-high"></i></span>
                        <span>Dashboard</span><i class="ns-dot"></i>
                    </a>
                </li>
            </ul>
        @endcan

        {{-- ===== administration ===== --}}
        @canany(['roles.view', 'permissions.view', 'users.view', 'departments.view', 'designations.view', 'locations.view', 'areas.view'])
            <span class="ns-group">Administration</span>
            <ul class="ns-nav">
                @can('users.view')
                    <li>
                        <a class="ns-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                           href="{{ route('admin.users.list') }}">
                            <span class="ns-ico"><i class="fas fa-users"></i></span>
                            <span>Users</span><i class="ns-dot"></i>
                        </a>
                    </li>
                @endcan
                @can('roles.view')
                    <li>
                        <a class="ns-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"
                           href="{{ route('admin.roles.list') }}">
                            <span class="ns-ico"><i class="fas fa-user-shield"></i></span>
                            <span>Roles</span><i class="ns-dot"></i>
                        </a>
                    </li>
                    <li>
                        <a class="ns-link {{ request()->routeIs('admin.activity-log') ? 'active' : '' }}"
                           href="{{ route('admin.activity-log') }}">
                            <span class="ns-ico"><i class="fas fa-clipboard-list"></i></span>
                            <span>Activity log</span><i class="ns-dot"></i>
                        </a>
                    </li>
                @endcan
                @can('permissions.view')
                    <li>
                        <a class="ns-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}"
                           href="{{ route('admin.permissions.list') }}">
                            <span class="ns-ico"><i class="fas fa-key"></i></span>
                            <span>Permissions</span><i class="ns-dot"></i>
                        </a>
                    </li>
                @endcan
                @can('departments.view')
                    <li>
                        <a class="ns-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}"
                           href="{{ route('admin.departments.list') }}">
                            <span class="ns-ico"><i class="fas fa-sitemap"></i></span>
                            <span>Departments</span><i class="ns-dot"></i>
                        </a>
                    </li>
                @endcan
                @can('designations.view')
                    <li>
                        <a class="ns-link {{ request()->routeIs('admin.designations.*') ? 'active' : '' }}"
                           href="{{ route('admin.designations.list') }}">
                            <span class="ns-ico"><i class="fas fa-id-badge"></i></span>
                            <span>Designations</span><i class="ns-dot"></i>
                        </a>
                    </li>
                @endcan
                @can('locations.view')
                    <li>
                        <a class="ns-link {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}"
                           href="{{ route('admin.locations.list') }}">
                            <span class="ns-ico"><i class="fas fa-map-location-dot"></i></span>
                            <span>Locations</span><i class="ns-dot"></i>
                        </a>
                    </li>
                @endcan
                @can('areas.view')
                    <li>
                        <a class="ns-link {{ request()->routeIs('admin.areas.*') ? 'active' : '' }}" href="{{ route('admin.areas.list') }}">
                            <span class="ns-ico"><i class="fas fa-map-pin"></i></span>
                            <span>Areas</span><i class="ns-dot"></i>
                        </a>
                    </li>
                @endcan
            </ul>
        @endcanany

        {{-- ===== inventory ===== --}}
        @canany(['products.view', 'categories.view', 'brands.view'])
            <span class="ns-group">Inventory</span>
            <ul class="ns-nav">
                @can('products.view')
                    <li>
                        <a class="ns-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"
                           href="{{ route('admin.products.list') }}">
                            <span class="ns-ico"><i class="fas fa-box"></i></span>
                            <span>Products</span><i class="ns-dot"></i>
                        </a>
                    </li>
                @endcan
                @can('categories.view')
                    <li>
                        <a class="ns-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                           href="{{ route('admin.categories.list') }}">
                            <span class="ns-ico"><i class="fas fa-folder"></i></span>
                            <span>Categories</span><i class="ns-dot"></i>
                        </a>
                    </li>
                @endcan
                @can('brands.view')
                    <li>
                        <a class="ns-link {{ request()->routeIs('admin.brands.*') ? 'active' : '' }}"
                           href="{{ route('admin.brands.list') }}">
                            <span class="ns-ico"><i class="fas fa-box-open"></i></span>
                            <span>Brands</span><i class="ns-dot"></i>
                        </a>
                    </li>
                @endcan
            </ul>
        @endcanany

        @canany(['stock.view', 'stock.movements.view', 'stock.alerts.view', 'stock.opening'])
            <span class="ns-group">Stock</span>
            <ul class="ns-nav">
                @can('stock.view')
                    <li><a class="ns-link {{ request()->routeIs('admin.stock.levels') ? 'active' : '' }}" href="{{ route('admin.stock.levels') }}">
                        <span class="ns-ico"><i class="fas fa-layer-group"></i></span><span>Stock levels</span><i class="ns-dot"></i>
                    </a></li>
                @endcan
                @can('stock.movements.view')
                    <li><a class="ns-link {{ request()->routeIs('admin.stock.movements') ? 'active' : '' }}" href="{{ route('admin.stock.movements') }}">
                        <span class="ns-ico"><i class="fas fa-right-left"></i></span><span>Movements</span><i class="ns-dot"></i>
                    </a></li>
                @endcan
                @can('stock.alerts.view')
                    <li><a class="ns-link {{ request()->routeIs('admin.stock.alerts') ? 'active' : '' }}" href="{{ route('admin.stock.alerts') }}">
                        <span class="ns-ico"><i class="fas fa-triangle-exclamation"></i></span><span>Alerts</span>
                        @php($openStockAlerts = \App\Models\StockAlert::query()->where('status', 'open')->when(!auth()->user()->hasAccessAll() && !auth()->user()->can('stock.alerts.view_all'), function ($q) {
                            $q->where(function ($scope) {
                                $scope->whereIn('branch_id', auth()->user()->accessibleBranchIds() ?: [])
                                    ->orWhereIn('warehouse_id', auth()->user()->accessibleWarehouseIds() ?: []);
                            });
                        })->count())
                        @if($openStockAlerts)<span class="badge bg-danger ms-auto">{{ $openStockAlerts }}</span>@endif
                        <i class="ns-dot"></i>
                    </a></li>
                @endcan
                @can('stock.opening')
                    <li><a class="ns-link {{ request()->routeIs('admin.stock.opening') ? 'active' : '' }}" href="{{ route('admin.stock.opening') }}">
                        <span class="ns-ico"><i class="fas fa-boxes-stacked"></i></span><span>Opening stock</span><i class="ns-dot"></i>
                    </a></li>
                @endcan
            </ul>
        @endcanany

        {{-- ===== modules ===== --}}
        @canany(['warehouses.view', 'branches.view'])
            <span class="ns-group">Modules</span>
            <ul class="ns-nav">
                @can('warehouses.view')
                    <li>
                        <a class="ns-link {{ request()->routeIs('admin.warehouse.*') ? 'active' : '' }}"
                           href="{{ route('admin.warehouse.list') }}">
                            <span class="ns-ico"><i class="fas fa-warehouse"></i></span>
                            <span>Warehouses</span><i class="ns-dot"></i>
                        </a>
                    </li>
                @endcan
                @can('branches.view')
                    <li>
                        <a class="ns-link {{ request()->routeIs('admin.branch.*') ? 'active' : '' }}"
                           href="{{ route('admin.branch.list') }}">
                            <span class="ns-ico"><i class="fas fa-store"></i></span>
                            <span>Branches</span><i class="ns-dot"></i>
                        </a>
                    </li>
                @endcan
            </ul>
        @endcanany

        {{-- ===== footer card ===== --}}
        <div class="ns-foot">
            <span class="role">{{ auth()->user()->getRoleNames()->first() ?? 'No role' }}</span>
            <div class="name">{{ auth()->user()->full_name }}</div>
            <div class="ver">Nexus Stock v1.0</div>
        </div>
    </div>
</nav>
