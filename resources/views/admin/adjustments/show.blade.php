@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Adjustment</small>{{ $adjustment->adj_no }}</h1></div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.adjustments.index') }}">Back</a>
  </div>

  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

  <div class="card mb-3"><div class="card-body">
    <div class="row">
      <div class="col-sm-6"><p class="mb-1"><strong>Location:</strong> {{ $adjustment->locationName() }}</p></div>
      <div class="col-sm-6"><p class="mb-1"><strong>Reason:</strong> {{ ucfirst(str_replace('_', ' ', $adjustment->reason)) }}</p></div>
      <div class="col-sm-6"><p class="mb-1"><strong>Status:</strong>
        <span class="badge {{ $adjustment->status === 'approved' ? 'bg-success' : ($adjustment->status === 'pending' ? 'bg-warning' : 'bg-secondary') }}">{{ ucfirst($adjustment->status) }}</span>
      </p></div>
      <div class="col-sm-6"><p class="mb-1"><strong>Stock count:</strong>
        @if($adjustment->count)
          <a href="{{ route('admin.counts.show', $adjustment->count) }}">{{ $adjustment->count->count_no }}</a>
        @else
          —
        @endif
      </p></div>
      <div class="col-12"><p class="mb-0"><strong>Notes:</strong> {{ $adjustment->notes ?: 'None' }}</p></div>
      @if($adjustment->rejection_reason)<div class="col-12"><p class="mb-0 text-danger"><strong>Rejection:</strong> {{ $adjustment->rejection_reason }}</p></div>@endif
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
      @if($adjustment->status === 'pending' && auth()->user()->can('adjustments.approve'))
        <form method="POST" action="{{ route('admin.adjustments.approve', $adjustment) }}">@csrf<button class="btn btn-success">Approve &amp; post</button></form>
        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
      @endif
      @if($adjustment->status === 'pending' && $adjustment->requested_by === auth()->id() && auth()->user()->can('adjustments.create'))
        <form method="POST" action="{{ route('admin.adjustments.cancel', $adjustment) }}">@csrf<button class="btn btn-outline-secondary">Cancel</button></form>
      @endif
    </div>
  </div></div>

  <div class="card"><div class="card-body">
    <h2 class="h5 mb-3">Lines</h2>
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>Product</th><th>Delta</th><th>Serials</th></tr></thead>
        <tbody>
        @foreach($adjustment->lines as $line)
          <tr>
            <td>{{ $line->product?->name }}<div class="small text-muted">{{ $line->product?->sku }}</div></td>
            <td>{{ $line->delta }}</td>
            <td class="small">{{ implode(', ', $line->serial_numbers ?? []) ?: (count($line->stock_unit_ids ?? []) ? count($line->stock_unit_ids).' unit(s)' : '—') }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div></div>
</div>

@if($adjustment->status === 'pending' && auth()->user()->can('adjustments.approve'))
  <div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.adjustments.reject', $adjustment) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">Reject adjustment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <label class="form-label">Reason</label>
          <textarea name="rejection_reason" class="form-control" rows="2" required></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-danger">Reject</button>
        </div>
      </form>
    </div></div>
  </div>
@endif
@endsection
