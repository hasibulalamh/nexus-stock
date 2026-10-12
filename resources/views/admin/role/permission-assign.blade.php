@extends('layout.master')

@section('content')

<style>
  .assign-card {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0,0,0,.06);
    overflow: hidden;
  }
  .assign-card .card-header {
    background: linear-gradient(135deg, #6f42c1, #20c997);
    color: #fff;
    padding: 1rem 1.25rem;
  }
  .form-label {
    font-weight: 500;
  }
  .permission-list {
    max-height: 350px;
    overflow-y: auto;
    border: 1px solid #e9ecef;
    border-radius: .5rem;
    padding: 1rem;
    background: #fafafa;
  }
  .form-check-input:checked {
    background-color: #20c997;
    border-color: #20c997;
  }
  .btn-submit {
    background: linear-gradient(135deg, #6f42c1, #20c997);
    color: #fff;
    border: 0;
  }
  .btn-submit:hover {
    opacity: 0.9;
  }
  .permission-group {
    margin-bottom: 1.2rem;
  }
  .permission-group h6 {
    font-weight: 600;
    font-size: .9rem;
    margin-bottom: .5rem;
    color: #495057;
  }
</style>

<div class="container my-4">
  <div class="card assign-card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="mb-0"><i class="fa-solid fa-user-shield me-2"></i>Assign Permissions to Role</h5>
      <a href="{{ route('admin.roles.list') }}" class="btn btn-light btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i>Back to Roles
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('admin.roles.permissions.assign.store', $role->id) }}" method="POST">
        @csrf

        <!-- Role Info -->
        <div class="mb-3">
          <label class="form-label">Role Name</label>
          
          <input type="text" class="form-control" value="{{ $role->name }}" disabled>
        </div>

        <!-- Permissions List -->
        <div class="mb-3">
          <label class="form-label">Select Permissions</label>
          <div class="permission-list">
            @foreach($permissions as $permission)
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                     id="permission_{{ $permission->id }}" {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}>
              <label class="form-check-label" for="permission_{{ $permission->id }}">
                {{ $permission->name }}
              </label>
            </div>
            @endforeach
          </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-submit">
          <i class="fa-solid fa-check me-1"></i>Assign Permissions
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@endsection
