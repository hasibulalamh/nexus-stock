@extends('layout.master')

@section('content')
@php($driftLines = $count->lines->filter(fn ($line) => (float) $line->currentDrift() !== 0.0))
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Stock count</small>{{ $count->count_no }}</h1></div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" target="_blank" href="{{ route('admin.counts.print', $count) }}">Print count sheet</a>
      <a class="btn btn-outline-secondary" href="{{ route('admin.counts.index') }}">Back</a>
    </div>
  </div>

  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

  <div class="card mb-3"><div class="card-body">
    <div class="row">
      <div class="col-sm-6"><p class="mb-1"><strong>Location:</strong> {{ $count->locationName() }}</p></div>
      <div class="col-sm-6"><p class="mb-1"><strong>Status:</strong>
        <span class="badge {{ $count->status === 'approved' ? 'bg-success' : (in_array($count->status, ['open', 'counting'], true) ? 'bg-warning' : 'bg-secondary') }}">{{ ucfirst($count->status) }}</span>
      </p></div>
      <div class="col-sm-6"><p class="mb-1"><strong>Snapshot:</strong> {{ $count->snapshot_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</p></div>
      <div class="col-sm-6"><p class="mb-1"><strong>Requested by:</strong> {{ $count->requester?->full_name }}</p></div>
      <div class="col-12"><p class="mb-0"><strong>Notes:</strong> {{ $count->notes ?: 'None' }}</p></div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
      @if($count->status === 'submitted' && auth()->user()->can('counts.approve'))
        <form method="POST" action="{{ route('admin.counts.approve', $count) }}">@csrf<button class="btn btn-success">Approve count</button></form>
      @endif
      @if($count->status === 'submitted' && auth()->user()->can('counts.create'))
        <form method="POST" action="{{ route('admin.counts.acknowledge-drift', $count) }}">@csrf<button class="btn btn-outline-warning">Acknowledge drift</button></form>
      @endif
      @if(in_array($count->status, ['open', 'counting', 'submitted'], true) && $count->requested_by === auth()->id() && auth()->user()->can('counts.create'))
        <form method="POST" action="{{ route('admin.counts.cancel', $count) }}">@csrf<button class="btn btn-outline-secondary">Cancel count</button></form>
      @endif
      @if($count->adjustment)
        <a class="btn btn-outline-primary" href="{{ route('admin.adjustments.show', $count->adjustment) }}">View linked adjustment</a>
      @endif
    </div>

    @if($count->status === 'submitted' && $driftLines->isNotEmpty() && ! $count->drift_acknowledged)
      <div class="alert alert-warning mt-3 mb-0">
        Stock moved after the snapshot for {{ $driftLines->count() }} product(s). Approval is blocked until the drift is acknowledged or the count is re-done.
      </div>
    @endif
  </div></div>

  <div class="card"><div class="card-body">
    <h2 class="h5 mb-3">Lines</h2>
    @if($count->isEditable())
      <form method="POST" action="{{ route('admin.counts.submit', $count) }}">
        @csrf
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>Product</th><th>System quantity</th><th>Counted quantity</th><th>Current drift</th></tr></thead>
            <tbody>
            @foreach($count->lines as $line)
              <tr>
                <td>{{ $line->product?->name }}<div class="small text-muted">{{ $line->product?->sku }}</div></td>
                <td>{{ $line->system_qty }}</td>
                <td><input type="number" step="0.001" min="0" class="form-control" name="counts[{{ $line->id }}]" value="{{ old('counts.'.$line->id, $line->counted_qty ?? $line->system_qty) }}"></td>
                <td>{{ $line->currentDrift() }}</td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
        <button class="btn btn-primary">Submit count</button>
      </form>
    @else
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Product</th><th>System (snapshot)</th><th>Counted</th><th>Variance</th><th>Drift at submission</th></tr></thead>
          <tbody>
          @foreach($count->lines as $line)
            <tr>
              <td>{{ $line->product?->name }}<div class="small text-muted">{{ $line->product?->sku }}</div></td>
              <td>{{ $line->system_qty }}</td>
              <td>{{ $line->counted_qty ?? '—' }}</td>
              <td>
                @if($line->variance !== null && (float) $line->variance !== 0.0)
                  <span class="badge bg-warning text-dark">{{ $line->variance }}</span>
                @else
                  —
                @endif
              </td>
              <td>{{ $line->drift_qty ?? '—' }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div></div>
</div>
@endsection
