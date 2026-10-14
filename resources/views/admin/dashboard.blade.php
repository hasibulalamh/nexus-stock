@extends('layout.master')

@section('content')
@php
    $count = function ($table) {
        try { return \Illuminate\Support\Facades\DB::table($table)->count(); }
        catch (\Throwable $e) { return 0; }
    };
    $recentUsers = \App\Models\User::latest()->take(6)->get();
@endphp

<div class="container-fluid p-0">

  <h1 class="h3 nx-title">
    <small>Overview</small>
    Welcome back, {{ auth()->user()->full_name }}
  </h1>

  {{-- real counts --}}
  <div class="row">
    <div class="col-sm-6 col-xl-3">
      <div class="card nx-stat" style="--c:#14b8a6"><div class="card-body">
        <span class="nx-stat-ico"><i class="fas fa-users"></i></span>
        <div><div class="nx-stat-lbl">Users</div><div class="nx-stat-num">{{ $count('users') }}</div></div>
      </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card nx-stat" style="--c:#f43f5e"><div class="card-body">
        <span class="nx-stat-ico"><i class="fas fa-user-shield"></i></span>
        <div><div class="nx-stat-lbl">Roles</div><div class="nx-stat-num">{{ $count('roles') }}</div></div>
      </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card nx-stat" style="--c:#f97316"><div class="card-body">
        <span class="nx-stat-ico"><i class="fas fa-warehouse"></i></span>
        <div><div class="nx-stat-lbl">Warehouses</div><div class="nx-stat-num">{{ $count('warehouses') }}</div></div>
      </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card nx-stat" style="--c:#22c55e"><div class="card-body">
        <span class="nx-stat-ico"><i class="fas fa-store"></i></span>
        <div><div class="nx-stat-lbl">Branches</div><div class="nx-stat-num">{{ $count('branches') }}</div></div>
      </div></div>
    </div>
  </div>

  <div class="row">
    <div class="col-xl-8 d-flex">
      <div class="card flex-fill w-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Recent Movement <span class="nx-sample">SAMPLE</span></h5>
        </div>
        <div class="card-body py-3">
          <div class="chart chart-sm"><canvas id="chartjs-dashboard-line"></canvas></div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 d-flex">
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

  <div class="row">
    <div class="col-12 col-xl-8 d-flex">
      <div class="card flex-fill">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Recent Users</h5>
          @can('users.view')
            <a href="{{ route('admin.users.list') }}" class="btn btn-light btn-sm">View all</a>
          @endcan
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

    <div class="col-12 col-xl-4 d-flex">
      <div class="card flex-fill w-100">
        <div class="card-header"><h5 class="card-title mb-0">Monthly Sales <span class="nx-sample">SAMPLE</span></h5></div>
        <div class="card-body d-flex w-100">
          <div class="align-self-center chart chart-lg"><canvas id="chartjs-dashboard-bar"></canvas></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12 col-xl-8 d-flex">
      <div class="card flex-fill w-100">
        <div class="card-header"><h5 class="card-title mb-0">Locations <span class="nx-sample">SAMPLE</span></h5></div>
        <div class="card-body px-4"><div id="world_map" style="height:320px;"></div></div>
      </div>
    </div>
    <div class="col-12 col-xl-4 d-flex">
      <div class="card flex-fill">
        <div class="card-header"><h5 class="card-title mb-0">Calendar</h5></div>
        <div class="card-body d-flex">
          <div class="align-self-center w-100"><div class="chart"><div id="datetimepicker-dashboard"></div></div></div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
