@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <h1 class="h3 nx-title">
    <small>Overview</small>
    Welcome back, {{ auth()->user()->full_name }}
    <span class="text-muted fs-6">({{ $role?->name ?? 'No role' }})</span>
  </h1>

  @canany(['users.view', 'roles.view', 'warehouses.view', 'branches.view'])
  <div class="row g-4">
    @can('users.view')
    <div class="col-sm-6 col-xl-3">
      <div class="card nx-stat nx-stat-1"><div class="card-body">
        <span class="nx-stat-ico"><i class="fas fa-users"></i></span>
        <div><div class="nx-stat-lbl">Users</div><div class="nx-stat-num">{{ $usersCount }}</div></div>
      </div></div>
    </div>
    @endcan
    @can('roles.view')
    <div class="col-sm-6 col-xl-3">
      <div class="card nx-stat nx-stat-2"><div class="card-body">
        <span class="nx-stat-ico"><i class="fas fa-user-shield"></i></span>
        <div><div class="nx-stat-lbl">Roles</div><div class="nx-stat-num">{{ $rolesCount }}</div></div>
      </div></div>
    </div>
    @endcan
    @can('warehouses.view')
    <div class="col-sm-6 col-xl-3">
      <div class="card nx-stat nx-stat-3"><div class="card-body">
        <span class="nx-stat-ico"><i class="fas fa-warehouse"></i></span>
        <div><div class="nx-stat-lbl">Warehouses</div><div class="nx-stat-num">{{ $warehousesCount }}</div></div>
      </div></div>
    </div>
    @endcan
    @can('branches.view')
    <div class="col-sm-6 col-xl-3">
      <div class="card nx-stat nx-stat-4"><div class="card-body">
        <span class="nx-stat-ico"><i class="fas fa-store"></i></span>
        <div><div class="nx-stat-lbl">Branches</div><div class="nx-stat-num">{{ $branchesCount }}</div></div>
      </div></div>
    </div>
    @endcan
  </div>
  @endcanany

  @if($showSampleWidgets)
  <div class="row g-4">
    <div class="col-12 col-xl-8 d-flex">
      <div class="card flex-fill w-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Recent Movement <span class="nx-sample">SAMPLE</span></h5>
        </div>
        <div class="card-body py-3">
          <div class="chart chart-sm"><canvas id="chartjs-dashboard-line"></canvas></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl-4 d-flex">
      <div class="card flex-fill w-100">
        <div class="card-header"><h5 class="card-title mb-0">Stock Share <span class="nx-sample">SAMPLE</span></h5></div>
        <div class="card-body d-flex">
          <div class="align-self-center w-100">
            <div class="py-3"><div class="chart chart-xs"><canvas id="chartjs-dashboard-pie"></canvas></div></div>
            <table class="table mb-0">
              <tbody>
                <tr><td>Group A</td><td class="text-end">4306</td></tr>
                <tr><td>Group B</td><td class="text-end">3801</td></tr>
                <tr><td>Group C</td><td class="text-end">1689</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-12 col-xl-8 d-flex">
      <div class="card flex-fill w-100">
        <div class="card-header"><h5 class="card-title mb-0">Monthly Sales <span class="nx-sample">SAMPLE</span></h5></div>
        <div class="card-body d-flex w-100">
          <div class="align-self-center chart chart-lg"><canvas id="chartjs-dashboard-bar"></canvas></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl-4 d-flex">
      <div class="card flex-fill w-100">
        <div class="card-header"><h5 class="card-title mb-0">Locations <span class="nx-sample">SAMPLE</span></h5></div>
        <div class="card-body px-4"><div id="world_map" class="nx-map"></div></div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-12 col-xl-8 d-flex">
      <div class="card flex-fill w-100">
        <div class="card-header"><h5 class="card-title mb-0">Calendar</h5></div>
        <div class="card-body d-flex">
          <div class="align-self-center w-100"><div class="chart"><div id="datetimepicker-dashboard"></div></div></div>
        </div>
      </div>
    </div>
  </div>
  @endif

  @can('users.view')
  <div class="row g-4">
    <div class="col-12 d-flex">
      <div class="card flex-fill">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Recent Users</h5>
          <a href="{{ route('admin.users.list') }}" class="btn btn-light btn-sm">View all</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr><th>User</th><th class="d-none d-md-table-cell">Email</th><th>Status</th></tr>
            </thead>
            <tbody>
              @forelse($recentUsers as $u)
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <img src="{{ $u->profile_photo_url }}" class="nx-avatar" alt="">
                      <span class="fw-bold">{{ $u->full_name }}</span>
                    </div>
                  </td>
                  <td class="d-none d-md-table-cell">{{ $u->email }}</td>
                  <td>
                    <span class="badge {{ $u->status === 'active' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst($u->status) }}</span>
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" class="text-center text-muted py-4">No users yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  @endcan

</div>
@endsection
