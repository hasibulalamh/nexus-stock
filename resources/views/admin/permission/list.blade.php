@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Permissions</h1>
      <p class="nx-sub">Read-only list — permissions are created by PermissionSeeder</p>
    </div>
  </div>

  <div class="card" data-loading-target>
    <div class="table-responsive">
      <table class="table table-hover align-middle nx-table">
        <thead>
          <tr>
            <th scope="col" class="d-none d-md-table-cell">ID</th>
            <th scope="col">Permission</th>
            <th scope="col" class="d-none d-md-table-cell">Guard</th>
            <th scope="col">Status</th>
            <th scope="col" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($permissions as $permission)
            <tr>
              <td class="text-muted d-none d-md-table-cell">{{ $permission->id }}</td>

              <td><div class="nx-user-name">{{ $permission->name }}</div></td>

              <td class="d-none d-md-table-cell"><span class="nx-code">{{ $permission->guard_name }}</span></td>

              <td>
                @if($permission->status == 'active')
                  <span class="badge bg-success">Active</span>
                @elseif($permission->status == 'inactive')
                  <span class="badge bg-danger">Inactive</span>
                @endif
              </td>

              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  @can('permissions.edit')
                    <a href="{{ route('admin.permissions.edit', $permission->id) }}" class="nx-action"
                       data-tip="Edit" aria-label="Edit {{ $permission->name }}">
                      <i class="fas fa-pen"></i>
                    </a>
                  @endcan
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="nx-empty">
                <i class="fas fa-key"></i>
                <div class="nx-empty-title">No permissions found</div>
                <div class="nx-empty-sub">Run the PermissionSeeder to create the permission set.</div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="nx-footer-bar">
      <small class="text-muted">
        Showing <strong>{{ $permissions->firstItem() ?? 0 }}-{{ $permissions->lastItem() ?? 0 }}</strong>
        of <strong>{{ $permissions->total() }}</strong> permissions
      </small>
      @if($permissions->hasPages()) {{ $permissions->links() }} @endif
    </div>
  </div>
</div>
@endsection
