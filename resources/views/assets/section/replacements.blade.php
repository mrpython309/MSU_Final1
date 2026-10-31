@extends('assets.asset-layout')

@section('asset_section')

<div class="card card-action mb-6">
    <div class="card-header align-items-center py-6">
        <h5 class="card-action-title mb-0 me-3">Replacement History</h5>

        <div class="card-action-element">
             @can('asset.replacement:create')
            <a class="btn btn-sm btn-primary text-white"
               href="{{ route('asset.replacement.create', ['asset_id' => $asset->id]) }}">
                Add Replacement
            </a>
            @endcan
        </div>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table class="table border-top table-border-bottom-0">
                <thead>
                    <tr>
                        <th>Actions</th>
                        <th>Date</th>
                        <th>Old Serial No</th>
                        <th>New Serial No</th>
                        <th>Old Condition</th>
                        <th>New Condition</th> 
                        <th>Reason</th> 
                        <th>Location</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($asset->replacements as $replacement)
                        <tr>

                            <td class="text-truncate">

                                 <div class="d-flex align-items-center">
                                     @can('asset.replacement:edit')
                                    <a href="{{ route('asset.replacement.edit', $replacement) }}"
                                       class="btn btn-icon">
                                        <i class="icon-base bx bx-edit icon-md"></i>
                                    </a>
                                    @endcan

                                     @can('asset.replacement:delete')

                                    <form method="POST"
                                          action="{{ route('asset.replacement.destroy', $replacement) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dropdown-item delete-record">
                                            <i class="icon-base bx bx-trash me-1 icon-md"></i>
                                        </button>
                                    </form>

                                    @endcan

                                </div>
                            </td>

                            <td>
                                {{ \Carbon\Carbon::parse($replacement->replacement_date)->format('d M Y') }}
                            </td>

                            <td>{{ $replacement->old_serial_no }}</td>
                            <td>{{ $replacement->new_serial_no }}</td>

                            <td>
                                <span class="badge bg-label-warning">
                                    {{ ucfirst($replacement->old_condition) }}
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-label-success">
                                    {{ ucfirst($replacement->new_condition) }}
                                </span>
                            </td>

                            

                            <td>
                                {{ \Illuminate\Support\Str::limit($replacement->replacement_reason, 40) }}
                            </td>

                            <td>
                                {{ $replacement->company->name ?? '--' }}
                                <br>
                                {{ $replacement->branch->name ?? '--' }}
                                <br>
                                {{ $replacement->location->location_name ?? '--' }}
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">
                                No Replacement Records Found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>

@endsection
