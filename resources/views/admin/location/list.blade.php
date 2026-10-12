@extends('layout.master')

@section('content')

<style>
  .list-card {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0,0,0,.06);
    overflow: hidden;
  }
  .list-card .card-header {
    background: linear-gradient(135deg, #6f42c1, #20c997);
    color: #fff;
    padding: 1rem 1.25rem;
  }
  .table thead th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: .8rem;
    background: #f8f9fa;
    border-bottom: 0;
  }
  .table-hover tbody tr:hover {
    background: #fbfbff;
  }
  .status-dot {
    width: .6rem; height: .6rem; border-radius: 50%;
    display: inline-block; margin-right: .4rem;
  }
  .dot-active { background: #28a745; }
  .dot-inactive { background: #6c757d; }
  .action-btns .btn {
    border-radius: 50%;
  }
</style>

<div class="container my-4">
  <div class="card list-card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fa-solid fa-warehouse me-2"></i>Location List</h5>
      <a href="{{ route('admin.locations.create') }}" class="btn btn-light btn-sm">
        <i class="fa-solid fa-plus me-1"></i>Create Location
      </a>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
          <thead>
            <tr>
              <th>Country</th>
              <th>State</th>
              <th>City</th>
              <th>Thana</th>
              <th>union</th>
              <th>Zip Code</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($locations as $location)
              <tr id="row-{{ $location->id }}">
                <td>{{ $location->country->name }}</td>
                <td>{{ $location->state->name }}</td>
                <td>{{ $location->city->name }}</td>
                <td>{{ $location->thana->name }}</td>
                <td>{{ $location->union->name }}</td>
                <td>{{ $location->union->zipcode }}</td>
                <td>
                <button class="btn btn-sm toggle-status-btn {{ $location->status == 'active' ? 'btn-success' : 'btn-danger' }}"
                data-id="{{ $location->id }}">
            {{ ucfirst($location->status) }}
        </button>
                </td>
                <td class="text-end action-btns">
                  <a href="" 
                     class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" title="Edit">
                    <i class="fa-regular fa-pen-to-square"></i>
                  </a>
                  <form action="" 
                        method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm" 
                            onclick="return confirm('Are you sure to delete this location?')" 
                            data-bs-toggle="tooltip" title="Delete">
                      <i class="fa-regular fa-trash-can"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-4">No locations found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="p-3 d-flex justify-content-between align-items-center">
        <small class="text-muted">
          Showing {{ $locations->firstItem() ?? 0 }} - {{ $locations->lastItem() ?? 0 }} of {{ $locations->total() }} locations
        </small>
        <div>
          {{ $locations->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap tooltip -->
<script>
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).on('click', '.toggle-status-btn', function() {
    let button = $(this);
    let id = button.data('id');

    $.ajax({
        url: "{{ route('admin.locations.changeStatus') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            id: id
        },
        success: function(response) {
            if (response.success) {
                if (response.status === 'active') {
                    button.removeClass('btn-danger').addClass('btn-success').text('Active');
                } else {
                    button.removeClass('btn-success').addClass('btn-danger').text('Inactive');
                }
            } else {
                alert('Something went wrong!');
            }
        },
        error: function() {
            alert('Server error!');
        }
    });
});
</script>


@endsection
