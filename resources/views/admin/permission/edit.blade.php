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
  .form-label {
    font-weight: 500;
  }
  .form-control:focus, .form-select:focus {
    box-shadow: 0 0 0 0.2rem rgba(32,201,151,.25);
    border-color: #20c997;
  }
  .btn-submit {
    background: linear-gradient(135deg, #20c997, #6f42c1);
    color: #fff;
    border: 0;
  }
  .btn-submit:hover {
    opacity: 0.9;
  }
</style>

<div class="container my-4">
  <div class="card permission-card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="mb-0"><i class="fa-solid fa-lock me-2"></i>Edit Permission</h5>
      <a href="{{ route('admin.permissions.list') }}" class="btn btn-light btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i>Back to Permissions
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('admin.permissions.update', $permission->id) }}" method="POST">
        @csrf
        @method('PUT')

     

        <!-- Guard Name -->
        <div class="mb-3">
          <label for="guard_name" class="form-label">Guard Name</label>
          <select class="form-select" id="guard_name" name="guard_name" required>
            <option value="web" {{ $permission->guard_name == 'web' ? 'selected' : '' }}>Web</option>
            <option value="api" {{ $permission->guard_name == 'api' ? 'selected' : '' }}>API</option>
          </select>
        </div>

        <!-- Status -->
        <div class="mb-3">
          <label for="status" class="form-label">Status</label>
          <select class="form-select" id="status" name="status" required>
            <option value="active" {{ $permission->status == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ $permission->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
          </select>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-submit">
          <i class="fa-solid fa-plus me-1"></i>Edit Permission
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@endsection
