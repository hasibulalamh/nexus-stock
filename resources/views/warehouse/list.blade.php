@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Modules</small>Warehouses</h1>
      <p class="nx-sub">Manage storage facilities and their addresses</p>
    </div>
    @can('warehouses.create')
      <a href="{{ route('admin.warehouse.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add warehouse
      </a>
    @endcan
  </div>

  <div class="card" data-loading-target>
    <div class="table-responsive">
      <table class="table table-hover align-middle nx-table">
        <thead>
          <tr>
            <th scope="col" class="d-none d-md-table-cell">ID</th>
            <th scope="col">Warehouse</th>
            <th scope="col">Code</th>
            <th scope="col" class="d-none d-lg-table-cell">Location</th>
            <th scope="col" class="d-none d-md-table-cell">Capacity</th>
            <th scope="col">Status</th>
            <th scope="col" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($warehouses as $warehouse)
            <tr>
              <td class="text-muted d-none d-md-table-cell">{{ $warehouse->id }}</td>

              <td>
                <div class="nx-user-name">{{ $warehouse->name }}</div>
                <div class="nx-user-sub d-lg-none">
                  {{ $warehouse->location?->full_address ?: 'No location set' }}
                </div>
              </td>

              <td><span class="nx-code">{{ $warehouse->code }}</span></td>

              <td class="d-none d-lg-table-cell">
                <span class="nx-user-sub">
                  {{ $warehouse->location?->full_address ?: 'No location set' }}
                </span>
              </td>

              <td class="d-none d-md-table-cell">{{ $warehouse->capacity ?? '-' }}</td>

              <td>
                @if($warehouse->status == 'active')
                  <span class="badge bg-success">Active</span>
                @else
                  <span class="badge bg-danger">Inactive</span>
                @endif
              </td>

              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  @can('warehouses.edit')
                    <a href="{{ route('admin.warehouse.edit', $warehouse->id) }}" class="nx-action"
                       data-tip="Edit" aria-label="Edit {{ $warehouse->name }}">
                      <i class="fas fa-pen"></i>
                    </a>
                  @endcan

                  @can('warehouses.delete')
                    <form action="{{ route('admin.warehouse.delete', $warehouse->id) }}" method="POST"
                          data-confirm="This will permanently delete {{ $warehouse->name }}. This cannot be undone."
                          data-confirm-title="Delete warehouse?">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="nx-action danger" data-tip="Delete"
                              aria-label="Delete {{ $warehouse->name }}">
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
                <i class="fas fa-warehouse"></i>
                <div class="nx-empty-title">No warehouses found</div>
                <div class="nx-empty-sub">Add your first warehouse to get started.</div>
                @can('warehouses.create')
                  <a href="{{ route('admin.warehouse.create') }}" class="btn btn-light btn-sm mt-3">
                    <i class="fas fa-plus"></i> Add warehouse
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
        Showing <strong>{{ $warehouses->firstItem() ?? 0 }}-{{ $warehouses->lastItem() ?? 0 }}</strong>
        of <strong>{{ $warehouses->total() }}</strong> warehouses
      </small>
      @if($warehouses->hasPages()) {{ $warehouses->links('pagination::bootstrap-5') }} @endif
    </div>
  </div>
</div>
@endsection
