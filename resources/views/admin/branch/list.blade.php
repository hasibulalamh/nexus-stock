@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Modules</small>Branches</h1>
      <p class="nx-sub">Manage branch offices and their addresses</p>
    </div>
    @can('branches.create')
      <a href="{{ route('admin.branch.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add branch
      </a>
    @endcan
  </div>

  <div class="card" data-loading-target>
    <div class="table-responsive">
      <table class="table table-hover align-middle nx-table">
        <thead>
          <tr>
            <th scope="col" class="d-none d-md-table-cell">ID</th>
            <th scope="col">Branch</th>
            <th scope="col">Code</th>
            <th scope="col" class="d-none d-lg-table-cell">Location</th>
            <th scope="col" class="d-none d-md-table-cell">Capacity</th>
            <th scope="col">Status</th>
            <th scope="col" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($branches as $branch)
            <tr>
              <td class="text-muted d-none d-md-table-cell">{{ $branch->id }}</td>

              <td>
                <div class="nx-user-name">{{ $branch->name }}</div>
                <div class="nx-user-sub d-lg-none">
                  {{ $branch->location?->full_address ?: 'No location set' }}
                </div>
              </td>

              <td><span class="nx-code">{{ $branch->code }}</span></td>

              <td class="d-none d-lg-table-cell">
                <span class="nx-user-sub">
                  {{ $branch->location?->full_address ?: 'No location set' }}
                </span>
              </td>

              <td class="d-none d-md-table-cell">{{ $branch->capacity ?? '-' }}</td>

              <td>
                @if($branch->status == 'active')
                  <span class="badge bg-success">Active</span>
                @else
                  <span class="badge bg-danger">Inactive</span>
                @endif
              </td>

              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  @can('branches.edit')
                    <a href="{{ route('admin.branch.edit', $branch->id) }}" class="nx-action"
                       data-tip="Edit" aria-label="Edit {{ $branch->name }}">
                      <i class="fas fa-pen"></i>
                    </a>
                  @endcan

                  @can('branches.delete')
                    <form action="{{ route('admin.branch.delete', $branch->id) }}" method="POST"
                          data-confirm="This will permanently delete {{ $branch->name }}. This cannot be undone."
                          data-confirm-title="Delete branch?">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="nx-action danger" data-tip="Delete"
                              aria-label="Delete {{ $branch->name }}">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  @endcan
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="nx-empty">
                <i class="fas fa-store"></i>
                <div class="nx-empty-title">No branches found</div>
                <div class="nx-empty-sub">Add your first branch to get started.</div>
                @can('branches.create')
                  <a href="{{ route('admin.branch.create') }}" class="btn btn-light btn-sm mt-3">
                    <i class="fas fa-plus"></i> Add branch
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
        Showing <strong>{{ $branches->firstItem() ?? 0 }}-{{ $branches->lastItem() ?? 0 }}</strong>
        of <strong>{{ $branches->total() }}</strong> branches
      </small>
      @if($branches->hasPages()) {{ $branches->links('pagination::bootstrap-5') }} @endif
    </div>
  </div>
</div>
@endsection
