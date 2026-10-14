@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Designations</h1>
      <p class="nx-sub">Manage job titles and the department they belong to</p>
    </div>
    @can('designations.create')
      <a href="{{ route('admin.designations.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add designation
      </a>
    @endcan
  </div>

  <div class="card" data-loading-target>
    <div class="table-responsive">
      <table class="table table-hover align-middle nx-table">
        <thead>
          <tr>
            <th scope="col" class="d-none d-md-table-cell">ID</th>
            <th scope="col">Designation</th>
            <th scope="col">Code</th>
            <th scope="col" class="d-none d-lg-table-cell">Department</th>
            <th scope="col">Status</th>
            <th scope="col" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($designations as $designation)
            <tr>
              <td class="text-muted d-none d-md-table-cell">{{ $designation->id }}</td>

              <td>
                <div class="nx-user-name">{{ $designation->name }}</div>
              </td>

              <td><span class="nx-code">{{ $designation->code }}</span></td>

              <td class="d-none d-lg-table-cell">{{ $designation->department->name ?? '-' }}</td>

              <td>
                @if($designation->status == 'active')
                  <span class="badge bg-success">Active</span>
                @elseif($designation->status == 'inactive')
                  <span class="badge bg-danger">Inactive</span>
                @endif
              </td>

              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  @can('designations.edit')
                    <a href="{{ route('admin.designations.edit', $designation->id) }}" class="nx-action"
                       data-tip="Edit" aria-label="Edit {{ $designation->name }}">
                      <i class="fas fa-pen"></i>
                    </a>
                  @endcan

                  @can('designations.delete')
                    <form action="{{ route('admin.designations.delete', $designation->id) }}" method="POST"
                          data-confirm="This will permanently delete {{ $designation->name }}. This cannot be undone."
                          data-confirm-title="Delete designation?">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="nx-action danger" data-tip="Delete"
                              aria-label="Delete {{ $designation->name }}">
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
                <i class="fas fa-id-badge"></i>
                <div class="nx-empty-title">No designations found</div>
                <div class="nx-empty-sub">Add your first designation to get started.</div>
                @can('designations.create')
                  <a href="{{ route('admin.designations.create') }}" class="btn btn-light btn-sm mt-3">
                    <i class="fas fa-plus"></i> Add designation
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
        Showing <strong>{{ $designations->firstItem() ?? 0 }}-{{ $designations->lastItem() ?? 0 }}</strong>
        of <strong>{{ $designations->total() }}</strong> designations
      </small>
      @if($designations->hasPages()) {{ $designations->links() }} @endif
    </div>
  </div>
</div>
@endsection
