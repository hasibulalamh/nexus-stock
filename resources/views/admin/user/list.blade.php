@extends('layout.master')

@section('content')
<div class="container-fluid py-4">
  <div class="card shadow border-0">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fas fa-users me-2"></i>All Users</h5>
      @can('users.create')
        <a href="{{ route('admin.users.create') }}" class="btn btn-light btn-sm">
          <i class="fas fa-plus-circle me-1"></i>Add New User
        </a>
      @endcan
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="bg-light">
            <tr>
              <th>#</th>
              <th>Profile</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Department</th>
              <th>Designation</th>
              <th>Status</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($users as $user)
            <tr>
              <td>{{ $users->firstItem() + $loop->index }}</td>
              <td>
                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->full_name }}"
                     class="rounded-circle border" width="40" height="40" style="object-fit: cover;">
              </td>
              <td class="fw-semibold">{{ $user->full_name }}</td>
              <td>{{ $user->email }}</td>
              <td>{{ $user->department?->name ?? '-' }}</td>
              <td>{{ $user->designation?->name ?? '-' }}</td>
              <td>
                @if($user->status == 'active')
                  <span class="badge bg-success">Active</span>
                @else
                  <span class="badge bg-danger">Inactive</span>
                @endif
              </td>

              <td class="text-end">
                <div class="d-flex gap-2 justify-content-end">
                  @can('users.edit')
                    <a href="{{ route('admin.users.edit', $user->id) }}"
                       class="btn btn-outline-primary btn-sm rounded-circle"
                       data-bs-toggle="tooltip" data-bs-title="Edit"
                       style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                      <i class="fas fa-edit"></i>
                    </a>
                  @endcan

                  @can('users.delete')
                    @if($user->id !== auth()->id())
                      <form action="{{ route('admin.users.delete', $user->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle"
                                data-bs-toggle="tooltip" data-bs-title="Delete"
                                style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;"
                                onclick="return confirm('Are you sure you want to delete this user?')">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    @endif
                  @endcan
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="8" class="text-center text-muted py-4">No users found.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-end mt-3">
        {{ $users->links() }}
      </div>
    </div>
  </div>
</div>

<style>
.bg-gradient-primary {
  background: linear-gradient(135deg, #20c997, #6f42c1);
}
.table th {
  font-weight: 600;
}
.badge {
  font-size: 0.8rem;
  padding: 0.45em 0.6em;
}
</style>
@endsection
