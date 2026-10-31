<div class="card card-action mb-6">
  <div class="card-header align-items-center py-6">
    <h5 class="card-action-title mb-0 me-3">Maintenance</h5>

    <div class="card-action-element">
        @can('asset.maintenance:create')
      <a class="btn btn-sm btn-primary text-white"
         href="{{ route('asset.maintenance.create') }}?asset_id={{ $asset->id }}">
        Add Maintenance
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
            <th>Code</th>
            <th>Date</th>
            <th>Vendor</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Cost</th>
            <th>Duration</th>
          </tr>
        </thead>

        <tbody>
          @forelse($asset->maintenance as $maintenance)
          <tr>

            {{-- Actions --}}
            <td class="text-truncate">
              <div class="d-flex align-items-center gap-3">

                @if($maintenance->status != 'completed')

                {{-- Edit --}}
                @can('asset.maintenance:edit')
                <a class="btn btn-icon edit-record"
                   href="{{ route('asset.maintenance.edit', $maintenance) }}">
                  <i class="bx bx-edit"></i>
                </a>
               @endcan

                {{-- Delete --}}
                @can('asset.maintenance:delete')
                <form method="POST"
                      action="{{ route('asset.maintenance.destroy', $maintenance) }}"
                      class="delete-form">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-icon delete-record">
                    <i class="bx bx-trash"></i>
                  </button>
                </form>
                @endcan

                @endif

              </div>
            </td>

            {{-- Code --}}
            <td>{{ $maintenance->maintenance_code }}</td>

            {{-- Maintenance Date --}}
            <td>
              {{ $maintenance->maintenance_date?->format('d M Y') ?? '--' }}
            </td>

            {{-- Vendor --}}
            <td>
              @if($maintenance->vendor)
                <div class="d-flex flex-column">
                  <span class="fw-medium">{{ $maintenance->vendor->name }}</span>
                  <small class="text-muted">
                    {{ $maintenance->vendor->phone_number ?? '' }}
                  </small>
                </div>
              @else
                --
              @endif
            </td>

            {{-- Priority --}}
            <td>
              @php
                $priorityColors = [
                  'low' => 'secondary',
                  'medium' => 'info',
                  'high' => 'warning',
                  'critical' => 'danger',
                ];
              @endphp

              <span class="badge bg-label-{{ $priorityColors[$maintenance->priority] ?? 'secondary' }}">
                {{ ucfirst($maintenance->priority) }}
              </span>
            </td>

            {{-- Status --}}
            <td>
              @php
                $statusColors = [
                  'pending' => 'secondary',
                  'in_progress' => 'warning',
                  'completed' => 'success',
                  'cancelled' => 'danger',
                ];
              @endphp

              <span class="badge bg-label-{{ $statusColors[$maintenance->status] ?? 'secondary' }}">
                {{ $maintenance->status === 'completed' ? 'Complete' : ucfirst(str_replace('_',' ',$maintenance->status)) }}
              </span>
            </td>

            {{-- Cost --}}
            <td>
              ₹ {{ number_format($maintenance->maintenance_cost ?? 0, 2) }}
            </td>

            {{-- Duration --}}
            <td>
              @if($maintenance->start_date && $maintenance->end_date)
                {{ $maintenance->start_date->format('d M') }}
                -
                {{ $maintenance->end_date->format('d M Y') }}
              @else
                --
              @endif
            </td>

          </tr>
          @empty
          <tr>
            <td colspan="8" class="text-center">No Maintenance Records Found</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>
</div>
