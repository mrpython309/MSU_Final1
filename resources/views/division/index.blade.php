@extends('layouts/contentNavbarLayout')

@section('title', 'Divisions')

@section('content')
<h4 class="py-3 breadcrumb-wrapper mb-4">
  Divisions
</h4>

<x-master-filters action="{{ route('division.index') }}" placeholder="Division" />

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Division List</h5>
        </div>
        <div class="d-flex gap-2">
            @include('partials.master-export-buttons', ['excelRoute' => 'division.export', 'pdfRoute' => 'division.export-pdf', 'permission' => 'division:view'])
            @can('division:create')
            <a href="{{ route('division.create') }}" class="btn btn-primary">
                Add Division
            </a>
            @endcan
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Division</th>
                    <th>Status</th>
                    <th width="180">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($divisions as $division)
                    <tr>
                        <td class="text-nowrap text-muted">
                            {{ ($divisions->currentPage() - 1) * $divisions->perPage() + $loop->iteration }}
                        </td>
                        <td>{{ $division->division_name }}</td>
                        <td>
                            @if($division->status === 'active')
                                <span class="badge bg-label-success">Active</span>
                            @else
                                <span class="badge bg-label-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    @can('division:edit')
                                    <a href="{{ route('division.edit', $division->id) }}" class="dropdown-item">
                                        <i class="bx bx-edit-alt me-1"></i> Edit
                                    </a>
                                    @endcan

                                    @can('division:delete')
                                    <form action="{{ route('division.destroy', $division->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          id="delete-form-{{ $division->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                class="dropdown-item text-danger"
                                                onclick="confirmDelete({{ $division->id }})">
                                            <i class="bx bx-trash me-1"></i> Delete
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">
                            No divisions found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        @if($divisions->hasPages())
            <div class="text-muted">Showing {{ $divisions->firstItem() ?? 0 }} to {{ $divisions->lastItem() ?? 0 }} of {{ $divisions->total() }} Divisions</div>
            <div>{{ $divisions->links() }}</div>
        @endif
    </div>
</div>
@endsection

@section('page-script')
<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-form-' + id).submit();
        }
    });
}
</script>
@endsection
