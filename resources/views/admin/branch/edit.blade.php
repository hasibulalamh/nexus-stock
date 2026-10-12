@extends('layout.master')

@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid py-4">
  <div class="card shadow border-0">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fas fa-shop me-2"></i>Edit Branch</h5>
      <a href="{{ route('admin.branch.list') }}" class="btn btn-light btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('admin.branch.update', $branch->id) }}" method="POST" id="branchForm">
        @csrf
        @method('PUT')

        <div class="row g-4">

          <!-- Warehouse Information -->
          <h6 class="fw-bold text-primary mb-2">🏢 Branch Information</h6>
          <hr class="mt-0 mb-3">

          <div class="col-md-4">
            <label class="form-label required"> Branch Name</label>
            <input type="text" id="branch_name" name="name" class="form-control" 
                   value="{{ old('name', $branch->name) }}" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required"> Branch Code</label>
            <input type="text" id="branch_code" name="code" class="form-control" 
                   value="{{ old('code', $branch->code) }}" readonly required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Branch Slug</label>
            <input type="text" id="branch_slug" name="slug" class="form-control" 
                   value="{{ old('slug', $branch->slug) }}" readonly>
          </div>

          <!-- Location Details -->
          <h6 class="fw-bold text-primary mt-4 mb-2">📍 Location Details</h6>
          <hr class="mt-0 mb-3">

          <div class="col-md-4">
            <label class="form-label required">Country</label>
            <select id="country" name="country_id" class="form-select" required>
              <option value="">-- Select Country --</option>
              @foreach($countries as $country)
                <option value="{{ $country->id }}" 
                        {{ $branch->location->country_id == $country->id ? 'selected' : '' }}>
                        {{ $country->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label required">State</label>
            <select id="state" name="state_id" class="form-select" required>
              @if($branch->location->state)
                <option value="{{ $branch->location->state_id }}">
                  {{ $branch->location->state->name }}
                </option>
              @else
                <option value="">-- Select State --</option>
              @endif
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label required">City</label>
            <select id="city" name="city_id" class="form-select" required>
              @if($branch->location->city)
                <option value="{{ $branch->location->city_id }}">
                  {{ $branch->location->city->name }}
                </option>
              @else
                <option value="">-- Select City --</option>
              @endif
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Thana</label>
            <select id="thana" name="thana_id" class="form-select" required>
              @if($branch->location->thana)
                <option value="{{ $branch->location->thana_id }}">
                  {{ $branch->location->thana->name }}
                </option>
              @else
                <option value="">-- Select Thana --</option>
              @endif
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Union</label>
            <select id="union" name="union_id" class="form-select" required>
              @if($branch->location->union)
                <option value="{{ $branch->location->union_id }}">
                  {{ $branch->location->union->name }}
                </option>
              @else
                <option value="">-- Select Union --</option>
              @endif
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Zipcode</label>
            <input type="text" id="zipcode" name="zipcode" 
                   class="form-control" 
                   value="{{ $branch->location->union->zipcode ?? '' }}" readonly>
          </div>

          <!-- Operational Details -->
          <h6 class="fw-bold text-primary mt-4 mb-2">⚙️ Operational Details</h6>
          <hr class="mt-0 mb-3">

          <div class="col-md-4">
            <label class="form-label required">Capacity</label>
            <input type="number" name="capacity" class="form-control" 
                   value="{{ old('capacity', $branch->capacity) }}" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Opening Date</label>
            <input type="date" name="starting_date" class="form-control" 
                   value="{{ old('starting_date', $branch->starting_date) }}" required>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Status</label>
            <select name="status" class="form-select" required>
              <option value="active" {{ $branch->status == 'active' ? 'selected' : '' }}>Active</option>
              <option value="inactive" {{ $branch->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>

        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-end mt-4">
          <button type="reset" class="btn btn-outline-secondary me-2">
            <i class="fas fa-undo"></i> Reset
          </button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Update Warehouse
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<!-- JS -->
<script src="{{ asset('assets/js/location.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  initLocationSelector({
    countryId: 'country',
    stateId: 'state',
    cityId: 'city',
    thanaId: 'thana',
    unionId: 'union',
    zipcodeId: 'zipcode',
    baseUrl: '{{ url("admin/branch") }}'
  });

  // Auto-update code & slug when name changes
  const nameInput = document.getElementById('branch_name');
  nameInput.addEventListener('input', function() {
    const name = this.value.trim();
    document.getElementById('branch_code').value = name.toUpperCase().replace(/\s+/g, '_').substring(0, 10);
    document.getElementById('branch_slug').value = name.toLowerCase().replace(/\s+/g, '-');
  });
});
</script>

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

@endsection
