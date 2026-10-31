@extends('layouts/supportTicketLayout')

@section('title', 'Create Support Ticket')

@section('page-style')
<style>
    .form-container {
        background: #f8f9fa;
        border-radius: 0.5rem;
        padding: 2rem;
        border: 1px solid #e3e6f0;
        margin-bottom: 1.5rem;
    }
    .form-section-title {
        color: #495057;
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e9ecef;
    }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="layout-menu-toggle navbar-nav align-items-center d-xl-none">
            <a class="nav-item nav-link px-0" href="javascript:void(0)">
                <i class="icon-base bx bx-menu icon-md"></i>
            </a>
        </div>
        <h4 class="mb-0 breadcrumb-wrapper">
            <span class="text-muted fw-light">Create Support Ticket</span>
        </h4>
    </div>
    @include('support-tickets.partials.header-actions')
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">New Support Ticket</h5>
        <a href="{{ route('support-ticket.index') }}" class="btn btn-secondary">Back to list</a>
    </div>

    <div class="card-body">
        <div class="form-container">

            <form action="{{ route('support-ticket.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- TICKET DETAILS --}}
                <div class="form-section-title">Ticket Details</div>

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label" for="ticket_number_preview">Ticket ID</label>
                        <input type="text" id="ticket_number_preview" class="form-control" value="{{ $previewTicketNumber }}" readonly disabled>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" required
                            class="form-control @error('subject') is-invalid @enderror"
                            value="{{ old('subject') }}">
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Priority <span class="text-danger">*</span></label>
                        <select name="priority" required
                            class="form-select @error('priority') is-invalid @enderror">
                            <option value="">Select Priority</option>
                            <option value="low">Low</option>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" value="{{date('Y-m-d')}}"
                            class="form-control @error('due_date') is-invalid @enderror"
                            value="{{ old('due_date') }}">
                        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                </div>

                {{-- ORGANIZATION DETAILS --}}
                <div class="form-section-title mt-4">Organization Details</div>

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Asset </label>
                        <select id="asset_id" name="asset_id"
                            class="form-select @error('asset_id') is-invalid @enderror select2">
                            <option value="">Select Asset</option>
                            @foreach($assets as $asset)
                                <option value="{{ $asset->id }}"
                                    data-company_id="{{ $asset->company_id }}"
                                    data-branch_id="{{ $asset->branch_id }}"
                                    data-department_id="{{ $asset->department_id }}">{{ $asset->asset_name }} | {{ $asset->serial_no }}@if(filled($asset->asset_code)) | {{ $asset->asset_code }}@endif</option>
                            @endforeach
                        </select>
                        @error('asset_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Company <span class="text-danger">*</span></label>
                        <select id="company_id" name="company_id"
                            class="form-select @error('company_id') is-invalid @enderror" required>
                            <option value="">Select Company</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                        @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select id="branch_id" name="branch_id"
                            class="form-select select2 @error('branch_id') is-invalid @enderror" required>
                            <option value="">Select Branch</option>                             
                        </select>
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Division <span class="text-danger">*</span></label>
                        <select id="division_id" name="division_id" required
                            class="form-select @error('division_id') is-invalid @enderror">
                            <option value="">Select Division</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}" @selected((string) old('division_id') === (string) $division->id)>{{ $division->division_name }}</option>
                            @endforeach
                        </select>
                        @error('division_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                </div>

                {{-- DESCRIPTION --}}
                <div class="form-section-title mt-4">Issue Description</div>

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="4"
                            class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                     <div class="col-4">
                        <label class="form-label">Ticket File </label>
                        <input class="form-control @error('filename') is-invalid @enderror" type="file" id="filename" name="filename">              
                        @error('filename')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                </div>

                {{-- ACTIONS --}}
                <div class="mt-4 text-end">
                    <button class="btn btn-primary">Create Ticket</button>
                    <a href="{{ route('support-ticket.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
@php
    $assetMeta = $assets->mapWithKeys(function ($a) {
        return [(string) $a->id => [
            'company_id' => $a->company_id,
            'branch_id' => $a->branch_id,
            'department_id' => $a->department_id,
        ]];
    });
@endphp
(function () {
    let pendingBranchId = null;
    let pendingDepartmentId = null;
    let suppressAssetReloadOnBranchChange = false;
    let syncingFromAsset = false;
    let ticketPreviewToken = 0;
    let ticketPreviewRequest = null;

    function refreshTicketNumberPreview() {
        const token = ++ticketPreviewToken;
        if (ticketPreviewRequest && ticketPreviewRequest.abort) {
            ticketPreviewRequest.abort();
        }

        ticketPreviewRequest = $.ajax({
            url: @json(route('support-ticket.preview-ticket-number')),
            type: 'GET',
            dataType: 'json',
            data: {
                company_id: $('#company_id').val() || '',
                branch_id: $('#branch_id').val() || ''
            }
        }).done(function (res) {
            if (token !== ticketPreviewToken) return;
            if (res.success && res.ticket_number) {
                $('#ticket_number_preview').val(res.ticket_number);
            }
        });
    }

    const assetMeta = @json($assetMeta);

    function rebuildAssetMetaFromOptions($select) {
        $select.find('option').each(function () {
            const $o = $(this);
            const id = $o.val();
            if (!id) return;
            const cid = $o.attr('data-company_id');
            const bid = $o.attr('data-branch_id');
            const did = $o.attr('data-department_id');
            const num = function (v) {
                if (v === undefined || v === '') return null;
                const n = Number(v);
                return Number.isNaN(n) ? null : n;
            };
            assetMeta[id] = {
                company_id: num(cid),
                branch_id: num(bid),
                department_id: num(did),
            };
        });
    }

    function whenBranchOptionAvailable(done) {
        const el = document.getElementById('branch_id');
        const bid = pendingBranchId != null ? String(pendingBranchId) : '';
        if (!el || !bid) {
            done();
            return;
        }
        const tryReady = function () {
            return !!el.querySelector('option[value="' + bid + '"]');
        };
        if (tryReady()) {
            done();
            return;
        }
        let tid;
        const obs = new MutationObserver(function () {
            if (tryReady()) {
                clearTimeout(tid);
                obs.disconnect();
                done();
            }
        });
        tid = setTimeout(function () {
            obs.disconnect();
            done();
        }, 15000);
        obs.observe(el, { childList: true, subtree: true });
    }

    $('.select2').select2({
        placeholder: "Select ",
        allowClear: true
    });

    $('#company_id').on('change', function () {
        let companyId = $(this).val();
        let branchDropdown = $('#branch_id');

        branchDropdown.html('<option value="">Loading...</option>');

        if (companyId) {
            $.ajax({
                url: "{{ route('ajax.getbranch.bycompany', ':id') }}".replace(':id', companyId),
                type: "GET",
                dataType: "json",
                success: function (res) {
                    branchDropdown.empty();
                    branchDropdown.append('<option value="">Select Branch</option>');

                    if (res.data.length > 0) {
                        $.each(res.data, function (key, value) {
                            branchDropdown.append(
                                `<option value="${value.id}">${value.name}</option>`
                            );
                        });
                    } else {
                        branchDropdown.append('<option value="">No branches found</option>');
                    }

                    refreshTicketNumberPreview();
                },
                error: function () {
                    branchDropdown.html('<option value="">Failed to load branches</option>');
                    refreshTicketNumberPreview();
                }
            });
        } else {
            branchDropdown.html('<option value="">Select Branch</option>');
            refreshTicketNumberPreview();
        }
    });

    $('#branch_id').on('change', function () {
        refreshTicketNumberPreview();

        if (suppressAssetReloadOnBranchChange) {
            suppressAssetReloadOnBranchChange = false;
            return;
        }

        let branchId = $(this).val();
        let assetDropDown = $('#asset_id');

        if (!branchId && syncingFromAsset) {
            return;
        }

        assetDropDown.html('<option value="">Loading...</option>');

        if (branchId) {
            $.ajax({
                url: "{{ route('ajax.getasset.bybranch', ':id') }}".replace(':id', branchId),
                type: "GET",
                dataType: "json",
                success: function (res) {
                    assetDropDown.empty();
                    assetDropDown.append('<option value="">Select Branch</option>');

                    if (res.data.length > 0) {
                        $.each(res.data, function (key, value) {
                            const cid = value.company_id != null ? value.company_id : '';
                            const bid = value.branch_id != null ? value.branch_id : '';
                            const did = value.department_id != null ? value.department_id : '';
                            assetMeta[String(value.id)] = {
                                company_id: value.company_id,
                                branch_id: value.branch_id,
                                department_id: value.department_id
                            };
                            assetDropDown.append(
                                `<option value="${value.id}" data-company_id="${cid}" data-branch_id="${bid}" data-department_id="${did}">${value.asset_name + ' | ' + value.serial_no + (value.asset_code ? (' | ' + value.asset_code) : '')}</option>`
                            );
                        });
                    } else {
                        assetDropDown.append('<option value="">No branches found</option>');
                    }
                },
                error: function () {
                    assetDropDown.html('<option value="">Failed to load branches</option>');
                }
            });
        } else {
            assetDropDown.html('<option value="">Select Branch</option>');
        }
    });

    $('#asset_id').on('change', function () {
        const rawId = $(this).val();
        const id = rawId ? String(rawId) : '';
        if (!id) {
            pendingBranchId = null;
            pendingDepartmentId = null;
            syncingFromAsset = false;
            return;
        }
        const meta = assetMeta[id];
        if (!meta || meta.company_id == null) {
            return;
        }

        pendingBranchId = meta.branch_id;
        pendingDepartmentId = meta.department_id;

        syncingFromAsset = true;
        $('#company_id').val(String(meta.company_id)).trigger('change');

        whenBranchOptionAvailable(function () {
            try {
                const pb = pendingBranchId;
                const pd = pendingDepartmentId;
                if (pb != null) {
                    suppressAssetReloadOnBranchChange = true;
                    $('#branch_id').val(String(pb)).trigger('change');
                }
                pendingDepartmentId = null;
                pendingBranchId = null;
                pendingDepartmentId = null;
            } finally {
                syncingFromAsset = false;
            }
        });
    });

    rebuildAssetMetaFromOptions($('#asset_id'));
})();
</script>
@endsection
