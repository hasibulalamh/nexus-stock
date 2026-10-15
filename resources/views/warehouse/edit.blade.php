@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Modules</small>Edit Warehouse</h1>
      <p class="nx-sub">Update the warehouse details and address</p>
    </div>
    <a href="{{ route('admin.warehouse.list') }}" class="btn btn-light">
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
      <form action="{{ route('admin.warehouse.update', $warehouse->id) }}" method="POST" id="warehouseForm">
        @csrf
        @method('PUT')

        <div class="row g-3">

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-warehouse"></i> Warehouse Information</div>
            <hr>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="warehouse_name">Warehouse Name</label>
            <input type="text" id="warehouse_name" name="name" value="{{ old('name', $warehouse->name) }}"                   class="form-control @error('name') is-invalid @enderror" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="warehouse_code">Warehouse Code</label>
            <input type="text" id="warehouse_code" name="code" value="{{ old('code', $warehouse->code) }}"                   class="form-control @error('code') is-invalid @enderror" readonly required>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label" for="warehouse_slug">Slug</label>
            <input type="text" id="warehouse_slug" name="slug" value="{{ old('slug', $warehouse->slug) }}"                   class="form-control @error('slug') is-invalid @enderror" readonly>
            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-location-dot"></i> Location Details</div>
            <hr>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="country">Country</label>
            <select id="country" name="country_id" class="form-select @error('country_id') is-invalid @enderror" required>
              <option value="">-- Select Country --</option>
              @foreach($countries as $country)
                <option value="{{ $country->id }}" @selected(old('country_id', $warehouse->location->country_id) == $country->id)
                  {{ $country->name }}
                </option>
              @endforeach
            </select>
            @error('country_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="state">State</label>
            <select id="state" name="state_id" class="form-select @error('state_id') is-invalid @enderror" required>
              @if($warehouse->location->state)
                <option value="{{ $warehouse->location->state_id }}">{{ $warehouse->location->state->name }}</option>
              @else
                <option value="">-- Select State --</option>
              @endif
            </select>
            @error('state_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="city">City</label>
            <select id="city" name="city_id" class="form-select @error('city_id') is-invalid @enderror" required>
              @if($warehouse->location->city)
                <option value="{{ $warehouse->location->city_id }}">{{ $warehouse->location->city->name }}</option>
              @else
                <option value="">-- Select City --</option>
              @endif
            </select>
            @error('city_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="upazila">Upazila</label>
            <select id="upazila" name="upazila_id" class="form-select @error('upazila_id') is-invalid @enderror" required>
              @if($warehouse->location->upazila)
                <option value="{{ $warehouse->location->upazila_id }}">{{ $warehouse->location->upazila->name }}</option>
              @else
                <option value="">-- Select Upazila --</option>
              @endif
            </select>
            @error('upazila_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-sliders"></i> Operational Details</div>
            <hr>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="capacity">Capacity</label>
            <input type="number" id="capacity" name="capacity" value="{{ old('capacity', $warehouse->capacity) }}"                   class="form-control @error('capacity') is-invalid @enderror" required>
            @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="starting_date">Opening Date</label>
            <input type="date" id="starting_date" name="starting_date"
                   value="{{ old('starting_date', $warehouse->starting_date) }}"
                   class="form-control @error('starting_date') is-invalid @enderror" required>
            @error('starting_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label required" for="status">Status</label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
              <option value="active" @selected(old('status', $warehouse->status) == 'active')>Active</option>
              <option value="inactive" @selected(old('status', $warehouse->status) == 'inactive')>Inactive</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label" for="branch">Branch</label>
            <select id="branch" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
              <option value="">-- Select Branch --</option>
              @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}
                  {{ $warehouse->branch_id == $branch->id ? 'data-selected' : '' }}>
                  {{ $branch->name }}
                </option>
              @endforeach
            </select>
            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

        </div>

        <div class="nx-form-actions">
          <button type="reset" class="btn btn-outline-secondary">
            <i class="fas fa-rotate-left"></i> Reset
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-floppy-disk"></i> Update Warehouse
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/location.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  initLocationSelector({
    countryId: 'country',
    stateId: 'state',
    cityId: 'city',
    upazilaId: 'upazila',
    baseUrl: '{{ url("admin/warehouse") }}'
  });

  // Auto-update code & slug when name changes
  const nameInput = document.getElementById('warehouse_name');
  nameInput.addEventListener('input', function () {
    const name = this.value.trim();
    document.getElementById('warehouse_code').value = name.toUpperCase().replace(/\s+/g, '_').substring(0, 10);
    document.getElementById('warehouse_slug').value = name.toLowerCase().replace(/\s+/g, '-');
  });

  // Initialize branch select
  const branchSelect = document.getElementById('branch');
  // Optionally pre-select the warehouse's branch
  const selectedBranch = branchSelect.options[branchSelect.selectedIndex];
  // Highlight the currently selected branch
  {% if $warehouse->branch_id %}
    // Set the selected option to match the warehouse's branch
    for (let i = 0; i < branchSelect.options.length; i++) {
      if (branchSelect.options[i].value == {{ $warehouse->branch_id }}) {
        branchSelect.selectedIndex = i;
        break;
      }
    }
  {% endif %}
});
</script>
@endsection