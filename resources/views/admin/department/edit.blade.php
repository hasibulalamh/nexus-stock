@extends('layout.master')

@section('content')

<style>
  .dept-form-card {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0,0,0,.06);
    overflow: hidden;
  }
  .dept-form-card .card-header {
    background: linear-gradient(135deg, #20c997, #6f42c1);
    color: #fff;
    padding: 1rem 1.25rem;
  }
  .form-label {
    font-weight: 500;
    color: #495057;
  }
  .form-control, .form-select {
    border-radius: .6rem;
    padding: .6rem .9rem;
  }
  .btn-gradient {
    background: linear-gradient(135deg, #20c997, #6f42c1);
    border: 0;
    color: #fff;
    border-radius: .6rem;
    transition: all .3s ease;
  }
  .btn-gradient:hover {
    opacity: .9;
    transform: translateY(-2px);
  }
</style>

<div class="container my-4">
  <div class="card dept-form-card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="mb-0"><i class="fa-solid fa-plus-circle me-2"></i>Create Department</h5>
      <a href="{{ route('admin.departments.list') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-list me-1"></i>Department List</a>
    </div>

    <div class="card-body">
      <form action="{{ route('admin.departments.update', $department->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">

          <!-- Department Name -->
          <div class="col-md-6">
            <label class="form-label">Department Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="deptName" name="name" value="{{ $department->name }}" placeholder="Enter Department Name" required>
          </div>

          <!-- Department Code -->
          <div class="col-md-6">
            <label class="form-label">Department Code</label>
            <input type="text" class="form-control" id="deptCode" name="code" value="{{ $department->code }}" placeholder="Auto-generated" readonly>
          </div>

          <!-- Status -->
          <div class="col-md-6">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select class="form-select" value="{{ $department->status }}" name="status" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          <!-- Action Buttons -->
          <div class="col-12 d-flex justify-content-end gap-2 mt-3">
            <button type="reset" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left me-1"></i>Reset</button>
            <button type="submit" class="btn btn-gradient"><i class="fa-solid fa-save me-1"></i>Save Department</button>
          </div>

        </div>
      </form>
    </div>
  </div>
</div>

<script>
  const deptNameInput = document.getElementById('deptName');
  const deptCodeInput = document.getElementById('deptCode');

  deptNameInput.addEventListener('input', function() {
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
