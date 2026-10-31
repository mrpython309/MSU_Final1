@extends('layouts/contentNavbarLayout')

@section('title', 'User')

@section('content')
<h4 class="py-3 breadcrumb-wrapper mb-4">
  Users
</h4>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">User List</h5>
         @can('employees:create')
        <a href="{{route('employees.create')}}" class="btn btn-primary">
            Add User
        </a>
        @endcan
    </div>

    <div class="card-body">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table ">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Emp Code</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile Number</th>
                    <th>Division</th>
                    <th>Company</th>
                    <th width="150">Actions</th>
                </tr>
            </thead>
            <tbody>
                
                @forelse($employees as $employee)
                    <tr id="row-{{ $employee->id }}">
                        <td>{{  $employees->firstItem() + $loop->index }}</td>
                        <td>{{ $employee->emp_code }}</td>
                        <td>{{ $employee->name }}</td>
                        <td>{{ $employee->email }}</td>                      
                        <td>{{ $employee->mobile_number }}</td>
                        <td>{{ $employee->division->division_name ?? '-' }}</td>
                        <td>{{ $employee->companies->pluck('name')->implode(', ') ?: '-' }}</td>
                        <td>
                            <div class="dropdown">
                                    <button class="btn p-0 btn-sm" type="button" data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                         @can('employees:edit')
                                        <li><a class="dropdown-item" href="{{ route('employees.edit', $employee) }}">
                                            <i class="bx bx-edit me-1"></i> Edit</a></li>
                                            @endcan
                                             @can('employees:delete')
                                        <li>
                                            <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="d-inline delete-form">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bx bx-trash me-2"></i>Delete
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>

                             
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            No users found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>

     <div class="card-footer d-flex justify-content-between align-items-center">
            @if($employees->hasPages())
                <div class="text-muted">Showing {{ $employees->firstItem() ?? 0 }} to {{ $employees->lastItem() ?? 0 }} of {{ $employees->total() }} users</div>
                <div>{{ $employees->links() }}</div>
            @endif
        </div>

</div>
@endsection

@section('page-script')
<script>


document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});

</script>
@endsection
