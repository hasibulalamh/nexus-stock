@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Edit Designation</h1>
      <p class="nx-sub">Update the designation name, code and department</p>
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
      <form action="{{ route('admin.designations.update', $designation->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-id-badge"></i> Designation Details</div>
            <hr>
          </div>

          <div class="col-12 col-md-6">
            <label for="name" class="form-label required">Designation Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                   value="{{ old('name', $designation->name) }}" name="name" required
                   placeholder="Enter designation name" onkeyup="generateCode()">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label" for="codePreview">Designation Code</label>
            <div class="nx-static" id="codePreview">{{ $designation->code }}</div>
            <input type="hidden" name="code" id="codeInput" value="{{ old('code', $designation->code) }}">
            <small class="text-muted">Code is auto-generated from the designation name</small>
          </div>

          <div class="col-12 col-md-6">
            <label for="department_id" class="form-label required">Department</label>
            <select class="form-select @error('department_id') is-invalid @enderror" id="department_id"
                    name="department_id" required>
              <option value="" disabled>Select Department</option>
              @foreach($departments as $department)
                <option value="{{ $department->id }}"
                        @selected(old('department_id', $designation->department_id) == $department->id)>
                  {{ $department->name }}
                </option>
              @endforeach
            </select>
            @error('department_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label required" for="status">Status</label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
              <option value="active" @selected(old('status', $designation->status) == 'active')>Active</option>
              <option value="inactive" @selected(old('status', $designation->status) == 'inactive')>Inactive</option>
            </select>
            @error('status')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

        </div>

        <div class="nx-form-actions">
          <button type="reset" class="btn btn-outline-secondary">
            <i class="fas fa-rotate-left"></i> Reset
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-floppy-disk"></i> Update Designation
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  // Initialize code preview with current value
  document.addEventListener('DOMContentLoaded', function () {
    generateCode();
  });

  function generateCode() {
    const nameInput = document.getElementById('name');
    const codePreview = document.getElementById('codePreview');
    const codeInput = document.getElementById('codeInput');

    if (nameInput.value.trim() !== '') {
      // "Senior Developer" -> "SENIOR_DEVELOPER"
      const code = nameInput.value
        .toUpperCase()
        .replace(/[^\w\s]/g, '')  // Remove special characters
        .trim()
        .replace(/\s+/g, '_');    // Replace spaces with underscores

      codePreview.textContent = code;
      codeInput.value = code;
    } else {
      // If name is empty, show the current code
      codePreview.textContent = '{{ $designation->code }}';
      codeInput.value = '{{ $designation->code }}';
    }
  }
</script>
@endsection
