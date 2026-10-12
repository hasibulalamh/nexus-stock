@extends('layout.master')

@section('content')

<style>
  .role-card {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0,0,0,.06);
    overflow: hidden;
  }
  .role-card .card-header {
    background: linear-gradient(135deg, #6f42c1, #20c997);
    color: #fff;
    padding: 1rem 1.25rem;
  }
  .form-label {
    font-weight: 500;
  }
  .form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(111,66,193,.25);
    border-color: #6f42c1;
  }
  .btn-submit {
    background: linear-gradient(135deg, #6f42c1, #20c997);
    color: #fff;
    border: 0;
  }
  .btn-submit:hover {
    opacity: 0.9;
  }
</style>

<div class="container my-4">
  <div class="card role-card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="mb-0"><i class="fa-solid fa-user-shield me-2"></i>Create New Role</h5>
      <a href="{{ route('admin.roles.list') }}" class="btn btn-light btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i>Back to Roles
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('admin.roles.update', $role->id) }}" method="POST" enctype="multipart/form-data">
        @method('PUT')
        @csrf

        <div class="mb-3">
          <label for="name" class="form-label">Role Name</label>
          <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $role->name) }}" required>
        </div>

        <div class="form-group mb-3">
    <label for="parent_id">Parent Role</label>
    <select name="parent_id" id="parent_id" class="form-control">
        <option value="">-- No Parent (Top Level Role) --</option>
        @foreach ($roles as $role)
            <option value="{{ $role->id }}" {{ old('parent_id', $role->parent_id ?? '') == $role->id ? 'selected' : '' }}>
                {{ $role->name }}
            </option>
             @endforeach
         </select>
        </div>

           <!--role icon-->
        <div class="mb-3">
          <label for="icon_class" class="form-label">Role Icon</label>
          <input type="text" class="form-control iconpicker" id="icon_class" name="icon_class" 
                placeholder="Select icon" value="{{ old('icon_class', $role->icon_class ?? '') }}">
        </div>


        <div class="mb-3">
          <label for="status" class="form-label">Status</label>
          <select class="form-select" id="status" name="status" value="{{ old('status', $role->status) }}" required>
                 <option value="active" {{ old('status', $role->status) == 'active' ? 'selected' : '' }}>Active</option>
                 <option value="inactive" {{ old('status', $role->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
          </select>
        </div>
        <button type="submit" class="btn btn-submit">
          <i class="fa-solid fa-plus me-1"></i>Edit Role
        </button>
      </form>
    </div>
  </div>
</div>
 <!-- Initialize icon picker globally -->
 <script>
    $(document).ready(function() {
        $('.iconpicker').iconpicker({
            placement: 'bottom',       // Picker popup location
            hideOnSelect: true,        // Close after selecting
            inputSearch: true,         // Search icons
            templates: {
                searchInput: '<input type="search" class="form-control iconpicker-search" placeholder="Search icons">'
            }
        });
    });
  </script>

@endsection
