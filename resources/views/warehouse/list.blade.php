@extends('layout.master')

@section('content')

<style>
  .list-card {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0,0,0,.06);
    overflow: hidden;
  }
  .list-card .card-header {
    background: linear-gradient(135deg, #6f42c1, #20c997);
    color: #fff;
    padding: 1rem 1.25rem;
  }
  .table thead th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: .8rem;
    background: #f8f9fa;
    border-bottom: 0;
  }
  .table-hover tbody tr:hover {
    background: #fbfbff;
  }
  .status-dot {
    width: .6rem; height: .6rem; border-radius: 50%;
    display: inline-block; margin-right: .4rem;
  }
  .dot-active { background: #28a745; }
  .dot-inactive { background: #6c757d; }
  .action-btns .btn {
    border-radius: 50%;
  }
</style>

<div class="container my-4">
  <div class="card list-card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fa-solid fa-warehouse me-2"></i>Warehouse List</h5>
      <a href="{{ route('admin.warehouse.create') }}" class="btn btn-light btn-sm">
        <i class="fa-solid fa-plus me-1"></i>Create Warehouse
      </a>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
          <thead>
            <tr>
              <th>#ID</th>
              <th>Warehouse Name</th>
              <th>Code</th>
              <th>Location</th>
              <th>Capacity</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($warehouses as $warehouse)
              <tr>
                <td>{{ $warehouse->id }}</td>
                <td>{{ $warehouse->name }}</td>
                <td><span class="badge bg-dark">{{ $warehouse->code }}</span></td>
                <td>
                    <small class="text-muted">
                       {{ $warehouse->location->country->name ?? '-' }},
                       {{ $warehouse->location->state->name ?? '-' }},
                       {{ $warehouse->location->city->name ?? '-' }},
                       {{ $warehouse->location->thana->name ?? '-' }},
                       {{ $warehouse->location->union->name ?? '-' }}
                 </small>
                </td>
                <td>{{ $warehouse->capacity ?? '-' }}</td>
                <td>
                  @if($warehouse->status == 'active')
                    <span class="status-dot dot-active"></span>
                    <span class="badge bg-success">Active</span>
                  @else
                    <span class="status-dot dot-inactive"></span>
                    <span class="badge bg-secondary">Inactive</span>
                  @endif
                </td>
                <td class="text-end action-btns">
                  <a href=" {{ route('admin.warehouse.edit', $warehouse->id) }}" 
                     class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" title="Edit">
                    <i class="fa-regular fa-pen-to-square"></i>
                  </a>
                  <form action=" {{ route('admin.warehouse.delete', $warehouse->id) }}" 
                        method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm" 
                            onclick="return confirm('Are you sure to delete this warehouse?')" 
                            data-bs-toggle="tooltip" title="Delete">
                      <i class="fa-regular fa-trash-can"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-4">No warehouses found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="p-3 d-flex justify-content-between align-items-center">
        <small class="text-muted">
          Showing {{ $warehouses->firstItem() ?? 0 }} - {{ $warehouses->lastItem() ?? 0 }} of {{ $warehouses->total() }} warehouses
        </small>
        <div>
          {{ $warehouses->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap tooltip -->
<script>
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>

@endsection
