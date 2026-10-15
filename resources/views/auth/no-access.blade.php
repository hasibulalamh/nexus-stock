@extends('layout.master')

@section('content')
<div class="container-fluid p-0">
  <div class="nx-page-head">
    <div>
      <h1 class="nx-title"><small>Access</small>No access assigned</h1>
      <p class="nx-sub">Your account does not have a module permission yet.</p>
    </div>
  </div>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-lock fs-1 text-muted mb-3"></i>
      <p class="mb-4">Please contact an administrator to request access.</p>
      <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">Go to my dashboard</a>
    </div>
  </div>
</div>
@endsection
