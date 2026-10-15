@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Administration</small>Activity log</h1>
      <p class="nx-sub">Read-only history of authenticated changes</p>
    </div>
  </div>

  <div class="card" data-loading-target>
    <form method="GET" action="{{ route('admin.activity-log') }}" class="nx-toolbar" data-filter>
      <select name="user_id" class="form-select" aria-label="Filter by user">
        <option value="">All users</option>
        @foreach($users as $user)
          <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->full_name }}</option>
        @endforeach
      </select>
      <select name="module" class="form-select" aria-label="Filter by module">
        <option value="">All modules</option>
        @foreach($modules as $module)
          <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
        @endforeach
      </select>
      <input type="date" name="from" class="form-control" value="{{ request('from') }}" aria-label="From date">
      <input type="date" name="to" class="form-control" value="{{ request('to') }}" aria-label="To date">
      <button type="submit" class="btn btn-light"><i class="fas fa-filter"></i> Apply</button>
      @if(request()->hasAny(['user_id', 'module', 'from', 'to']))
        <a href="{{ route('admin.activity-log') }}" class="btn btn-link">Reset</a>
      @endif
    </form>

    <div class="table-responsive">
      <table class="table table-hover align-middle nx-table">
        <thead>
          <tr>
            <th>User</th><th>Action</th><th>Module</th><th>Details</th><th>Date</th>
          </tr>
        </thead>
        <tbody>
          @forelse($activities as $activity)
            <tr>
              <td>{{ $activity->causer?->full_name ?? $activity->causer?->name ?? 'System' }}</td>
              <td><span class="badge bg-secondary">{{ ucfirst($activity->description) }}</span></td>
              <td>{{ class_basename($activity->subject_type ?? '') ?: 'Authentication' }}</td>
              <td><pre class="nx-json">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></td>
              <td>{{ $activity->created_at->format('d M, Y | h:i A') }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="nx-empty"><div class="nx-empty-title">No activity found</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="nx-footer-bar">{{ $activities->links() }}</div>
  </div>
</div>
@endsection
