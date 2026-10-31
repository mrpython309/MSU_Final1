<div class="modal fade" id="makeascompletemodel" tabindex="-1" style="display: none;" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">

                                         <form method="POST" action="{{route('support-ticket.complete', $supportTicket->id)}}" class="mt-4" enctype="multipart/form-data">
            @csrf

                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel1">Mark as Complete</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                               
                                                <div class="mb-3">
                        <label class="form-label">Vendor @if($supportTicket->asset_id)<span class="text-danger">*</span>@endif</label>
                        <select id="vendor_id" name="vendor_id"
                            class="form-select @error('vendor_id') is-invalid @enderror"
                            @if($supportTicket->asset_id) required @endif>
                            <option value="">Select Vendor</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected(old('vendor_id') == $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                        @error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                     <div  class="mb-3">
                        <label class="form-label">Total Cost @if($supportTicket->asset_id)<span class="text-danger">*</span>@endif</label>
                        <input type="text"  class="form-control @error('total_cost') is-invalid @enderror"
                                name="total_cost" value="{{ old('total_cost') }}"
                                @if($supportTicket->asset_id) required @endif>
                            @error('total_cost')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                        <label class="form-label">Commands <span class="text-danger">*</span></label>
                        <textarea name="completed_ticket_commands" rows="2"
                            class="form-control @error('completed_ticket_commands') is-invalid @enderror" required>{{ old('completed_ticket_commands') }}</textarea>
                        @error('completed_ticket_commands')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>  


                    <div class="mb-3">
                        <label class="form-label">Files to Attach <span class="text-danger">*</span></label>
                        <input class="form-control @error('completed_files') is-invalid @enderror @error('completed_files.*') is-invalid @enderror"
                               type="file"
                               id="completed_files"
                               name="completed_files[]"
                               accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,image/*"
                               multiple
                               required>
                        <small class="text-muted">Up to 2 files, max 20 MB each. Images are compressed on upload.</small>
                        @error('completed_files')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @error('completed_files.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                                                


                                            </div>
                                             
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Update Ticket as Complete</button>
                                        </div>

                                        </form>

                                    </div>
                                </div>
                            </div>

@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('makeascompletemodel');
    if (!modal || typeof window.jQuery === 'undefined' || !jQuery.fn.select2) {
        return;
    }

    function initVendorSearch() {
        const $vendor = jQuery('#vendor_id');
        if (!$vendor.length || $vendor.data('select2')) {
            return;
        }

        $vendor.select2({
            dropdownParent: jQuery('#makeascompletemodel .modal-content'),
            placeholder: 'Search vendor',
            allowClear: true,
            width: '100%'
        });
    }

    modal.addEventListener('shown.bs.modal', initVendorSearch);
});

document.addEventListener('change', function (event) {
    const input = event.target.closest('#completed_files');
    if (!input || !input.files) {
        return;
    }

    if (input.files.length > 2) {
        alert('You can upload a maximum of 2 files.');
        input.value = '';
        return;
    }

    const maxBytes = 20 * 1024 * 1024;
    for (const file of input.files) {
        if (file.size > maxBytes) {
            alert('Each file must be 20 MB or smaller.');
            input.value = '';
            return;
        }
    }
});
</script>
@endonce