@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div><h1 class="nx-title"><small>Insights</small>Reports</h1></div>
    @can('stock.kpi.view')
      <a class="btn btn-primary" href="{{ route('admin.stock.kpi') }}">Stock KPIs</a>
    @endcan
  </div>

  <div class="row g-3">
    @foreach($reports as $report)
      <div class="col-md-6 col-xl-4">
        <div class="card h-100"><div class="card-body d-flex flex-column">
          <h2 class="h5">{{ $report['title'] }}</h2>
          <p class="text-muted flex-grow-1">{{ $report['description'] }}</p>
          <a class="btn btn-outline-primary" href="{{ route('admin.reports.'.$report['key']) }}">Open report</a>
        </div></div>
      </div>
    @endforeach
  </div>
</div>
@endsection
