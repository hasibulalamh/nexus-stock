@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <h1 class="h3 nx-title mb-0"><small>Administration</small>Create User</h1>
    <a href="{{ route('admin.users.list') }}" class="btn btn-light">
      <i class="fas fa-arrow-left me-1"></i> Back
    </a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger">
      <strong><i class="fas fa-triangle-exclamation me-1"></i> Please fix the following:</strong>
      <ul class="mb-0 mt-1">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="card-body p-4">
      <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" id="userForm">
        @csrf
        <div class="row g-4">

          <div class="col-12">
            <div class="nx-section" style="--c:#14b8a6"><i class="fas fa-user"></i> Personal Information</div>
            <hr>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Full Name</label>
            <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}"
                   class="form-control @error('full_name') is-invalid @enderror" placeholder="Enter full name" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror" placeholder="name@company.com" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Contact Number</label>
            <input type="text" name="phone" value="{{ old('phone') }}"
                   class="form-control @error('phone') is-invalid @enderror" placeholder="Enter phone number" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Gender</label>
            <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
              <option value="">-- Select Gender --</option>
              <option value="male" @selected(old('gender') === 'male')>Male</option>
              <option value="female" @selected(old('gender') === 'female')>Female</option>
            </select>
          </div>

          <div class="col-12 mt-4">
            <div class="nx-section" style="--c:#f43f5e"><i class="fas fa-lock"></i> Login Credentials</div>
            <hr>
          </div>

          <div class="col-md-6">
            <label class="form-label required">Password</label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                   placeholder="Enter password" autocomplete="new-password" required>
          </div>

          <div class="col-md-6">
            <label class="form-label required">Confirm Password</label>
            <input type="password" name="password_confirmation" class="form-control"
                   placeholder="Confirm password" autocomplete="new-password" required>
          </div>

          <div class="col-12 mt-4">
            <div class="nx-section" style="--c:#f97316"><i class="fas fa-building"></i> Employment Details</div>
            <hr>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Department</label>
            <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
              <option value="">-- Select Department --</option>
              @foreach($departments as $department)
                <option value="{{ $department->id }}" data-code="{{ $department->code }}"
                        @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Designation</label>
            <select name="designation_id" id="designation_id" class="form-select @error('designation_id') is-invalid @enderror" required>
              <option value="">-- Select Designation --</option>
              @foreach($designations as $designation)
                <option value="{{ $designation->id }}" data-code="{{ $designation->code }}"
                        @selected(old('designation_id') == $designation->id)>{{ $designation->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Role</label>
            <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
              <option value="">-- Select Role --</option>
              @foreach($roles as $role)
                <option value="{{ $role->id }}" data-name="{{ $role->name }}"
                        @selected(old('role_id') == $role->id)>{{ $role->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Employee ID</label>
            <input type="text" name="employee_id" id="employee_id" value="{{ old('employee_id') }}"
                   class="form-control" placeholder="Auto-generated" readonly required>
            <small class="text-muted">Auto-generated from department, designation, role and name</small>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Joining Date</label>
            <input type="date" name="join_date" value="{{ old('join_date') }}" class="form-control" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Status</label>
            <select name="status" class="form-select" required>
              <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
              <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Profile Picture</label>
            <input type="file" name="profile_photo" class="form-control" accept="image/*">
          </div>

        </div>

        <div class="d-flex justify-content-end gap-2 mt-4 pt-3" style="border-top:2px dashed var(--nx-line)">
          <button type="reset" class="btn btn-outline-secondary">
            <i class="fas fa-rotate-left me-1"></i> Reset
          </button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-floppy-disk me-1"></i> Save User
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const fullName = document.getElementById('full_name');
  const dept = document.getElementById('department_id');
  const desig = document.getElementById('designation_id');
  const role = document.getElementById('role_id');
  const out = document.getElementById('employee_id');

  function generate() {
    const name = fullName.value.trim();
    if (!name || !dept.value || !desig.value || !role.value) { out.value = ''; return; }

    const deptCode = dept.options[dept.selectedIndex].getAttribute('data-code') || '';
    const desigCode = desig.options[desig.selectedIndex].getAttribute('data-code') || '';
    const roleName = role.options[role.selectedIndex].getAttribute('data-name') || '';
    const initials = name.split(' ').filter(Boolean).map(w => w.charAt(0).toUpperCase()).join('');

    let id = '';
    if (deptCode) id += deptCode.substring(0, 4).toUpperCase() + '-';
    if (desigCode) id += desigCode.substring(0, 4).toUpperCase() + '-';
    if (roleName) id += roleName.substring(0, 3).toUpperCase() + '-';
    if (initials) id += initials + '-';
    id += Math.floor(1000 + Math.random() * 9000);
    out.value = id;
  }

  [fullName].forEach(el => el.addEventListener('input', generate));
  [dept, desig, role].forEach(el => el.addEventListener('change', generate));
});
</script>
@endsection
