@extends('layout.master')

@section('content')

<style>
  .desig-card {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0,0,0,.06);
    overflow: hidden;
  }
  .desig-card .card-header {
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
  <div class="card desig-card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
      <h5 class="mb-0"><i class="fa-solid fa-briefcase me-2"></i>Designation Management</h5>
      <a href="{{ route('admin.designations.create') }}" class="btn btn-light btn-sm">
        <i class="fa-solid fa-plus me-1"></i>Create Designation
      </a>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
          <thead>
            <tr>
              <th style="width: 80px;">ID</th>
              <th>Designation Name</th>
              <th>Designation code</th>  
              <th>Department</th>
              <th style="width: 160px;">Status</th>
              <th style="width: 160px;" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($designations as $designation)
            <tr>
              <td>{{ $designation->id }}</td>
              <td>{{ $designation->name }}</td>
              <td>{{ $designation->code }}</td>
              <td>{{ $designation->department->name }}</td>
              <td>
                @if ($designation->status == 'active')
                <span class="status-dot dot-active"></span>
                <span class="badge text-bg-success">{{ $designation->status }}</span>
                @elseif ($designation->status == 'inactive')
                <span class="status-dot dot-inactive"></span>
                <span class="badge text-bg-danger">{{ $designation->status }}</span>
                @endif
              </td>
              <td class="text-end action-btns">
              <div class="d-flex gap-2 justify-content-end">
                  <a href=" {{ route('admin.designations.edit', $designation->id) }}" class="btn btn-outline-primary btn-sm rounded-circle" data-bs-toggle="tooltip" data-bs-title="Edit" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-regular fa-pen-to-square"></i>
                  </a>
                  <form action="{{ route('admin.designations.delete', $designation->id) }}" method="POST" class="d-inline">
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
        <small class="text-muted">Showing <strong> {{ $designations->total() }}</strong> designations</small>
        <nav>
          {{ $designations->links() }}
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
