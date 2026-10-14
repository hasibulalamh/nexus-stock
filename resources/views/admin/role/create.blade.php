@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Create Role</h1>
      <p class="nx-sub">Define a role, its icon and its place in the hierarchy</p>
    </div>
    <a href="{{ route('admin.roles.list') }}" class="btn btn-light">
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
      <form action="{{ route('admin.roles.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-3">

          <div class="col-12">
            <div class="nx-section"><i class="fas fa-user-shield"></i> Role Details</div>
            <hr>
          </div>

          <div class="col-12 col-md-6">
            <label for="name" class="form-label required">Role Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                   name="name" value="{{ old('name') }}" placeholder="Enter role name" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label for="icon_class" class="form-label required">Role Icon</label>
            <input type="text" class="form-control iconpicker @error('icon_class') is-invalid @enderror"
                   id="icon_class" name="icon_class" value="{{ old('icon_class') }}" placeholder="Select icon" required>
            <small class="text-muted">Picked automatically from the role name — click to choose another</small>
            @error('icon_class')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label for="parent_id" class="form-label">Parent Role</label>
            <select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
              <option value="">— None —</option>
              @foreach($roles as $parent)
                <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>{{ $parent->name }}</option>
              @endforeach
            </select>
            @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-6">
            <label for="status" class="form-label required">Status</label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
              <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
              <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

        </div>

        <div class="nx-form-actions">
          <a href="{{ route('admin.roles.list') }}" class="btn btn-light">
            <i class="fas fa-xmark"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-floppy-disk"></i> Create Role
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    // Initialize the icon picker
    $('.iconpicker').iconpicker({
        placement: 'bottom',
        hideOnSelect: true,
        inputSearch: true,
        templates: {
            searchInput: '<input type="search" class="form-control iconpicker-search" placeholder="Search icons">'
        }
    });

    // Role name -> icon mapping
    const roleIcons = [
        { pattern: /super\s*admin/i, icon: 'fa-solid fa-crown' },
        { pattern: /admin/i, icon: 'fa-solid fa-user-shield' },
        { pattern: /warehouse/i, icon: 'fa-solid fa-warehouse' },
        { pattern: /inventory/i, icon: 'fa-solid fa-boxes-stacked' },
        { pattern: /sales\s*manager/i, icon: 'fa-solid fa-chart-line' },
        { pattern: /sales\s*(executive|rep|representative)/i, icon: 'fa-solid fa-user-tie' },
        { pattern: /account(s|ing)?\s*manager/i, icon: 'fa-solid fa-calculator' },
        { pattern: /accountant/i, icon: 'fa-solid fa-file-invoice-dollar' },
        { pattern: /hr\s*manager|human\s*resource\s*manager/i, icon: 'fa-solid fa-people-group' },
        { pattern: /hr\s*executive|human\s*resource/i, icon: 'fa-solid fa-user-group' },
        { pattern: /it\s*manager/i, icon: 'fa-solid fa-laptop-code' },
        { pattern: /it\s*support|technician/i, icon: 'fa-solid fa-headset' },
        { pattern: /stock\s*(keeper|manager|clerk)/i, icon: 'fa-solid fa-boxes-stacked' },
        { pattern: /customer\s*service|support/i, icon: 'fa-solid fa-headset' },
        { pattern: /manager/i, icon: 'fa-solid fa-user-tie' },
        { pattern: /director/i, icon: 'fa-solid fa-user-tie' },
        { pattern: /executive/i, icon: 'fa-solid fa-user-tie' },
        { pattern: /officer/i, icon: 'fa-solid fa-user' },
        { pattern: /assistant/i, icon: 'fa-solid fa-user' },
        { pattern: /staff/i, icon: 'fa-solid fa-user' }
    ];

    // Find the matching icon for a role name
    function findMatchingIcon(roleName) {
        if (!roleName) return null;

        // Convert to lowercase for case-insensitive matching
        const searchName = roleName.toLowerCase().trim();

        // First try exact matches
        const exactMatch = roleIcons.find(item =>
            searchName === item.pattern.source.replace(/[^\w\s]/g, '').toLowerCase()
        );
        if (exactMatch) return exactMatch.icon;

        // Then try pattern matching
        const patternMatch = roleIcons.find(item => item.pattern.test(searchName));
        if (patternMatch) return patternMatch.icon;

        // Default icon if no match found
        return 'fa-solid fa-user';
    }

    // When typing role name -> auto icon fill with debounce
    let typingTimer;
    $('#name').on('input', function () {
        clearTimeout(typingTimer);
        const roleName = $(this).val();

        typingTimer = setTimeout(() => {
            const icon = findMatchingIcon(roleName);
            if (icon) {
                $('#icon_class').val(icon).trigger('change');
                $('.iconpicker').iconpicker('setIcon', icon);
            }
        }, 300); // 300ms debounce
    });

    // Also trigger icon update when the field loses focus
    $('#name').on('blur', function () {
        const roleName = $(this).val();
        const icon = findMatchingIcon(roleName);
        if (icon && !$('#icon_class').val()) {
            $('#icon_class').val(icon).trigger('change');
            $('.iconpicker').iconpicker('setIcon', icon);
        }
    });
});
</script>
@endsection
