@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Insights</small>Stock KPIs</h1></div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index') }}">Reports</a>
    </div>
  </div>

  <form class="row g-2 mb-3">
    <div class="col-md-3">
      <select name="days" class="form-select">
        @foreach([30, 90, 365] as $option)
          <option value="{{ $option }}" @selected($days === $option)>Last {{ $option }} days</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-1"><button class="btn btn-primary">Apply</button></div>
  </form>

  <div class="row g-3">
    @foreach($cards as $card)
      <div class="col-md-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
          <div class="text-muted small">{{ $card['label'] }}</div>
          <div class="display-6">{{ $card['value'] }}</div>
          <div class="small text-muted">{{ $card['note'] }}</div>
        </div></div>
      </div>
    @endforeach

    <div class="col-md-6 col-xl-3">
      <div class="card h-100 border-secondary"><div class="card-body">
        <div class="text-muted small">Inventory turnover</div>
        <div class="display-6">Not available</div>
        <div class="small text-muted">Requires a Sales module, which does not exist yet. No placeholder numbers are shown.</div>
      </div></div>
    </div>
  </div>
</div>
@endsection
