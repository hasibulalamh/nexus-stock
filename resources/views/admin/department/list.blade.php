@extends('layout.master')

@section('content')

<style>
  .dept-card {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0,0,0,.06);
    overflow: hidden;
  }
  .dept-card .card-header {
    background: linear-gradient(135deg, #6f42c1, #20c997);
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
  .status-dot {
    width: .5rem; height: .5rem; display: inline-block; border-radius: 50%;
    margin-right: .4rem;
  }
  .dot-active { background: #28a745; }
  .dot-inactive { background: #6c757d; }
  .action-btns .btn {
    border-radius: 999px;
  }
  .table-hover tbody tr:hover {
    background: #fbfbff;
  }
</style>

<div class="container my-4">
  <div class="card dept-card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
      <h5 class="mb-0"><i class="fa-solid fa-building me-2"></i>Department Management</h5>
      <a href="{{ route('admin.departments.create') }}" class="btn btn-light btn-sm">
        <i class="fa-solid fa-plus me-1"></i>Create Department
      </a>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
          <thead>
            <tr>
              <th style="width: 80px;">ID</th>
              <th>Department Name</th>
              <th>Department Code</th>
              <th style="width: 160px;">Status</th>
              <th style="width: 160px;" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
           @foreach ($departments as $department)
            <tr>
              <td>{{ $department->id }}</td>
              <td>{{ $department->name }}</td>
              <td>{{ $department->code }}</td>
              <td>
                @if ($department->status == 'active')
                <span class="status-dot dot-active"></span>
                <span class="badge text-bg-success">Active</span>
                @elseif ($department->status == 'inactive')
                <span class="status-dot dot-inactive"></span>
                <span class="badge text-bg-danger">Inactive</span>
                @endif
              </td>
              <td class="text-end">
                <div class="d-flex gap-2 justify-content-end">
                  <a href="{{ route('admin.departments.edit', $department->id) }}" class="btn btn-outline-primary btn-sm rounded-circle" data-bs-toggle="tooltip" data-bs-title="Edit" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-regular fa-pen-to-square"></i>
                  </a>
                  <form action="{{ route('admin.departments.delete', $department->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger rounded-circle" data-bs-toggle="tooltip" data-bs-title="Delete" onclick="return confirm('Are you sure you want to delete this role?')">
                      <i class="fa-regular fa-trash-can"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>

      <!-- Footer -->
      <div class="p-3 d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing <strong> {{ $departments->total() }}</strong> departments</small>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            {{ $departments->links() }}
          </ul>
        </nav>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>
@endsection
