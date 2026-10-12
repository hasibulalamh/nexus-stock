@extends('layout.master')

@section('content')
<div class="container-fluid py-4">
  <div class="card shadow border-0">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Role Audit Log</h5>
      <a href="{{ url()->previous() }}" class="btn btn-light btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Back
      </a>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="bg-light">
            <tr>
              <th>User</th>
              <th>Action</th>
              <th>Model</th>
              <th>Details</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            @forelse($activities as $activity)
            <tr>
              <td class="fw-semibold">
                <i class="fas fa-user-circle text-primary me-1"></i>
                {{ $activity->causer?->name ?? 'System' }}
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
              <td>{{ class_basename($activity->subject_type) }}</td>
              <td>
                <pre class="json-box">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
              </td>
              <td>{{ $activity->created_at->format('d M, Y | h:i A') }}</td>
            </tr>
            @empty
            <tr>
              <td colspan="5" class="text-center text-muted py-4">
                <i class="fas fa-info-circle me-1"></i> No activities found
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if(method_exists($activities, 'links'))
      <div class="d-flex justify-content-end mt-3">
        {{ $activities->links('pagination::bootstrap-5') }}
      </div>
      @endif
    </div>
  </div>
</div>

<style>
.bg-gradient-primary {
  background: linear-gradient(135deg, #20c997, #6f42c1);
}
.table th {
  font-weight: 600;
  background-color: #f8f9fa;
}
.table td {
  vertical-align: middle;
}
.json-box {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  padding: 8px 10px;
  font-size: 0.85rem;
  color: #495057;
  max-height: 180px;
  overflow-y: auto;
}
.badge {
  font-size: 0.8rem;
  padding: 0.4em 0.65em;
  border-radius: 0.5rem;
}
.table-hover tbody tr:hover {
  background-color: #f3f0ff;
}
</style>
@endsection
