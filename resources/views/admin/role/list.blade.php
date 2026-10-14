@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Roles</h1>
      <p class="nx-sub">Manage roles, their hierarchy and permission sets</p>
    </div>
    @can('roles.create')
      <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add role
      </a>
    @endcan
  </div>

  <div class="card" data-loading-target>
    <div class="table-responsive">
      <table class="table table-hover align-middle nx-table">
        <thead>
          <tr>
            <th scope="col" class="d-none d-md-table-cell">ID</th>
            <th scope="col">Role</th>
            <th scope="col" class="d-none d-lg-table-cell">Parent role</th>
            <th scope="col" class="d-none d-md-table-cell">Icon</th>
            <th scope="col">Status</th>
            <th scope="col" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($roles as $role)
            <tr>
              <td class="text-muted d-none d-md-table-cell">{{ $role->id }}</td>

              <td><div class="nx-user-name">{{ $role->name }}</div></td>

              <td class="d-none d-lg-table-cell">
                {{ $role->parent && $role->parent->name ? $role->parent->name : 'No Parent' }}
              </td>

              <td class="d-none d-md-table-cell">
                <span class="nx-ico-chip"><i class="{{ $role->icon_class }}"></i></span>
              </td>

              <td>
                @if($role->status == 'active')
                  <span class="badge bg-success">Active</span>
                @elseif($role->status == 'inactive')
                  <span class="badge bg-danger">Inactive</span>
                @endif
              </td>

              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  @can('roles.view')
                    <a href="{{ route('admin.roles.audit-log', $role->id) }}" class="nx-action"
                       data-tip="Audit log" aria-label="View audit log for {{ $role->name }}">
                      <i class="fas fa-clipboard-list"></i>
                    </a>
                  @endcan

                  @can('roles.assign')
                    <a href="{{ route('admin.roles.permissions.assign', $role->id) }}" class="nx-action"
                       data-tip="Assign permissions" aria-label="Assign permissions to {{ $role->name }}">
                      <i class="fas fa-user-shield"></i>
                    </a>
                  @endcan

                  @can('roles.edit')
                    <a href="{{ route('admin.roles.edit', $role->id) }}" class="nx-action"
                       data-tip="Edit" aria-label="Edit {{ $role->name }}">
                      <i class="fas fa-pen"></i>
                    </a>
                  @endcan

                  @can('roles.delete')
                    <form action="{{ route('admin.roles.delete', $role->id) }}" method="POST"
                          data-confirm="This will permanently delete {{ $role->name }}. This cannot be undone."
                          data-confirm-title="Delete role?">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="nx-action danger" data-tip="Delete"
                              aria-label="Delete {{ $role->name }}">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  @endcan
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="nx-empty">
                <i class="fas fa-user-shield"></i>
                <div class="nx-empty-title">No roles found</div>
                <div class="nx-empty-sub">Add your first role to get started.</div>
                @can('roles.create')
                  <a href="{{ route('admin.roles.create') }}" class="btn btn-light btn-sm mt-3">
                    <i class="fas fa-plus"></i> Add role
                  </a>
                @endcan
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="nx-footer-bar">
      <small class="text-muted">
        Showing <strong>{{ $roles->firstItem() ?? 0 }}-{{ $roles->lastItem() ?? 0 }}</strong>
        of <strong>{{ $roles->total() }}</strong> roles
      </small>
      @if($roles->hasPages()) {{ $roles->links() }} @endif
    </div>
  </div>
</div>
@endsection
