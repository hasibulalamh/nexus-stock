@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Create Department</h1>
      <p class="nx-sub">Add a department and its short code</p>
    </div>
    <a href="{{ route('admin.departments.list') }}" class="btn btn-light">
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
      <form action="{{ route('admin.departments.store') }}" method="POST">
        @csrf
        <div class="row g-3">

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-sitemap"></i> Department Details</div>
            <hr>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label required" for="deptName">Department Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="deptName"
                   name="name" value="{{ old('name') }}" placeholder="Enter department name" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label required" for="deptCode">Department Code</label>
            <input type="text" class="form-control @error('code') is-invalid @enderror" id="deptCode"
                   name="code" value="{{ old('code') }}" placeholder="Auto-generated" readonly>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label required" for="status">Status</label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
              <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
              <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

        </div>

        <div class="nx-form-actions">
          <button type="reset" class="btn btn-outline-secondary">
            <i class="fas fa-rotate-left"></i> Reset
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-floppy-disk"></i> Save Department
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  const deptNameInput = document.getElementById('deptName');
  const deptCodeInput = document.getElementById('deptCode');

  deptNameInput.addEventListener('input', function () {
    const name = deptNameInput.value.trim();
    if (name.length === 0) {
      deptCodeInput.value = '';
      return;
    }

    // Take first letters of each word in department name
    const words = name.split(' ').filter(w => w.length > 0);
    let code = words.map(w => w[0]).join('').toUpperCase();

    // Add simple numeric suffix (001)
    code += '001';

    deptCodeInput.value = code;
  });
</script>
@endsection
