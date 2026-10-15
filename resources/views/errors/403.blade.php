@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Access</small>403 Forbidden</h1>
      <p class="nx-sub">You do not have permission to use this page.</p>
    </div>
  </div>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-shield-halved fs-1 text-muted mb-3"></i>
      <p class="mb-4">Choose a module you are allowed to access.</p>
      <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">Go to my dashboard</a>
    </div>
  </div>
</div>
@endsection
