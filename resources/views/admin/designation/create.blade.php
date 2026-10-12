@extends('layout.master')

@section('content')
<div class="container-fluid py-4">
  <div class="card shadow border-0">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Create Designation</h5>
      <a href="{{ route('admin.designations.list') }}" class="btn btn-light btn-sm">
        <i class="fas fa-list-ul me-1"></i>Designation List
      </a>
    </div>

    <div class="card-body">
      <form id="designationForm" action="{{ route('admin.designations.store') }}" method="POST">
        @csrf
        <div class="row g-4">
          
          <h6 class="fw-bold text-primary mb-2">🏢 Designation Details</h6>
          <hr class="mt-0 mb-3">

          <div class="col-md-6">
            <label class="form-label required">Designation Name</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="Enter designation name" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Designation Code</label>
            <input type="text" name="code" id="code" class="form-control" placeholder="CODE_WILL_APPEAR_HERE" readonly>
            <small class="text-muted">Auto-generated from designation name</small>
          </div>

          <div class="col-md-6">
            <label class="form-label required">Department</label>
            <select name="department_id" class="form-select" required>
              <option value="">-- Select Department --</option>
              @foreach ($departments as $dept)
                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label required">Status</label>
            <select name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
          <button type="reset" class="btn btn-outline-secondary me-2">
            <i class="fas fa-undo"></i> Reset
          </button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Save Designation
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  .required::after {
    content: " *";
    color: red;
    font-weight: bold;
  }
  .bg-gradient-primary {
    background: linear-gradient(135deg, #20c997, #6f42c1);
  }
  .form-control:focus, .form-select:focus {
    border-color: #6f42c1;
    box-shadow: 0 0 0 0.15rem rgba(111, 66, 193, 0.25);
  }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const nameInput = document.querySelector('#name');
  const codeInput = document.querySelector('#code');

  // Auto-generate code from name
  nameInput.addEventListener('input', function() {
    let name = this.value.trim();
    if (name.length > 0) {
      // Generate a code from the name (e.g., "Senior Developer" -> "SENIOR_DEVELOPER")
      let code = name
        .toUpperCase()
        .replace(/[^\w\s]/g, '')  // Remove special characters
        .trim()
        .replace(/\s+/g, '_');     // Replace spaces with underscores
      
      codeInput.value = code;
    } else {
      codeInput.value = '';
    }
  });
});
</script>
@endsection
