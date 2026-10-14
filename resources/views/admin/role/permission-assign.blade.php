@extends('layout.master')

@section('content')
@php
    // group the flat permission list (users.view, roles.edit, ...) by its prefix
    $groups = $permissions->groupBy(fn ($permission) => \Illuminate\Support\Str::before($permission->name, '.'));
@endphp
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Assign Permissions</h1>
      <p class="nx-sub">Choose the permissions granted to this role</p>
    </div>
    <a href="{{ route('admin.roles.list') }}" class="btn btn-light">
      <i class="fas fa-arrow-left"></i> Back
    </a>
  </div>

  <div class="card">
    <div class="card-body">
      <form action="{{ route('admin.roles.permissions.assign.store', $role->id) }}" method="POST">
        @csrf

        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label" for="roleName">Role Name</label>
            <input type="text" class="form-control" id="roleName" value="{{ $role->name }}" disabled>
            <small class="text-muted">Read-only</small>
          </div>

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-key"></i> Permissions ({{ $permissions->count() }})</div>
            <hr>
          </div>

          <div class="col-12">
            @if($groups->isEmpty())
              <div class="nx-empty">
                <i class="fas fa-key"></i>
                <div class="nx-empty-title">No permissions available</div>
                <div class="nx-empty-sub">Run the PermissionSeeder to create the permission set.</div>
              </div>
            @else
              <div class="nx-perm-grid">
                @foreach($groups as $group => $items)
                  <section class="nx-perm-card">
                    <header>
                      <span>{{ $group }}</span>
                      <span class="nx-count">{{ $items->count() }}</span>
                    </header>
                    <div class="nx-perm-items">
                      @foreach($items as $permission)
                        <div class="form-check">
                          <input class="form-check-input nx-check-input" type="checkbox" name="permissions[]"
                                 value="{{ $permission->id }}" id="permission_{{ $permission->id }}"
                                 {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                          <label class="form-check-label" for="permission_{{ $permission->id }}">
                            {{ $permission->name }}
                          </label>
                        </div>
                      @endforeach
                    </div>
                  </section>
                @endforeach
              </div>
            @endif
          </div>
        </div>

        <div class="nx-form-actions">
          <a href="{{ route('admin.roles.list') }}" class="btn btn-light">
            <i class="fas fa-xmark"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-check"></i> Save permissions
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
