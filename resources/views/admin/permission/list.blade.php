@extends('layout.master')

@section('content')

<style>
  .permission-card {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0,0,0,.06);
    overflow: hidden;
  }
  .permission-card .card-header {
    background: linear-gradient(135deg, #20c997, #6f42c1);
    color: #fff;
    padding: 1rem 1.25rem;
  }
  .table thead th {
    font-weight: 600;
    letter-spacing: .3px;
    text-transform: uppercase;
    font-size: .8rem;
    background: #f8f9fa;
    border-bottom: 0;
  }
  .action-btns .btn {
    border-radius: 999px;
  }
  .table-hover tbody tr:hover {
    background: #fbfbff;
  }
  .status-dot {
    width: .5rem; height: .5rem; display: inline-block; border-radius: 50%;
    margin-right: .4rem;
  }
  .dot-active { background: #28a745; }
  .dot-inactive { background: #6c757d; }
</style>

<div class="container my-4">
  <div class="card permission-card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
      <h5 class="mb-0"><i class="fa-solid fa-lock me-2"></i>Permission Management</h5>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
          <thead>
            <tr>
              <th style="width: 80px;">ID</th>
              <th>Permission Name</th>
              <th style="width: 180px;">Guard Name</th>
              <th style="width: 160px;">Status</th>
              <th style="width: 160px;" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($permissions as $permission)
            <tr>
              <td>{{$permission->id}}</td>
              <td>{{$permission->name}}</td>
              <td>{{$permission->guard_name}}</td>
              <td>
                @if ($permission->status == 'active')
                <span class="status-dot dot-active"></span>
                <span class="badge text-bg-success">Active</span>
                @elseif ($permission->status == 'inactive')
                <span class="status-dot dot-inactive"></span>
                <span class="badge text-bg-danger">Inactive</span>
                @endif
              </td>
              <td class="text-end">
                <div class="d-flex gap-2 justify-content-end">
                  <a href="{{ route('admin.permissions.edit', $permission->id) }}" class="btn btn-outline-primary btn-sm rounded-circle" data-bs-toggle="tooltip" data-bs-title="Edit" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-regular fa-pen-to-square"></i>
                  </a>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Footer -->
           <!-- Footer -->
      <div class="p-3 d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing <strong>{{ $permissions->total() }}</strong> permissions</small>
    <nav>{{ $permissions->links() }}</nav>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Enable tooltips
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>
@endsection
