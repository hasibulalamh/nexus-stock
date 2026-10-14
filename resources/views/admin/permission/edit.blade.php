@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Edit Permission</h1>
      <p class="nx-sub">The permission name and guard come from PermissionSeeder — only the status can change</p>
    </div>
    <a href="{{ route('admin.permissions.list') }}" class="btn btn-light">
      <i class="fas fa-arrow-left"></i> Back
    </a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger">
      <strong><i class="fas fa-triangle-exclamation"></i> Please fix the following:</strong>
      <ul class="mb-0 mt-1">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="card-body">
      <form action="{{ route('admin.permissions.update', $permission->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-key"></i> Permission Details</div>
            <hr>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label" for="permissionName">Permission Name</label>
            <div class="nx-static" id="permissionName">{{ $permission->name }}</div>
            <small class="text-muted">Read-only — rename it in PermissionSeeder</small>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label" for="guardName">Guard Name</label>
            <div class="nx-static" id="guardName">{{ $permission->guard_name }}</div>
            {{-- kept as a field name so the payload is unchanged; the controller ignores it --}}
            <input type="hidden" name="guard_name" value="{{ $permission->guard_name }}">
            <small class="text-muted">Read-only — the guard is set by PermissionSeeder</small>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label required" for="status">Status</label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
              <option value="active" @selected(old('status', $permission->status) == 'active')>Active</option>
              <option value="inactive" @selected(old('status', $permission->status) == 'inactive')>Inactive</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

        </div>

        <div class="nx-form-actions">
          <a href="{{ route('admin.permissions.list') }}" class="btn btn-light">
            <i class="fas fa-xmark"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-floppy-disk"></i> Save Permission
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
