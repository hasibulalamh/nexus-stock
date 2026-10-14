@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Edit Role</h1>
      <p class="nx-sub">Update the role name, icon and hierarchy</p>
    </div>
    <a href="{{ route('admin.roles.list') }}" class="btn btn-light">
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
      <form action="{{ route('admin.roles.update', $role->id) }}" method="POST" enctype="multipart/form-data">
        @method('PUT')
        @csrf
        <div class="row g-3">

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-user-shield"></i> Role Details</div>
            <hr>
          </div>

          <div class="col-12 col-md-6">
            <label for="name" class="form-label required">Role Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                   name="name" value="{{ old('name', $role->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label for="parent_id" class="form-label">Parent Role</label>
            <select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
              <option value="">-- No Parent (Top Level Role) --</option>
              @foreach($roles as $parent)
                <option value="{{ $parent->id }}"
                        @selected(old('parent_id', $role->parent_id ?? '') == $parent->id)>
                  {{ $parent->name }}
                </option>
              @endforeach
            </select>
            @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label for="icon_class" class="form-label required">Role Icon</label>
            <input type="text" class="form-control iconpicker @error('icon_class') is-invalid @enderror"
                   id="icon_class" name="icon_class" placeholder="Select icon"
                   value="{{ old('icon_class', $role->icon_class ?? '') }}" required>
            @error('icon_class')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label for="status" class="form-label required">Status</label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
              <option value="active" @selected(old('status', $role->status) == 'active')>Active</option>
              <option value="inactive" @selected(old('status', $role->status) == 'inactive')>Inactive</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

        </div>

        <div class="nx-form-actions">
          <a href="{{ route('admin.roles.list') }}" class="btn btn-light">
            <i class="fas fa-xmark"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-floppy-disk"></i> Update Role
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
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
