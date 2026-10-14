@extends('layout.master')

@section('content')
<div class="container-fluid p-0">

  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Role Audit Log</h1>
      <p class="nx-sub">Read-only history of the changes recorded for this role</p>
    </div>
    <a href="{{ url()->previous() }}" class="btn btn-light">
      <i class="fas fa-arrow-left"></i> Back
    </a>
  </div>

  <div class="card" data-loading-target>
    <div class="table-responsive">
      <table class="table table-hover align-middle nx-table">
        <thead>
          <tr>
            <th scope="col">User</th>
            <th scope="col">Action</th>
            <th scope="col" class="d-none d-md-table-cell">Model</th>
            <th scope="col">Details</th>
            <th scope="col" class="d-none d-lg-table-cell">Date</th>
          </tr>
        </thead>
        <tbody>
          @forelse($activities as $activity)
            <tr>
              <td>
                <div class="nx-user">
                  <span class="nx-ico-chip"><i class="fas fa-user"></i></span>
                  <div class="nx-user-name">{{ $activity->causer?->name ?? 'System' }}</div>
                </div>
              </td>

              <td>
                <span class="badge
                  @if(str_contains(strtolower($activity->description), 'created')) bg-success
                  @elseif(str_contains(strtolower($activity->description), 'updated')) bg-info
                  @elseif(str_contains(strtolower($activity->description), 'deleted')) bg-danger
                  @else bg-secondary @endif">
                  {{ ucfirst($activity->description) }}
                </span>
              </td>

              <td class="d-none d-md-table-cell">{{ class_basename($activity->subject_type) }}</td>

              <td>
                <pre class="nx-json">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
              </td>

              <td class="d-none d-lg-table-cell">{{ $activity->created_at->format('d M, Y | h:i A') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="nx-empty">
                <i class="fas fa-clipboard-list"></i>
                <div class="nx-empty-title">No activity found</div>
                <div class="nx-empty-sub">Changes made to this role will be listed here.</div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if(method_exists($activities, 'links'))
      <div class="nx-footer-bar">
        <small class="text-muted">
          Showing <strong>{{ $activities->firstItem() ?? 0 }}-{{ $activities->lastItem() ?? 0 }}</strong>
          of <strong>{{ $activities->total() }}</strong> activities
        </small>
        @if($activities->hasPages()) {{ $activities->links('pagination::bootstrap-5') }} @endif
      </div>
    @endif
  </div>
</div>
@endsection
