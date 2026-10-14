@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Departments</h1>
      <p class="nx-sub">Manage the departments used across the organisation</p>
    </div>
    @can('departments.create')
      <a href="{{ route('admin.departments.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add department
      </a>
    @endcan
  </div>

  <div class="card" data-loading-target>
    <div class="table-responsive">
      <table class="table table-hover align-middle nx-table">
        <thead>
          <tr>
            <th scope="col" class="d-none d-md-table-cell">ID</th>
            <th scope="col">Department</th>
            <th scope="col">Code</th>
            <th scope="col">Status</th>
            <th scope="col" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($departments as $department)
            <tr>
              <td class="text-muted d-none d-md-table-cell">{{ $department->id }}</td>

              <td><div class="nx-user-name">{{ $department->name }}</div></td>

              <td><span class="nx-code">{{ $department->code }}</span></td>

              <td>
                @if($department->status == 'active')
                  <span class="badge bg-success">Active</span>
                @elseif($department->status == 'inactive')
                  <span class="badge bg-danger">Inactive</span>
                @endif
              </td>

              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  @can('departments.edit')
                    <a href="{{ route('admin.departments.edit', $department->id) }}" class="nx-action"
                       data-tip="Edit" aria-label="Edit {{ $department->name }}">
                      <i class="fas fa-pen"></i>
                    </a>
                  @endcan

                  @can('departments.delete')
                    <form action="{{ route('admin.departments.delete', $department->id) }}" method="POST"
                          data-confirm="This will permanently delete {{ $department->name }}. This cannot be undone."
                          data-confirm-title="Delete department?">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="nx-action danger" data-tip="Delete"
                              aria-label="Delete {{ $department->name }}">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  @endcan
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="nx-empty">
                <i class="fas fa-sitemap"></i>
                <div class="nx-empty-title">No departments found</div>
                <div class="nx-empty-sub">Add your first department to get started.</div>
                @can('departments.create')
                  <a href="{{ route('admin.departments.create') }}" class="btn btn-light btn-sm mt-3">
                    <i class="fas fa-plus"></i> Add department
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
        Showing <strong>{{ $departments->firstItem() ?? 0 }}-{{ $departments->lastItem() ?? 0 }}</strong>
        of <strong>{{ $departments->total() }}</strong> departments
      </small>
      @if($departments->hasPages()) {{ $departments->links() }} @endif
    </div>
  </div>
</div>
@endsection
