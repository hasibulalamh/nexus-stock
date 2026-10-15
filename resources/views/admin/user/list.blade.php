@extends('layout.master')

@section('content')
@php
    $activeFilters = collect(['q', 'department_id', 'designation_id', 'status'])
        ->filter(fn ($k) => request()->filled($k))->count();
@endphp
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Users</h1>
      <p class="nx-sub">Manage users, roles and access</p>
    </div>
    @can('users.create')
      <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add user
      </a>
    @endcan
  </div>

  <div class="card" data-loading-target>

    <form method="GET" action="{{ route('admin.users.list') }}" class="nx-toolbar" data-filter>
      <div class="nx-search">
        <i class="fas fa-search"></i>
        <input type="text" name="q" value="{{ request('q') }}" class="form-control"
               placeholder="Search name, email or employee ID" aria-label="Search users">
      </div>

      <select name="department_id" class="form-select" aria-label="Filter by department" onchange="nxSubmit(this.form)">
        <option value="">All departments</option>
        @foreach($departments as $d)
          <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
        @endforeach
      </select>

      <select name="designation_id" class="form-select" aria-label="Filter by designation" onchange="nxSubmit(this.form)">
        <option value="">All designations</option>
        @foreach($designations as $d)
          <option value="{{ $d->id }}" @selected(request('designation_id') == $d->id)>{{ $d->name }}</option>
        @endforeach
      </select>

      <select name="status" class="form-select" aria-label="Filter by status" onchange="nxSubmit(this.form)">
        <option value="">Any status</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
      </select>

      <input type="hidden" name="per_page" value="{{ request('per_page') }}">

      <button type="submit" class="btn btn-light">
        <i class="fas fa-filter"></i> Apply
        @if($activeFilters)<span class="nx-count" aria-label="{{ $activeFilters }} filters applied">{{ $activeFilters }}</span>@endif
      </button>
      @if($activeFilters)
        <a href="{{ route('admin.users.list') }}" class="btn btn-link">Reset</a>
      @endif
    </form>

    <div class="table-responsive">
      <table class="table table-hover align-middle nx-table">
        <thead>
          <tr>
            <th class="nx-col-num">#</th>
            <th>User</th>
            <th class="d-none d-lg-table-cell">Department</th>
            <th class="d-none d-lg-table-cell">Designation</th>
            <th class="d-none d-md-table-cell">Role</th>
            <th class="d-none d-xl-table-cell">Allocations</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $user)
          <tr>
            <td class="text-muted">{{ $users->firstItem() + $loop->index }}</td>

            <td>
              <div class="nx-user">
                <img src="{{ $user->profile_photo_url }}" alt="" class="nx-avatar">
                <div>
                  <div class="nx-user-name">{{ $user->full_name }}</div>
                  <div class="nx-user-sub text-truncate">{{ $user->email }}</div>
                  <div class="nx-user-sub d-lg-none">
                    {{ $user->department?->name ?? '-' }} · {{ $user->designation?->name ?? '-' }}
                  </div>
                </div>
              </div>
            </td>

            <td class="d-none d-lg-table-cell">{{ $user->department?->name ?? '-' }}</td>
            <td class="d-none d-lg-table-cell">{{ $user->designation?->name ?? '-' }}</td>

            <td class="d-none d-md-table-cell">
              @if($user->roles->first())
                <span class="nx-role">{{ $user->roles->first()->name }}</span>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>

            <td class="d-none d-xl-table-cell">
              @forelse($user->branches as $branch)
                <div class="small">{{ $branch->name }}</div>
                <div class="nx-user-sub">{{ $branch->location?->full_address ?: 'No location set' }}</div>
              @empty
                <span class="text-muted">No branch assigned</span>
              @endforelse
              @foreach($user->warehouses as $warehouse)
                <div class="small">{{ $warehouse->name }}</div>
                <div class="nx-user-sub">{{ $warehouse->location?->full_address ?: 'No location set' }}</div>
              @endforeach
            </td>

            <td>
              @if($user->status == 'active')
                <span class="badge bg-success">Active</span>
              @else
                <span class="badge bg-danger">Inactive</span>
              @endif
            </td>

            <td class="text-end">
              <div class="d-inline-flex gap-1">
                @can('users.edit')
                  <a href="{{ route('admin.users.edit', $user->id) }}" class="nx-action"
                     data-tip="Edit" aria-label="Edit {{ $user->full_name }}">
                    <i class="fas fa-pen"></i>
                  </a>
                @endcan

                @can('users.status')
                  @if($user->id !== auth()->id())
                    <form action="{{ route('admin.users.status', $user->id) }}" method="POST"
                          @if($user->status === 'active')
                            data-confirm="This will sign {{ $user->full_name }} out and block their login until restored."
                            data-confirm-title="Restrict user?"
                          @endif>
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="nx-action"
                              data-tip="{{ $user->status === 'active' ? 'Restrict' : 'Restore' }}"
                              aria-label="{{ $user->status === 'active' ? 'Restrict' : 'Restore' }} {{ $user->full_name }}">
                        <i class="fas {{ $user->status === 'active' ? 'fa-lock' : 'fa-lock-open' }}"></i>
                      </button>
                    </form>
                  @endif
                @endcan

                @can('users.delete')
                  @if($user->id !== auth()->id())
                    <form action="{{ route('admin.users.delete', $user->id) }}" method="POST"
                          data-confirm="This will permanently delete {{ $user->full_name }}. This cannot be undone."
                          data-confirm-title="Delete user?">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="nx-action danger" data-tip="Delete" aria-label="Delete {{ $user->full_name }}">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  @endif
                @endcan
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="text-center py-5">
              <i class="fas fa-user-slash fs-3 d-block mb-2 text-muted"></i>
              <div class="fw-semibold">No users found</div>
              <div class="text-muted small">
                {{ $activeFilters ? 'Try changing or clearing the filters.' : 'Add your first user to get started.' }}
              </div>
              @if($activeFilters)
                <a href="{{ route('admin.users.list') }}" class="btn btn-light btn-sm mt-3">Clear filters</a>
              @endif
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="nx-footer-bar">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <small class="text-muted">
          @if($users->total())
            Showing <strong>{{ $users->firstItem() }}-{{ $users->lastItem() }}</strong> of <strong>{{ $users->total() }}</strong> users
          @else
            0 results
          @endif
        </small>
        <form method="GET" action="{{ route('admin.users.list') }}" class="d-flex align-items-center gap-2" data-filter>
          @foreach(request()->except(['per_page', 'page']) as $k => $v)
            @if(!is_array($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
          @endforeach
          <label class="small text-muted mb-0" for="per_page">Rows</label>
          <select id="per_page" name="per_page" class="form-select form-select-sm w-auto" onchange="nxSubmit(this.form)">
            @foreach([10, 25, 50] as $n)
              <option value="{{ $n }}" @selected((int) request('per_page', 10) === $n)>{{ $n }}</option>
            @endforeach
          </select>
        </form>
      </div>
      @if($users->hasPages()) {{ $users->links() }} @endif
    </div>
  </div>
</div>
@endsection
