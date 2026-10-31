<div class="card card-action mb-6">
      <div class="card-header align-items-center py-6">
        <h5 class="card-action-title mb-0 me-3">Warranties</h5>
        <div class="card-action-element">
          @can('asset.warranty:create')
          <a class="btn btn-sm btn-primary text-white" href="{{route('asset.warranty.create')}}?asset_id={{$asset->id}}" >Add Warranty</a>
          @endcan
        </div>
      </div>
      <div class="card-body">

        <div class="table-responsive">
        <table class="table border-top table-border-bottom-0">
          <thead>
            <tr>
              <th class="text-truncate">Actions</th>
              <th class="text-truncate">Warranty Type</th>
              <th class="text-truncate">Warranty No</th>
              <th class="text-truncate">Covered Parts</th>
              <th class="text-truncate">Expiry Date</th>
              <th class="text-truncate">Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($asset->warranties as $warrenty)
                <tr>
              <td class="text-truncate">
                
                <div class="d-flex align-items-center">
                   @can('asset.warranty:edit')
                    <a href="{{route('asset.warranty.edit',$warrenty)}}" class="btn btn-icon">
                  <i class="icon-base bx bx-edit icon-md"></i>
                </a>
                @endcan
                @can('asset.warranty:delete')

                  <form method="POST" action="{{ route('asset.warranty.destroy', $warrenty) }}"
                                            class="delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item delete-record">
                                                <i class="icon-base bx bx-trash me-1 icon-md"></i> 
                                            </button>
                                        </form>

                @endcan
                
                
              </div>

              </td>
                  <td class="text-truncate">{{ $warrenty->warranty_type->label() ?? '--'}}</td>
                  <td class="text-truncate">{{ $warrenty->warranty_number ?? '--'}}</td>
                   <td class="text-truncate">{{ $warrenty->covered_parts ?? '--'}}</td>
                   <td class="text-truncate">{{ $warrenty->warranty_expire_date?->format('d M Y') ?? '--'}}</td>
                   <td class="text-truncate">{!! $warrenty->status_label !!} </td>
            </tr>
            @empty
             <tr>
                 <td colspan="6" class="text-center">No Assignments Found</td> 
             </tr>
            @endforelse


            
           
          </tbody>
        </table>
      </div>
       

      </div>
    </div> 
 

