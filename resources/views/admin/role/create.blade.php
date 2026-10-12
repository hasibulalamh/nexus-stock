@extends('layout.master')

@section('content')

<style>
  .role-card {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0,0,0,.06);
    overflow: hidden;
  }
  .role-card .card-header {
    background: linear-gradient(135deg, #6f42c1, #20c997);
    color: #fff;
    padding: 1rem 1.25rem;
  }
  .form-label {
    font-weight: 500;
  }
  .form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(111,66,193,.25);
    border-color: #6f42c1;
  }
  .btn-submit {
    background: linear-gradient(135deg, #6f42c1, #20c997);
    color: #fff;
    border: 0;
  }
  .btn-submit:hover {
    opacity: 0.9;
  }
</style>

<div class="container my-4">
  <div class="card role-card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="mb-0"><i class="fa-solid fa-user-shield me-2"></i>Create New Role</h5>
      <a href="{{ route('admin.roles.list') }}" class="btn btn-light btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i>Back to Roles
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('admin.roles.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
  <label for="name" class="form-label">Role Name</label>
  <input type="text" class="form-control" id="name" name="name" placeholder="Enter role name">
</div>

<div class="mb-3">
  <label for="icon_class" class="form-label">Role Icon</label>
  <input type="text" class="form-control iconpicker" id="icon_class" name="icon_class" placeholder="Select icon">
</div>

                  <!--perent id-->
   <div class="col-md-6">
    <label class="form-label">Parent Role</label>
      <select name="parent_id" class="form-select">
        <option value="">— None —</option>
          @foreach($roles as $role)
           <option value="{{ $role->id }}">{{ $role->name }}</option>
             @endforeach
              </select>
                </div>

        <!--role status-->
        <div class="mb-3">
          <label for="status" class="form-label">Status</label>
          <select class="form-select" id="status" name="status" required>
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <button type="submit" class="btn btn-submit">
          <i class="fa-solid fa-plus me-1"></i>Create Role
        </button>
      </form>
    </div>
  </div>
</div>
<!-- FontAwesome CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Icon Picker CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.2.0/css/fontawesome-iconpicker.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- Icon Picker JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.2.0/js/fontawesome-iconpicker.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize the icon picker
    $('.iconpicker').iconpicker({
        placement: 'bottom',
        hideOnSelect: true,
        inputSearch: true,
        templates: {
            searchInput: '<input type="search" class="form-control iconpicker-search" placeholder="Search icons">'
        }
    });

    // Role name -> icon mapping with more comprehensive icon options
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

    // Function to find matching icon for role name
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

    // When typing role name → auto icon fill with debounce
    let typingTimer;
    $('#name').on('input', function() {
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
    $('#name').on('blur', function() {
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
