@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <h1 class="h3 nx-title mb-0"><small>Administration</small>Edit User</h1>
    <a href="{{ route('admin.users.list') }}" class="btn btn-light">
      <i class="fas fa-arrow-left"></i> Back
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
      <form action="{{ route('admin.users.update', $user->id) }}" method="POST" enctype="multipart/form-data" id="userForm">
        @csrf
        @method('PUT')
        <div class="row g-4">

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-user"></i> Personal Information</div>
            <hr>
          </div>

          <div class="col-md-4">
            <label class="form-label required" for="full_name">Full Name</label>
            <input type="text" name="full_name" id="full_name" value="{{ old('full_name', $user->full_name) }}"
                   class="form-control @error('full_name') is-invalid @enderror" placeholder="Enter full name" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required" for="email">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                   class="form-control @error('email') is-invalid @enderror" placeholder="name@company.com" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required" for="phone">Contact Number</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                   class="form-control @error('phone') is-invalid @enderror" placeholder="Enter phone number" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required" for="gender">Gender</label>
            <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror" required>
              <option value="">-- Select Gender --</option>
              <option value="male" @selected(old('gender', $user->gender) === 'male')>Male</option>
              <option value="female" @selected(old('gender', $user->gender) === 'female')>Female</option>
            </select>
          </div>

          <div class="col-12 mt-4">
            <div class="nx-section"><i class="fas fa-lock"></i> Login Credentials</div>
            <hr>
          </div>

          <div class="col-12">
            <small class="text-muted d-inline-flex align-items-center gap-2">
              <i class="fas fa-circle-info"></i> Leave the password fields blank to keep the current password.
            </small>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="password">New Password</label>
            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror"
                   placeholder="Enter new password" autocomplete="new-password">
          </div>

          <div class="col-md-6">
            <label class="form-label" for="password_confirmation">Confirm New Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                   placeholder="Confirm new password" autocomplete="new-password">
          </div>

          <div class="col-12 mt-4">
            <div class="nx-section"><i class="fas fa-building"></i> Employment Details</div>
            <hr>
          </div>

          <div class="col-md-4">
            <label class="form-label required" for="department_id">Department</label>
            <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
              <option value="">-- Select Department --</option>
              @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $user->department_id) == $department->id)>{{ $department->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label required" for="designation_id">Designation</label>
            <select name="designation_id" id="designation_id" class="form-select @error('designation_id') is-invalid @enderror" required>
              <option value="">-- Select Designation --</option>
              @foreach($designations as $designation)
                <option value="{{ $designation->id }}" @selected(old('designation_id', $user->designation_id) == $designation->id)>{{ $designation->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label required" for="role_id">Role</label>
            <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
              <option value="">-- Select Role --</option>
              @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id ?? $user->roles->first()?->id) == $role->id)>{{ $role->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <div class="form-label">Employee ID</div>
            <div class="nx-static">{{ $user->employee_id }}</div>
            <small class="text-muted">Generated when the user was created and cannot be changed.</small>
          </div>

          <div class="col-md-4">
            <label class="form-label required" for="join_date">Joining Date</label>
            <input type="date" name="join_date" id="join_date"
                   value="{{ old('join_date', $user->join_date ? \Illuminate\Support\Carbon::parse($user->join_date)->format('Y-m-d') : '') }}"
                   class="form-control @error('join_date') is-invalid @enderror" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required" for="status">Status</label>
            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
              <option value="active" @selected(old('status', $user->status) === 'active')>Active</option>
              <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactive</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="profile_photo">Profile Picture</label>
            <div class="d-flex align-items-center gap-3">
              <img src="{{ $user->profile_photo_url }}" alt="" class="nx-avatar">
              <input type="file" name="profile_photo" id="profile_photo"
                     class="form-control @error('profile_photo') is-invalid @enderror" accept="image/*">
            </div>
            <small class="text-muted">Leave empty to keep the current picture.</small>
          </div>

          <div class="col-12 mt-4">
            <div class="nx-section"><i class="fas fa-shield-halved"></i> Access</div>
            <hr>
          </div>

          @if($user->id === auth()->id())
            <div class="col-12">
              <small class="text-muted d-inline-flex align-items-center gap-2">
                <i class="fas fa-circle-info"></i> You cannot change your own branch and warehouse access.
              </small>
            </div>
          @else
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input nx-check-input" type="checkbox" name="access_all" value="1"
                       id="access_all" @checked(old('access_all', $user->access_all))>
                <label class="form-check-label" for="access_all">Access all branches and warehouses</label>
              </div>
            </div>

            <div class="col-md-6" data-access-block>
              <label class="form-label" for="branch_ids">Branches</label>
              <select name="branch_ids[]" id="branch_ids" class="form-select @error('branch_ids') is-invalid @enderror"
                      multiple size="6" aria-label="Branches">
                @foreach($branches as $branch)
                  <option value="{{ $branch->id }}"
                          @selected(in_array($branch->id, old('branch_ids', $user->branches()->pluck('branches.id')->all())))>{{ $branch->name }}</option>
                @endforeach
              </select>
              @error('branch_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              <small class="text-muted">Hold Ctrl/Cmd to select multiple. Empty means no branch access.</small>
            </div>

            <div class="col-md-6" data-access-block>
              <label class="form-label" for="warehouse_ids">Warehouses</label>
              <select name="warehouse_ids[]" id="warehouse_ids" class="form-select @error('warehouse_ids') is-invalid @enderror"
                      multiple size="6" aria-label="Warehouses">
                @foreach($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}"
                          @selected(in_array($warehouse->id, old('warehouse_ids', $user->warehouses()->pluck('warehouses.id')->all())))>{{ $warehouse->name }}</option>
                @endforeach
              </select>
              @error('warehouse_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              <small class="text-muted">Hold Ctrl/Cmd to select multiple. Empty means no warehouse access.</small>
            </div>
          @endif

        </div>

        <div class="nx-form-actions">
          <button type="reset" class="btn btn-outline-secondary">
            <i class="fas fa-rotate-left"></i> Reset
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-floppy-disk"></i> Update User
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
  // Access section: hide and disable the pickers while "access all" is checked.
  const accessAll = document.getElementById('access_all');
  if (!accessAll) return;

  const blocks = document.querySelectorAll('[data-access-block]');

  function toggleAccess() {
    blocks.forEach(function (block) {
      block.classList.toggle('d-none', accessAll.checked);
      block.querySelectorAll('select').forEach(function (el) { el.disabled = accessAll.checked; });
    });
  }

  accessAll.addEventListener('change', toggleAccess);
  toggleAccess();
});
</script>
@endsection
