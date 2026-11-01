@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Administration</small>Areas</h1><p class="nx-sub">Manage searchable city areas.</p></div>
    <div class="d-flex gap-2">
      @can('areas.import')<a href="{{ route('admin.areas.import') }}" class="btn btn-light">Import CSV</a>@endcan
      @can('areas.create')<a href="{{ route('admin.areas.create') }}" class="btn btn-primary">Add area</a>@endcan
    </div>
  </div>
  <div class="card">
    <div class="table-responsive"><table class="table table-hover align-middle nx-table"><thead><tr><th>Area</th><th>City</th><th>Type</th><th>Verified</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
      @forelse($areas as $area)
        <tr><td>{{ $area->display_name }}</td><td>{{ $area->city->name }}</td><td>{{ $area->type ?: '-' }}</td><td>{{ $area->verified ? 'Yes' : 'No' }}</td><td>{{ ucfirst($area->status) }}</td><td class="text-end">
          @can('areas.edit')<a class="btn btn-sm btn-light" href="{{ route('admin.areas.edit', $area) }}">Edit</a>@endcan
          @can('areas.delete')<form class="d-inline" method="POST" action="{{ route('admin.areas.delete', $area) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>@endcan
        </td></tr>
      @empty <tr><td colspan="6" class="nx-empty">No areas found.</td></tr>@endforelse
    </tbody></table></div>
    <div class="nx-footer-bar">{{ $areas->links('pagination::bootstrap-5') }}</div>
  </div>
</div>
@endsection
