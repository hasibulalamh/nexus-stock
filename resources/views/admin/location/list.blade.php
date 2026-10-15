@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Locations</h1>
      <p class="nx-sub">Imported country, state, city and upazila records</p>
    </div>
    @can('locations.create')
      <a href="{{ route('admin.locations.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Import locations
      </a>
    @endcan
  </div>

  <div class="card" data-loading-target>
    {{-- locations are imported from CSV and toggled by status; there is no edit/delete route or permission --}}
    <div class="table-responsive">
      <table class="table table-hover align-middle nx-table">
        <thead>
          <tr>
            <th scope="col" class="d-none d-lg-table-cell">Country</th>
            <th scope="col" class="d-none d-lg-table-cell">State</th>
            <th scope="col">City</th>              <th scope="col" class="d-none d-md-table-cell">Upazila</th>
            <th scope="col">Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($locations as $location)
            <tr id="row-{{ $location->id }}">
              <td class="d-none d-lg-table-cell">{{ $location->country->name }}</td>
              <td class="d-none d-lg-table-cell">{{ $location->state->name }}</td>
              <td><div class="nx-user-name">{{ $location->city->name }}</div></td>
              <td class="d-none d-md-table-cell">{{ $location->upazila->name }}</td>

              <td>
                <button type="button"
                        class="nx-status-toggle toggle-status-btn {{ $location->status == 'active' ? 'btn-success' : 'btn-danger' }}"
                        data-id="{{ $location->id }}"
                        aria-label="Toggle status for {{ $location->city->name }}">
                  {{ ucfirst($location->status) }}
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="nx-empty">
                <i class="fas fa-map-location-dot"></i>
                <div class="nx-empty-title">No locations found</div>
                <div class="nx-empty-sub">Import a CSV file to load location data.</div>
                @can('locations.create')
                  <a href="{{ route('admin.locations.create') }}" class="btn btn-light btn-sm mt-3">
                    <i class="fas fa-file-upload"></i> Import locations
                  </a>
                @endcan
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="nx-footer-bar">
      <small class="text-muted">
        Showing <strong>{{ $locations->firstItem() ?? 0 }}-{{ $locations->lastItem() ?? 0 }}</strong>
        of <strong>{{ $locations->total() }}</strong> locations
      </small>
      @if($locations->hasPages()) {{ $locations->links('pagination::bootstrap-5') }} @endif
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
$(document).on('click', '.toggle-status-btn', function () {
    let button = $(this);
    let id = button.data('id');

    $.ajax({
        url: "{{ route('admin.locations.changeStatus') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            id: id
        },
        success: function (response) {
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
        error: function () {
            alert('Server error!');
        }
    });
});
</script>
@endsection
