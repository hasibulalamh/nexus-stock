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
                @endcan
                @can('activitylog.view')
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

        @canany(['stock.view', 'stock.movements.view', 'stock.alerts.view', 'stock.opening', 'stock.opening.approve', 'transfers.view', 'adjustments.view', 'counts.view', 'reports.view', 'stock.kpi.view'])
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
                @can('transfers.view')
                    <li><a class="ns-link {{ request()->routeIs('admin.transfers.*') ? 'active' : '' }}" href="{{ route('admin.transfers.index') }}">
                        <span class="ns-ico"><i class="fas fa-truck-arrow-right"></i></span><span>Transfers</span><i class="ns-dot"></i>
                    </a></li>
                @endcan
                @can('adjustments.view')
                    <li><a class="ns-link {{ request()->routeIs('admin.adjustments.*') ? 'active' : '' }}" href="{{ route('admin.adjustments.index') }}">
                        <span class="ns-ico"><i class="fas fa-sliders"></i></span><span>Adjustments</span><i class="ns-dot"></i>
                    </a></li>
                @endcan
                @can('counts.view')
                    <li><a class="ns-link {{ request()->routeIs('admin.counts.*') ? 'active' : '' }}" href="{{ route('admin.counts.index') }}">
                        <span class="ns-ico"><i class="fas fa-clipboard-check"></i></span><span>Stock counts</span><i class="ns-dot"></i>
                    </a></li>
                @endcan
                @can('stock.kpi.view')
                    <li><a class="ns-link {{ request()->routeIs('admin.stock.kpi') ? 'active' : '' }}" href="{{ route('admin.stock.kpi') }}">
                        <span class="ns-ico"><i class="fas fa-chart-line"></i></span><span>Stock KPIs</span><i class="ns-dot"></i>
                    </a></li>
                @endcan
                @can('reports.view')
                    <li><a class="ns-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                        <span class="ns-ico"><i class="fas fa-chart-column"></i></span><span>Reports</span><i class="ns-dot"></i>
                    </a></li>
                @endcan
                @canany(['stock.opening', 'stock.opening.approve'])
                    <li><a class="ns-link {{ request()->routeIs('admin.stock.requests.*') ? 'active' : '' }}" href="{{ route('admin.stock.requests.index') }}">
                        <span class="ns-ico"><i class="fas fa-file-circle-check"></i></span><span>Stock requests</span>
                        @can('stock.opening.approve')
                            @php($pendingStockRequests = \App\Models\OpeningStockRequest::query()->where('status', 'pending')->where(function ($scope) {
                                foreach (['branch', 'warehouse'] as $type) {
                                    $ids = $type === 'branch' ? auth()->user()->accessibleBranchIds() : auth()->user()->accessibleWarehouseIds();
                                    $scope->orWhere(fn ($q) => $q->where('location_type', $type)->when($ids !== null, fn ($q) => $q->whereIn('location_id', $ids)));
                                }
                            })->count())
                            @if($pendingStockRequests)<span class="badge bg-warning ms-auto">{{ $pendingStockRequests }}</span>@endif
                        @endcan
                        <i class="ns-dot"></i>
                    </a></li>
                @endcanany
            </ul>
        @endcanany

        {{-- ===== procurement ===== --}}
        @canany(['suppliers.view', 'purchases.view', 'receiving.view'])
            <span class="ns-group">Procurement</span>
            <ul class="ns-nav">
                @can('suppliers.view')
                    <li><a class="ns-link {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}" href="{{ route('admin.suppliers.index') }}">
                        <span class="ns-ico"><i class="fas fa-handshake"></i></span><span>Suppliers</span><i class="ns-dot"></i>
                    </a></li>
                @endcan
                @can('purchases.view')
                    <li><a class="ns-link {{ request()->routeIs('admin.purchases.*') ? 'active' : '' }}" href="{{ route('admin.purchases.index') }}">
                        <span class="ns-ico"><i class="fas fa-file-invoice"></i></span><span>Purchase orders</span><i class="ns-dot"></i>
                    </a></li>
                @endcan
                @can('receiving.view')
                    <li><a class="ns-link {{ request()->routeIs('admin.receiving.*') ? 'active' : '' }}" href="{{ route('admin.receiving.index') }}">
                        <span class="ns-ico"><i class="fas fa-truck-ramp-box"></i></span><span>Receiving</span><i class="ns-dot"></i>
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
