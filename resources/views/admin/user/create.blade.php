@extends('layout.master')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid py-4">
  <div class="card shadow border-0">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Create User</h5>
      <a href="{{ route('admin.users.list') }}" class="btn btn-light btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Back
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" id="userForm">
        @csrf
        <div class="row g-4">

          <!-- Basic Info -->
          <h6 class="fw-bold text-primary mb-2">👤 Personal Information</h6>
          <hr class="mt-0 mb-3">

          <div class="col-md-4">
            <label class="form-label required">Full Name</label>
            <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Enter full name" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Email</label>
            <input type="email" name="email" class="form-control" placeholder="Enter email" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Contact Number</label>
            <input type="text" name="phone" class="form-control" placeholder="Enter phone number" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Gender</label>
            <select name="gender" class="form-select" required>
              <option value="">-- Select Gender --</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
            </select>
          </div>

          <!-- Login Info -->
          <h6 class="fw-bold text-primary mt-4 mb-2">🔐 Login Credentials</h6>
          <hr class="mt-0 mb-3">

          <div class="col-md-6">
            <label class="form-label required">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter password" required>
          </div>

          <div class="col-md-6">
            <label class="form-label required">Confirm Password</label>
            <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm password" required>
          </div>

          <!-- Employment Details -->
          <h6 class="fw-bold text-primary mt-4 mb-2">🏢 Employment Details</h6>
          <hr class="mt-0 mb-3">

          <div class="col-md-4">
            <label class="form-label required">Department</label>
            <select name="department_id" id="department_id" class="form-select" required>
              <option value="">-- Select Department --</option>
              @foreach($departments as $department)
                <option value="{{ $department->id }}" data-code="{{ $department->code }}">{{ $department->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Designation</label>
            <select name="designation_id" id="designation_id" class="form-select" required>
              <option value="">-- Select Designation --</option>
              @foreach($designations as $designation)
                <option value="{{ $designation->id }}" data-code="{{ $designation->code }}">{{ $designation->name }}</option>
              @endforeach
            </select>
          </div>



          <div class="col-md-4">
            <label class="form-label required">Role</label>
            <select name="role_id" id="role_id" class="form-select" required>
              <option value="">-- Select Role --</option>
              @foreach($roles as $role)
                <option value="{{ $role->id }}" data-name="{{ $role->name }}">{{ $role->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Employee ID</label>
            <input type="text" name="employee_id" id="employee_id" class="form-control" placeholder="Auto-generated" readonly required>
            <small class="text-muted">Auto-generated from department, designation, role & name</small>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Joining Date</label>
            <input type="date" name="join_date" class="form-control" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Status</label>
            <select name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Profile Picture</label>
            <input type="file" name="profile_photo" class="form-control" accept="image/*">
          </div>

        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-end mt-4">
          <button type="reset" class="btn btn-outline-secondary me-2">
            <i class="fas fa-undo"></i> Reset
          </button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Save User
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const fullNameInput = document.getElementById('full_name');
  const departmentSelect = document.getElementById('department_id');
  const designationSelect = document.getElementById('designation_id');
  const roleSelect = document.getElementById('role_id');
  const employeeIdInput = document.getElementById('employee_id');

  // Function to generate employee ID
  function generateEmployeeId() {
    const fullName = fullNameInput.value.trim();
    const deptOption = departmentSelect.options[departmentSelect.selectedIndex];
    const desigOption = designationSelect.options[designationSelect.selectedIndex];
    const roleOption = roleSelect.options[roleSelect.selectedIndex];

    // Check if all required fields are filled
    if (!fullName || !departmentSelect.value || !designationSelect.value || !roleSelect.value) {
      employeeIdInput.value = '';
      return;
    }

    // Get codes from data attributes
    const deptCode = deptOption.getAttribute('data-code') || '';
    const desigCode = desigOption.getAttribute('data-code') || '';
    const roleCode = roleOption.getAttribute('data-name') || '';

    // Generate initials from full name (first letter of each word)
    const nameInitials = fullName
      .split(' ')
      .map(word => word.charAt(0).toUpperCase())
      .join('');

    // Format: DEPT-DESIG-ROLE-INITIALS-RANDOM
    // Example: IT-DEV-ADM-JD-456
    let employeeId = '';
    
    if (deptCode) {
      employeeId += deptCode.substring(0, 4).toUpperCase() + '-';
    }
    
    if (desigCode) {
      employeeId += desigCode.substring(0, 4).toUpperCase() + '-';
    }
    
    if (roleCode) {
      employeeId += roleCode.substring(0, 3).toUpperCase() + '-';
    }
    
    if (nameInitials) {
      employeeId += nameInitials + '-';
    }
    
    employeeId += Math.floor(1000 + Math.random() * 9000);

    employeeIdInput.value = employeeId;
  }

  // Attach event listeners
  fullNameInput.addEventListener('input', generateEmployeeId);
  departmentSelect.addEventListener('change', generateEmployeeId);
  designationSelect.addEventListener('change', generateEmployeeId);
  roleSelect.addEventListener('change', generateEmployeeId);
});
</script>

@endsection
