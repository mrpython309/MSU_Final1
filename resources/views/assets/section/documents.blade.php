<div class="card card-action mb-6">
      <div class="card-header align-items-center py-6">
        <h5 class="card-action-title mb-0 me-3">Documents</h5>
        <div class="card-action-element">
           @can('asset.document:create')
          <a class="btn btn-sm btn-primary text-white" href="{{route('asset.document.create')}}?asset_id={{$asset->id}}">Add Document</a>
          @endcan
        </div>
      </div>
      <div class="card-body">

        <div class="table-responsive">
        <table class="table border-top table-border-bottom-0">
          <thead>
            <tr>
              <th class="text-truncate">Actions</th>
              <th class="text-truncate">Document Title</th>
              <th class="text-truncate">Document File</th>
              <th class="text-truncate">Created At</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($asset->documents as $document)
                <tr>
              <td class="text-truncate">
                
                <div class="d-flex align-items-center">
                   @can('asset.document:edit')
                    <a href="{{route('asset.document.edit',$document)}}" class="btn btn-icon">
                         <i class="icon-base bx bx-edit icon-md"></i>
                     </a>
                     @endcan


                      @can('asset.document:delete')
                     
                    <form method="POST" action="{{ route('asset.document.destroy', $document) }}" class="delete-form">
                     @csrf
                     @method('DELETE')
                     <button class="dropdown-item delete-record"> <i class="icon-base bx bx-trash me-1 icon-md"></i>  </button>
                    </form>

                    @endcan

                
                
                
              </div>

              </td>
                <td class="text-truncate">{{ $document->document_name ?? '--'}}</td>
                <td>
                    @if ($document->doc_url)
                        <div class="d-flex align-items-center gap-2">
                            @if ($document->icon == 'images')
                                <a href="{{ $document->doc_url }}" data-lightbox="asset-documents-{{ $asset->id }}" data-title="{{ $document->document_name }}">
                                    <img src="{{ $document->doc_url }}" alt="{{ $document->document_name }}" class="img-fluid rounded" style="max-height: 48px;">
                                </a>
                            @else
                                <a href="{{ $document->doc_url }}" target="_blank" rel="noopener" class="d-inline-flex align-items-center gap-2 text-body">
                                    <i class="bx {{ $document->fileIconClass() }}" style="font-size: 1.5rem;"></i>
                                    <span>{{ $document->document_name }}</span>
                                </a>
                            @endif
                            <a href="{{ $document->doc_url }}" download="{{ $document->filename }}" class="btn btn-icon" title="Download">
                                <i class="icon-base bx bx-download icon-md"></i>
                            </a>
                        </div>
                    @else
                        --
                    @endif
                </td>
                <td class="text-truncate">{{ $document->created_at?->format('d M Y') ?? '--'}}</td> 
            </tr>
            @empty
             <tr>
                 <td colspan="4" class="text-center">No Assignments Found</td> 
             </tr>
            @endforelse


            
           
          </tbody>
        </table>
      </div>
       

      </div>
    </div> 
 

