<div class="modal fade" id="manageTicketStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ticket Statuses</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('support-ticket.progress-statuses.store') }}" class="mb-4">
                    @csrf
                    <label class="form-label" for="new_status_name">New status</label>
                    <div class="input-group">
                        <input type="text" id="new_status_name" name="status_name" class="form-control {{ $errors->has('status_name') && !session('editing_status_id') ? 'is-invalid' : '' }}" placeholder="Status name" maxlength="50" value="{{ session('editing_status_id') ? '' : old('status_name') }}" required>
                        <button class="btn btn-primary" type="submit">Add</button>
                    </div>
                    @error('status_name')
                        @if(!session('editing_status_id'))
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @endif
                    @enderror
                </form>

                <label class="form-label">Existing statuses</label>
                @forelse($ticketProgressStatuses as $progressStatus)
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <form method="POST" action="{{ route('support-ticket.progress-statuses.update', $progressStatus) }}" class="d-flex flex-grow-1 gap-2">
                            @csrf
                            @method('PUT')
                            <input type="text" name="status_name" class="form-control form-control-sm" maxlength="50" required
                                value="{{ (int) session('editing_status_id') === (int) $progressStatus->id ? old('status_name', $progressStatus->name) : $progressStatus->name }}">
                            <button type="submit" class="btn btn-sm btn-icon btn-primary" title="Save">
                                <i class="icon-base bx bx-check"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('support-ticket.progress-statuses.destroy', $progressStatus) }}" onsubmit="return confirm('Delete this status?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete">
                                <i class="icon-base bx bx-trash"></i>
                            </button>
                        </form>
                    </div>
                    @if((int) session('editing_status_id') === (int) $progressStatus->id)
                        @error('status_name')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror
                    @endif
                @empty
                    <p class="text-muted mb-0">No statuses yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if($errors->has('status_name') || session('open_status_modal'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('manageTicketStatusModal');
    if (el && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(el).show();
    }
});
</script>
@endif
