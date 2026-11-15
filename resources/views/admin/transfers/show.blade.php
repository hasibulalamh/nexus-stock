@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>{{ ucfirst($transfer->type) }}</small>{{ $transfer->transfer_no }}</h1></div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" target="_blank" href="{{ route('admin.transfers.delivery-note', $transfer) }}">Delivery note</a>
      <a class="btn btn-outline-secondary" href="{{ route('admin.transfers.index') }}">Back</a>
    </div>
  </div>

  @if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card mb-3"><div class="card-body">
        <h2 class="h5 mb-3">Route</h2>
        <div class="row">
          <div class="col-sm-6"><p class="mb-1"><strong>From:</strong> {{ $transfer->fromLocationName() }}</p></div>
          <div class="col-sm-6"><p class="mb-1"><strong>To:</strong> {{ $transfer->toLocationName() }}</p></div>
          <div class="col-sm-6"><p class="mb-1"><strong>Status:</strong>
            <span class="badge {{ $transfer->status === 'received' ? 'bg-success' : ($transfer->status === 'cancelled' ? 'bg-secondary' : ($transfer->status === 'sent' ? 'bg-info' : 'bg-warning')) }}">{{ ucfirst($transfer->status) }}</span>
          </p></div>
          <div class="col-sm-6"><p class="mb-1"><strong>Batch:</strong> {{ $transfer->batch_uuid ?: '-' }}</p></div>
          @if($transfer->reason)
            <div class="col-sm-6"><p class="mb-1"><strong>Return reason:</strong> {{ ucfirst(str_replace('_', ' ', $transfer->reason)) }}</p></div>
          @endif
          <div class="col-sm-6"><p class="mb-1"><strong>Alert:</strong> {{ $transfer->stockAlert ? '#'.$transfer->stockAlert->id : 'None' }}</p></div>
          <div class="col-12"><p class="mb-1"><strong>Notes:</strong> {{ $transfer->notes ?: 'None' }}</p></div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
          @if($transfer->status === 'draft' && auth()->user()->can('transfers.send'))
            <form method="POST" action="{{ route('admin.transfers.send', $transfer) }}">@csrf<button class="btn btn-primary">Send all</button></form>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#sendModal">Send with quantities</button>
          @endif
          @if($transfer->status === 'sent' && auth()->user()->can('transfers.receive'))
            <form method="POST" action="{{ route('admin.transfers.receive', $transfer) }}">@csrf<button class="btn btn-success">Receive all</button></form>
            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#receiveModal">Receive with exceptions</button>
          @endif
          @if($transfer->status === 'draft' && auth()->user()->can('transfers.cancel'))
            <form method="POST" action="{{ route('admin.transfers.cancel', $transfer) }}">@csrf<button class="btn btn-outline-danger">Cancel draft</button></form>
          @endif
        </div>
      </div></div>

      <div class="card"><div class="card-body">
        <h2 class="h5 mb-3">Items</h2>
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>Product</th><th>Requested</th><th>Sent</th><th>Received</th><th>Serial units</th><th>Backorder</th></tr></thead>
            <tbody>
            @forelse($transfer->items as $item)
              @php($serials = $item->stockUnits->map(fn ($unit) => $unit->serial_no ?: $unit->unit_code)->implode(', '))
              @php($backorder = (float) $item->requested_qty - (float) $item->sent_qty)
              <tr>
                <td>{{ $item->product?->name }}<div class="small text-muted">{{ $item->product?->sku }}</div></td>
                <td>{{ $item->requested_qty }}</td>
                <td>{{ $item->sent_qty }}</td>
                <td>{{ $item->received_qty }}</td>
                <td class="small">{{ $serials !== '' ? $serials : '—' }}</td>
                <td>
                  @if($backorder > 0)
                    <span class="badge bg-warning text-dark">Backorder {{ number_format($backorder, 3, '.', '') }}</span>
                  @else
                    —
                  @endif
                </td>
              </tr>
              @if($item->discrepancies->isNotEmpty())
                <tr><td colspan="6" class="bg-light">
                  <strong class="d-block mb-2">Discrepancies</strong>
                  <table class="table table-sm mb-0">
                    <thead><tr><th>Type</th><th>Qty</th><th>Status</th><th>Reason / note</th><th></th></tr></thead>
                    <tbody>
                    @foreach($item->discrepancies as $discrepancy)
                      <tr>
                        <td>{{ ucfirst($discrepancy->type) }}</td>
                        <td>{{ $discrepancy->qty }}</td>
                        <td>
                          @if($discrepancy->status === 'open')
                            <span class="badge bg-danger">Open</span>
                          @else
                            <span class="badge bg-success">Resolved: {{ ucfirst(str_replace('_', ' ', $discrepancy->resolution)) }}</span>
                          @endif
                        </td>
                        <td>{{ $discrepancy->resolution_note ?: ($discrepancy->reason ?: '—') }}</td>
                        <td>
                          @if($discrepancy->status === 'open' && auth()->user()->can('stock.discrepancies.resolve'))
                            <form method="POST" action="{{ route('admin.transfers.discrepancies.resolve', $discrepancy) }}" class="d-flex flex-wrap gap-1">
                              @csrf
                              <select name="resolution" class="form-select form-select-sm" style="width:auto">
                                <option value="found">Found</option>
                                <option value="returned">Returned</option>
                                <option value="written_off">Written off</option>
                                <option value="lost">Lost</option>
                              </select>
                              <input name="note" class="form-control form-control-sm" style="width:140px" placeholder="Note">
                              <button class="btn btn-sm btn-primary">Resolve</button>
                            </form>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                    </tbody>
                  </table>
                </td></tr>
              @endif
            @empty
              <tr><td colspan="6">No lines.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div></div>
    </div>

    <div class="col-lg-4">
      <div class="card"><div class="card-body">
        <h2 class="h5 mb-3">Timeline</h2>
        <ul class="list-unstyled mb-0">
          <li class="mb-3">
            <strong>Created</strong><br>
            <span class="text-muted">{{ $transfer->requester?->full_name ?? 'Unknown user' }}</span><br>
            <span class="small">{{ $transfer->created_at?->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</span>
          </li>
          @if($transfer->sent_at)
            <li class="mb-3">
              <strong>Sent</strong><br>
              <span class="text-muted">{{ $transfer->sender?->full_name ?? 'Unknown user' }}</span><br>
              <span class="small">{{ $transfer->sent_at->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</span>
            </li>
          @endif
          @if($transfer->received_at)
            <li class="mb-3">
              <strong>Received</strong><br>
              <span class="text-muted">{{ $transfer->receiver?->full_name ?? 'Unknown user' }}</span><br>
              <span class="small">{{ $transfer->received_at->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</span>
            </li>
          @endif
          @if($transfer->cancelled_at)
            <li class="mb-3">
              <strong>Cancelled</strong><br>
              <span class="small">{{ $transfer->cancelled_at->setTimezone('Asia/Dhaka')->format('Y-m-d H:i') }}</span>
            </li>
          @endif
          @if($transfer->status === 'draft')
            <li class="text-muted small">Waiting to be sent.</li>
          @elseif($transfer->status === 'sent')
            <li class="text-muted small">Stock has left the source location and is in transit.</li>
          @endif
        </ul>
      </div></div>
    </div>
  </div>
</div>

@if($transfer->status === 'draft' && auth()->user()->can('transfers.send'))
  <div class="modal fade" id="sendModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" action="{{ route('admin.transfers.send', $transfer) }}">
          @csrf
          <div class="modal-header"><h5 class="modal-title">Send with quantities</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <p class="text-muted small">Send less than requested to leave a backorder. Unticked serial units stay at the source.</p>
            @foreach($transfer->items as $item)
              <div class="border rounded p-2 mb-2">
                <div class="fw-semibold mb-1">{{ $item->product?->name }} <span class="text-muted small">(requested {{ $item->requested_qty }})</span></div>
                @if($item->product?->tracking_type === 'serial')
                  @foreach($item->stockUnits->where('status', 'in_stock') as $unit)
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="items[{{ $item->id }}][unit_ids][]" value="{{ $unit->id }}" id="send-unit-{{ $item->id }}-{{ $unit->id }}" checked>
                      <label class="form-check-label" for="send-unit-{{ $item->id }}-{{ $unit->id }}">{{ $unit->serial_no ?: $unit->unit_code }}</label>
                    </div>
                  @endforeach
                @else
                  <input type="number" step="0.001" min="0" max="{{ $item->requested_qty }}" class="form-control" name="items[{{ $item->id }}][sent_qty]" value="{{ $item->requested_qty }}">
                @endif
              </div>
            @endforeach
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            <button class="btn btn-primary">Send</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endif

@if($transfer->status === 'sent' && auth()->user()->can('transfers.receive'))
  <div class="modal fade" id="receiveModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" action="{{ route('admin.transfers.receive', $transfer) }}">
          @csrf
          <div class="modal-header"><h5 class="modal-title">Receive with exceptions</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <p class="text-muted small">Enter the good quantity that arrived and the damaged quantity. Shortages and damage create discrepancies; only good stock enters the destination.</p>
            @foreach($transfer->items as $item)
              <div class="border rounded p-2 mb-2">
                <div class="fw-semibold mb-1">{{ $item->product?->name }} <span class="text-muted small">(sent {{ $item->sent_qty }})</span></div>
                @if($item->product?->tracking_type === 'serial')
                  @foreach($item->stockUnits->where('status', 'in_transit') as $unit)
                    <div class="row g-1 align-items-center mb-1">
                      <div class="col-7">{{ $unit->serial_no ?: $unit->unit_code }}</div>
                      <div class="col-5 d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="items[{{ $item->id }}][unit_ids][]" value="{{ $unit->id }}" id="receive-unit-{{ $item->id }}-{{ $unit->id }}" checked>
                          <label class="form-check-label small" for="receive-unit-{{ $item->id }}-{{ $unit->id }}">Arrived</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="items[{{ $item->id }}][damaged_unit_ids][]" value="{{ $unit->id }}" id="damaged-unit-{{ $item->id }}-{{ $unit->id }}">
                          <label class="form-check-label small" for="damaged-unit-{{ $item->id }}-{{ $unit->id }}">Damaged</label>
                        </div>
                      </div>
                    </div>
                  @endforeach
                  <div class="form-text">Untick arrived units and mark damaged ones; unticked units are shortages.</div>
                @else
                  <div class="row g-2">
                    <div class="col-6">
                      <label class="form-label small mb-0">Received</label>
                      <input type="number" step="0.001" min="0" max="{{ $item->sent_qty }}" class="form-control" name="items[{{ $item->id }}][received_qty]" value="{{ $item->sent_qty }}">
                    </div>
                    <div class="col-6">
                      <label class="form-label small mb-0">Damaged (of sent)</label>
                      <input type="number" step="0.001" min="0" max="{{ $item->sent_qty }}" class="form-control" name="items[{{ $item->id }}][damaged_qty]" value="0">
                    </div>
                  </div>
                @endif
                <input name="items[{{ $item->id }}][reason]" class="form-control form-control-sm mt-2" placeholder="Reason (optional)">
              </div>
            @endforeach
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            <button class="btn btn-success">Confirm receipt</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endif
@endsection
