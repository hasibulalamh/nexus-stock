@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Create Designation</h1>
      <p class="nx-sub">Add a job title and link it to a department</p>
    </div>
    <a href="{{ route('admin.designations.list') }}" class="btn btn-light">
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
      <form id="designationForm" action="{{ route('admin.designations.store') }}" method="POST">
        @csrf
        <div class="row g-3">

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-id-badge"></i> Designation Details</div>
            <hr>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label required" for="name">Designation Name</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}"
                   class="form-control @error('name') is-invalid @enderror"
                   placeholder="Enter designation name" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label required" for="code">Designation Code</label>
            <input type="text" name="code" id="code" value="{{ old('code') }}"
                   class="form-control @error('code') is-invalid @enderror"
                   placeholder="CODE_WILL_APPEAR_HERE" readonly>
            <small class="text-muted">Auto-generated from designation name</small>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label required" for="department_id">Department</label>
            <select name="department_id" id="department_id"
                    class="form-select @error('department_id') is-invalid @enderror" required>
              <option value="">-- Select Department --</option>
              @foreach ($departments as $dept)
                <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
              @endforeach
            </select>
            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label required" for="status">Status</label>
            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
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
            <i class="fas fa-floppy-disk"></i> Save Designation
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const nameInput = document.querySelector('#name');
  const codeInput = document.querySelector('#code');

  // Auto-generate code from name
  nameInput.addEventListener('input', function () {
    let name = this.value.trim();
    if (name.length > 0) {
      // "Senior Developer" -> "SENIOR_DEVELOPER"
      let code = name
        .toUpperCase()
        .replace(/[^\w\s]/g, '')  // Remove special characters
        .trim()
        .replace(/\s+/g, '_');    // Replace spaces with underscores

      codeInput.value = code;
    } else {
      codeInput.value = '';
    }
  });
});
</script>
@endsection
