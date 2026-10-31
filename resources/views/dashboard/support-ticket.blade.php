@extends('layouts/contentNavbarLayout')

@section('title', 'Support Ticket Dashboard')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/formValidation.min.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@endsection

@section('page-style')
<style>
    /* Page background override to a premium warm linen/stone gradient */
    body, .layout-page, .content-wrapper, .layout-wrapper {
        background: linear-gradient(135deg, #f5f3ef 0%, #e6e3dd 100%) !important;
    }

    /* Filters section */
    .support-ticket-filters {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.85) 0%, rgba(245, 247, 250, 0.7) 100%);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: inset 0 1px 0 0 rgba(255, 255, 255, 0.9), 0 10px 30px -10px rgba(0, 0, 0, 0.05);
    }
    .support-ticket-filters .form-label {
        font-size: 0.75rem;
        margin-bottom: 0.35rem;
        color: #64748b;
        font-weight: 600;
    }
    .support-ticket-filters__header {
        margin-bottom: 1.25rem;
    }
    .support-ticket-filters__row {
        row-gap: 0.75rem;
    }
    .support-ticket-filters__field {
        flex: 1 1 10rem;
        min-width: 10rem;
        max-width: 100%;
    }
    .support-ticket-filters__field--date {
        flex: 0 1 11rem;
        min-width: 9.5rem;
    }
    .support-ticket-filters__field .select2-container {
        width: 100% !important;
    }
    .support-ticket-filters .form-control-sm,
    .support-ticket-filters .form-select-sm {
        min-height: calc(1.5em + 0.5rem + 2px);
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);
    }
    .support-ticket-filters .select2-container--default .select2-selection--single {
        min-height: calc(1.5em + 0.5rem + 2px);
        padding: 0.25rem 0.5rem;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 0.875rem;
    }
    .support-ticket-filters .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5;
        padding-left: 0;
    }
    .support-ticket-filters .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
    }
    .support-ticket-filters__actions .btn {
        padding: 0.35rem 0.75rem;
        font-size: 0.8125rem;
        line-height: 1.25;
        border-radius: 8px;
    }

    /* 3D Dashboard Cards */
    .summary-card-container {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: inset 0 1px 0 0 rgba(255, 255, 255, 0.9), inset 0 -1px 0 0 rgba(0, 0, 0, 0.05), 0 20px 45px -20px rgba(0, 0, 0, 0.08);
    }
    .ticket-status-card {
        display: flex;
        align-items: center;
        gap: 1.15rem;
        padding: 1.25rem;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow: inset 0 1px 0 0 rgba(255, 255, 255, 0.8), inset 0 -2px 0 0 rgba(0, 0, 0, 0.04), 0 8px 20px -8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
    }
    .ticket-status-card:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: inset 0 1px 0 0 rgba(255, 255, 255, 0.9), inset 0 -1px 0 0 rgba(0, 0, 0, 0.04), 0 20px 40px -15px rgba(0, 0, 0, 0.16);
    }
    .card-icon-wrap {
        width: 46px;
        height: 46px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        box-shadow: inset 0 1px 0 0 rgba(255, 255, 255, 0.4), inset 0 -1px 0 0 rgba(0, 0, 0, 0.1), 0 4px 10px -2px rgba(0, 0, 0, 0.1);
    }
    .card-content-wrap {
        display: flex;
        flex-direction: column;
    }
    .card-title-text {
        font-size: 0.75rem;
        font-weight: 700;
        margin-bottom: 2px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .card-value-num {
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1.1;
    }

    /* Total Tickets */
    .card-total {
        background: linear-gradient(135deg, #f4f6f8 0%, #e9ecef 100%);
    }
    .card-total .card-icon-wrap {
        background: linear-gradient(135deg, #4A6B82 0%, #354F52 100%);
        color: #ffffff;
    }
    .card-total .card-title-text { color: #5c6c75; }
    .card-total .card-value-num { color: #2F3E46; }

    /* Open Tickets */
    .card-open {
        background: linear-gradient(135deg, #fefae0 0%, #f7ebd3 100%);
    }
    .card-open .card-icon-wrap {
        background: linear-gradient(135deg, #DDA15E 0%, #BC6C25 100%);
        color: #ffffff;
    }
    .card-open .card-title-text { color: #a0784d; }
    .card-open .card-value-num { color: #8C5A20; }

    /* Accepted Tickets */
    .card-accepted {
        background: linear-gradient(135deg, #f4f6f0 0%, #e2e8db 100%);
    }
    .card-accepted .card-icon-wrap {
        background: linear-gradient(135deg, #A3B19B 0%, #588157 100%);
        color: #ffffff;
    }
    .card-accepted .card-title-text { color: #72846f; }
    .card-accepted .card-value-num { color: #3B583A; }

    /* WIP Tickets */
    .card-wip {
        background: linear-gradient(135deg, #f0f4f8 0%, #dee7f0 100%);
    }
    .card-wip .card-icon-wrap {
        background: linear-gradient(135deg, #6B8BA4 0%, #1D3557 100%);
        color: #ffffff;
    }
    .card-wip .card-title-text { color: #5a778e; }
    .card-wip .card-value-num { color: #1D3557; }

    /* On Hold Tickets */
    .card-hold {
        background: linear-gradient(135deg, #f0f7f7 0%, #d8eae8 100%);
    }
    .card-hold .card-icon-wrap {
        background: linear-gradient(135deg, #A8DADC 0%, #457B9D 100%);
        color: #ffffff;
    }
    .card-hold .card-title-text { color: #628e93; }
    .card-hold .card-value-num { color: #2A5A75; }

    /* Completed Tickets */
    .card-completed {
        background: linear-gradient(135deg, #f1f5eb 0%, #dfebd4 100%);
    }
    .card-completed .card-icon-wrap {
        background: linear-gradient(135deg, #819864 0%, #283618 100%);
        color: #ffffff;
    }
    .card-completed .card-title-text { color: #6a7c55; }
    .card-completed .card-value-num { color: #283618; }

    /* Closed Tickets */
    .card-closed {
        background: linear-gradient(135deg, #faf0ed 0%, #f4ded8 100%);
    }
    .card-closed .card-icon-wrap {
        background: linear-gradient(135deg, #E0A899 0%, #9A4C3C 100%);
        color: #ffffff;
    }
    .card-closed .card-title-text { color: #b07e74; }
    .card-closed .card-value-num { color: #7B2F21; }

    /* Rejected Tickets */
    .card-rejected {
        background: linear-gradient(135deg, #fdf1ed 0%, #fadbd0 100%);
    }
    .card-rejected .card-icon-wrap {
        background: linear-gradient(135deg, #C15C3D 0%, #7B2612 100%);
        color: #ffffff;
    }
    .card-rejected .card-title-text { color: #b27563; }
    .card-rejected .card-value-num { color: #7B2612; }

    .card-purchase {
        background: linear-gradient(135deg, #f4f1f8 0%, #e4dced 100%);
    }
    .card-purchase .card-icon-wrap {
        background: linear-gradient(135deg, #7C6A9A 0%, #3D3354 100%);
        color: #ffffff;
    }
    .card-purchase .card-title-text { color: #6d5d86; }
    .card-purchase .card-value-num { color: #3D3354; }

    .card-custom {
        background: linear-gradient(135deg, #f7f7f8 0%, #ececef 100%);
    }
    .card-custom .card-icon-wrap {
        background: var(--status-color, #6B8BA4);
        color: #ffffff;
    }
    .card-custom .card-title-text { color: #5c6c75; }
    .card-custom .card-value-num { color: #2F3E46; }

    /* 3D Matrices Cards & Tables */
    .ticket-matrix-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 20px;
        box-shadow: inset 0 1px 0 0 rgba(255, 255, 255, 0.8), 0 15px 35px -15px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .ticket-matrix-card:hover {
        box-shadow: inset 0 1px 0 0 rgba(255, 255, 255, 0.9), 0 25px 45px -20px rgba(0, 0, 0, 0.12);
    }
    .dept-table-scroll {
        max-height: 420px;
        overflow-y: auto;
    }
    .dept-table-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 1;
    }
    .dept-name-cell {
        max-width: 16rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .recent-ticket-subject {
        max-width: 16rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }
    .invoice-list-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }
    .invoice-list-table th {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        font-weight: 700;
        color: #475569;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.85rem 1rem;
    }
    .invoice-list-table td {
        padding: 0.95rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.875rem;
        color: #334155;
        transition: background-color 0.2s ease;
    }
    .invoice-list-table tbody tr:hover td {
        background-color: #f8fafc;
    }

    /* Elegant Muted 3D Badges */
    .badge.bg-label-secondary {
        background: linear-gradient(135deg, #f4f6f8 0%, #e9ecef 100%) !important;
        color: #4A6B82 !important;
        border: 1px solid rgba(74, 107, 130, 0.3);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.6), 0 2px 4px rgba(0,0,0,0.03);
        font-weight: 700;
        padding: 0.35em 0.65em;
        border-radius: 6px;
    }
    .badge.bg-label-warning {
        background: linear-gradient(135deg, #fefae0 0%, #f7ebd3 100%) !important;
        color: #8C5A20 !important;
        border: 1px solid rgba(221, 161, 94, 0.3);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.6), 0 2px 4px rgba(0,0,0,0.03);
        font-weight: 700;
        padding: 0.35em 0.65em;
        border-radius: 6px;
    }
    .badge.bg-label-info {
        background: linear-gradient(135deg, #f0f7f7 0%, #d8eae8 100%) !important;
        color: #2A5A75 !important;
        border: 1px solid rgba(168, 218, 220, 0.4);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.6), 0 2px 4px rgba(0,0,0,0.03);
        font-weight: 700;
        padding: 0.35em 0.65em;
        border-radius: 6px;
    }
    .badge.bg-label-primary {
        background: linear-gradient(135deg, #f1f5eb 0%, #dfebd4 100%) !important;
        color: #283618 !important;
        border: 1px solid rgba(129, 152, 100, 0.3);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.6), 0 2px 4px rgba(0,0,0,0.03);
        font-weight: 700;
        padding: 0.35em 0.65em;
        border-radius: 6px;
    }
    .badge.bg-label-success {
        background: linear-gradient(135deg, #faf0ed 0%, #f4ded8 100%) !important;
        color: #7B2F21 !important;
        border: 1px solid rgba(224, 168, 153, 0.4);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.6), 0 2px 4px rgba(0,0,0,0.03);
        font-weight: 700;
        padding: 0.35em 0.65em;
        border-radius: 6px;
    }
</style>
@endsection

@section('content')
 
<div class="support-ticket-filters mb-4">
    <form id="supportTicketFilterForm" method="GET" action="{{ route('dashboard.supportticket') }}">
        <input type="hidden" name="export_format" id="export_format" value="">
        <div class="support-ticket-filters__header">
            <h4 class="h5 mb-0 fw-semibold">Support Ticket Dashboard</h4>
        </div>
        <div class="support-ticket-filters__row d-flex flex-wrap align-items-end gap-3">
            <div class="support-ticket-filters__field">
                <label for="company_id" class="form-label">Company</label>
                <select id="company_id" name="company_id"
                        class="form-select form-select-sm @error('company_id') is-invalid @enderror">
                    <option value="">All Companies</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}"
                            {{ request('company_id') == $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="support-ticket-filters__field">
                <label for="branch_id" class="form-label">Branch</label>
                <select id="branch_id" name="branch_id"
                        class="form-select form-select-sm select2 @error('branch_id') is-invalid @enderror" data-width="100%">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}"
                            {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="support-ticket-filters__field">
                <label for="division_id" class="form-label">Division</label>
                <select name="division_id" id="division_id" class="form-select form-select-sm">
                    <option value="">All Divisions</option>
                    @foreach($divisions as $division)
                        <option value="{{ $division->id }}"
                            {{ request('division_id') == $division->id ? 'selected' : '' }}>
                            {{ $division->division_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="support-ticket-filters__field">
                <label for="collaborator_id" class="form-label">Collaborators</label>
                <select name="collaborator_id" id="collaborator_id" class="form-select form-select-sm">
                    <option value="">All Collaborators</option>
                    @foreach($collaboratorUsers as $collaboratorUser)
                        <option value="{{ $collaboratorUser->id }}"
                            {{ request('collaborator_id') == $collaboratorUser->id ? 'selected' : '' }}>
                            {{ $collaboratorUser->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="support-ticket-filters__field support-ticket-filters__field--date">
                <label for="from_date" class="form-label">From date</label>
                <input type="date" id="from_date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
            </div>
            <div class="support-ticket-filters__field support-ticket-filters__field--date">
                <label for="to_date" class="form-label">To date</label>
                <input type="date" id="to_date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
            </div>
            <div class="support-ticket-filters__actions d-flex flex-wrap align-items-end gap-2 pt-1 pt-md-0 ms-md-auto">
                <button type="submit" id="goBtn" class="btn btn-sm btn-primary">GO</button>
                <button type="button" id="clearFilterBtn" class="btn btn-sm btn-outline-secondary">Clear</button>
                <button type="button" id="downloadExcelBtn" class="btn btn-sm btn-success">Excel</button>
                <button type="button" id="downloadPdfBtn" class="btn btn-sm btn-danger">PDF</button>
            </div>
        </div>
    </form>
</div>


    <div class="row g-6">

    <div class="col-md-12 order-0 mb-2">
        <div class="summary-card-container">
            <h5 class="mb-4 fw-semibold text-heading"><i class="bx bx-chart me-2"></i>Support Ticket Summary</h5>
            
            <!-- Row 1: 4 cards -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-md-3">
                    <a href="{{ route('support-ticket.index', request()->query()) }}" class="text-reset text-decoration-none">
                        <div class="ticket-status-card card-total">
                            <div class="card-icon-wrap">
                                <i class="icon-base bx bx-list-ul icon-md" style="font-size: 1.8rem;"></i>
                            </div>
                            <div class="card-content-wrap">
                                <span class="card-title-text">Total Tickets</span>
                                <span class="card-value-num">{{ $dashboard['total_count'] ?? 0 }}</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-md-3">
                    <a href="{{ route('support-ticket.index', array_merge(request()->query(), ['status' => 'open'])) }}" class="text-reset text-decoration-none">
                        <div class="ticket-status-card card-open">
                            <div class="card-icon-wrap">
                                <svg width="24" height="24" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5.9375 26.125V10.6875C5.9375 10.0576 6.18772 9.45352 6.63312 9.00812C7.07852 8.56272 7.68261 8.3125 8.3125 8.3125H29.6875C30.3174 8.3125 30.9215 8.56272 31.3669 9.00812C31.8123 9.45352 32.0625 10.0576 32.0625 10.6875V26.125H5.9375Z" fill="currentColor"></path>
                                    <path d="M5.9375 26.125V10.6875C5.9375 10.0576 6.18772 9.45352 6.63312 9.00812C7.07852 8.56272 7.68261 8.3125 8.3125 8.3125H29.6875C30.3174 8.3125 30.9215 8.56272 31.3669 9.00812C31.8123 9.45352 32.0625 10.0576 32.0625 10.6875V26.125M21.375 13.0625H16.625M3.5625 26.125H34.4375V28.5C34.4375 29.1299 34.1873 29.734 33.7419 30.1794C33.2965 30.6248 32.6924 30.875 32.0625 30.875H5.9375C5.30761 30.875 4.70352 30.6248 4.25812 30.1794C3.81272 29.734 3.5625 29.1299 3.5625 28.5V26.125Z" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </div>
                            <div class="card-content-wrap">
                                <span class="card-title-text">Open Tickets</span>
                                <span class="card-value-num">{{ $dashboard['open_count'] ?? 0 }}</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-md-3">
                    <a href="{{ route('support-ticket.index', array_merge(request()->query(), ['status' => 'accepted'])) }}" class="text-reset text-decoration-none">
                        <div class="ticket-status-card card-accepted">
                            <div class="card-icon-wrap">
                                <svg width="24" height="24" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M25.1509 6.46609C23.2675 5.17419 21.0251 4.50807 18.7418 4.5622L18.7411 4.56221C18.4983 4.56781 18.2574 4.58151 18.0187 4.60305L18.6951 2.56275C21.398 2.49881 24.0526 3.28743 26.2822 4.8168C28.512 6.34629 30.2037 8.53899 31.1173 11.0839C32.031 13.6289 32.1201 16.3969 31.3722 18.9954C30.6243 21.5938 29.0772 23.8909 26.9505 25.5607L26.9445 25.5654L26.9445 25.5654C26.6318 25.8071 26.3785 26.1171 26.204 26.4717C26.0295 26.8263 25.9384 27.2161 25.9376 27.6113V28.5C25.9376 29.0801 25.7072 29.6365 25.2969 30.0468C24.8867 30.457 24.3303 30.6875 23.7501 30.6875H14.2501C13.67 30.6875 13.1136 30.457 12.7033 30.0468C12.2931 29.6365 12.0626 29.0801 12.0626 28.5V27.6131C12.0595 27.2206 11.9683 26.8339 11.7957 26.4815C11.6232 26.1289 11.3737 25.8196 11.0656 25.5764L11.7414 23.5378C11.9208 23.6976 12.1057 23.8517 12.296 23.9996L11.6821 24.7891L12.301 24.0035C12.8459 24.4328 13.2871 24.9792 13.5921 25.6022C13.8971 26.2252 14.0579 26.9089 14.0626 27.6025L14.0627 27.6094L14.0626 28.5C14.0626 28.5497 14.0824 28.5974 14.1175 28.6326C14.1527 28.6677 14.2004 28.6875 14.2501 28.6875H23.7501C23.7999 28.6875 23.8475 28.6677 23.8827 28.6326C23.9179 28.5974 23.9376 28.5497 23.9376 28.5V27.6094L23.9376 27.6074C23.939 26.9073 24.1004 26.2167 24.4096 25.5885C24.7181 24.9616 25.1657 24.4133 25.7181 23.9855C27.5131 22.5752 28.8188 20.6359 29.4502 18.4422C30.082 16.2473 30.0067 13.9093 29.235 11.7597C28.4633 9.61009 27.0344 7.75799 25.1509 6.46609ZM11.7414 23.5378L11.7414 23.5378L18.0187 4.60305L18.018 4.6031L18.6944 2.56276C11.7043 2.72418 6.09331 8.53234 6.12513 15.5156C6.13159 17.458 6.57998 19.3733 7.43632 21.1167C8.29225 22.8593 9.53332 24.3843 11.0656 25.5764L11.7414 23.5378ZM11.7414 23.5378C10.7009 22.6109 9.84781 21.4898 9.23145 20.235C8.50882 18.7638 8.13049 17.1475 8.12512 15.5084L8.12512 15.5071C8.09905 9.84987 12.4637 5.10456 18.018 4.6031L11.7414 23.5378ZM12.0627 34.4375C12.0627 33.8852 12.5104 33.4375 13.0627 33.4375H24.9377C25.49 33.4375 25.9377 33.8852 25.9377 34.4375C25.9377 34.9898 25.49 35.4375 24.9377 35.4375H13.0627C12.5104 35.4375 12.0627 34.9898 12.0627 34.4375ZM20.3697 7.44532C19.8252 7.35302 19.3089 7.71961 19.2166 8.26412C19.1243 8.80864 19.4909 9.32489 20.0354 9.41719C21.2827 9.62862 22.4336 10.222 23.3292 11.1154C24.2249 12.0087 24.8212 13.1581 25.0358 14.4048C25.1295 14.9491 25.6467 15.3144 26.191 15.2207C26.7353 15.127 27.1005 14.6098 27.0068 14.0655C26.722 12.4107 25.9305 10.8851 24.7417 9.69934C23.5528 8.51353 22.0252 7.72596 20.3697 7.44532ZM12.0627 34.4375H25.9377V35.4375H12.0627V34.4375Z" fill="currentColor"></path>
                                </svg>
                            </div>
                            <div class="card-content-wrap">
                                <span class="card-title-text">Accepted Tickets</span>
                                <span class="card-value-num">{{ $dashboard['accepted_count'] ?? 0 }}</span>
                            </div>
                        </div>
                    </a>
                </div>

                                @foreach($statusData->where('kind', 'progress') as $status)
                <div class="col-sm-6 col-md-3">
                    <a href="{{ route('support-ticket.index', array_merge(request()->query(), ['status' => $status['slug']])) }}" class="text-reset text-decoration-none">
                        <div class="ticket-status-card {{ $status['card_class'] }}" @if($status['card_class'] === 'card-custom') style="--status-color: {{ $status['color'] }}" @endif>
                            <div class="card-icon-wrap">
                                <i class="icon-base bx {{ $status['slug'] === 'hold' ? 'bx-pause-circle' : ($status['slug'] === 'in_purchase' ? 'bx-cart' : 'bx-cog') }} icon-md" style="font-size: 1.8rem;"></i>
                            </div>
                            <div class="card-content-wrap">
                                <span class="card-title-text">{{ $status['label'] }}</span>
                                <span class="card-value-num">{{ $status['count'] }}</span>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach

                <div class="col-sm-6 col-md-3">
                    <a href="{{ route('support-ticket.index', array_merge(request()->query(), ['status' => 'completed'])) }}" class="text-reset text-decoration-none">
                        <div class="ticket-status-card card-completed">
                            <div class="card-icon-wrap">
                                <svg width="24" height="24" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M25.1509 6.46609C23.2675 5.17419 21.0251 4.50807 18.7418 4.5622L18.7411 4.56221C18.4983 4.56781 18.2574 4.58151 18.0187 4.60305L18.6951 2.56275C21.398 2.49881 24.0526 3.28743 26.2822 4.8168C28.512 6.34629 30.2037 8.53899 31.1173 11.0839C32.031 13.6289 32.1201 16.3969 31.3722 18.9954C30.6243 21.5938 29.0772 23.8909 26.9505 25.5607L26.9445 25.5654L26.9445 25.5654C26.6318 25.8071 26.3785 26.1171 26.204 26.4717C26.0295 26.8263 25.9384 27.2161 25.9376 27.6113V28.5C25.9376 29.0801 25.7072 29.6365 25.2969 30.0468C24.8867 30.457 24.3303 30.6875 23.7501 30.6875H14.2501C13.67 30.6875 13.1136 30.457 12.7033 30.0468C12.2931 29.6365 12.0626 29.0801 12.0626 28.5V27.6131C12.0595 27.2206 11.9683 26.8339 11.7957 26.4815C11.6232 26.1289 11.3737 25.8196 11.0656 25.5764L11.7414 23.5378C11.9208 23.6976 12.1057 23.8517 12.296 23.9996L11.6821 24.7891L12.301 24.0035C12.8459 24.4328 13.2871 24.9792 13.5921 25.6022C13.8971 26.2252 14.0579 26.9089 14.0626 27.6025L14.0627 27.6094L14.0626 28.5C14.0626 28.5497 14.0824 28.5974 14.1175 28.6326C14.1527 28.6677 14.2004 28.6875 14.2501 28.6875H23.7501C23.7999 28.6875 23.8475 28.6677 23.8827 28.6326C23.9179 28.5974 23.9376 28.5497 23.9376 28.5V27.6094L23.9376 27.6074C23.939 26.9073 24.1004 26.2167 24.4096 25.5885C24.7181 24.9616 25.1657 24.4133 25.7181 23.9855C27.5131 22.5752 28.8188 20.6359 29.4502 18.4422C30.082 16.2473 30.0067 13.9093 29.235 11.7597C28.4633 9.61009 27.0344 7.75799 25.1509 6.46609ZM11.7414 23.5378L11.7414 23.5378L18.0187 4.60305L18.018 4.6031L18.6944 2.56276C11.7043 2.72418 6.09331 8.53234 6.12513 15.5156C6.13159 17.458 6.57998 19.3733 7.43632 21.1167C8.29225 22.8593 9.53332 24.3843 11.0656 25.5764L11.7414 23.5378ZM11.7414 23.5378C10.7009 22.6109 9.84781 21.4898 9.23145 20.235C8.50882 18.7638 8.13049 17.1475 8.12512 15.5084L8.12512 15.5071C8.09905 9.84987 12.4637 5.10456 18.018 4.6031L11.7414 23.5378ZM12.0627 34.4375C12.0627 33.8852 12.5104 33.4375 13.0627 33.4375H24.9377C25.49 33.4375 25.9377 33.8852 25.9377 34.4375C25.9377 34.9898 25.49 35.4375 24.9377 35.4375H13.0627C12.5104 35.4375 12.0627 34.9898 12.0627 34.4375ZM20.3697 7.44532C19.8252 7.35302 19.3089 7.71961 19.2166 8.26412C19.1243 8.80864 19.4909 9.32489 20.0354 9.41719C21.2827 9.62862 22.4336 10.222 23.3292 11.1154C24.2249 12.0087 24.8212 13.1581 25.0358 14.4048C25.1295 14.9491 25.6467 15.3144 26.191 15.2207C26.7353 15.127 27.1005 14.6098 27.0068 14.0655C26.722 12.4107 25.9305 10.8851 24.7417 9.69934C23.5528 8.51353 22.0252 7.72596 20.3697 7.44532ZM12.0627 34.4375H25.9377V35.4375H12.0627V34.4375Z" fill="currentColor"></path>
                                </svg>
                            </div>
                            <div class="card-content-wrap">
                                <span class="card-title-text">Completed Tickets</span>
                                <span class="card-value-num">{{ $dashboard['completed_count'] ?? 0 }}</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-md-3">
                    <a href="{{ route('support-ticket.index', array_merge(request()->query(), ['status' => 'closed'])) }}" class="text-reset text-decoration-none">
                        <div class="ticket-status-card card-closed">
                            <div class="card-icon-wrap">
                                <svg width="24" height="24" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8.08984 29.9102C6.72422 28.5445 7.62969 25.6797 6.93203 24.0023C6.23438 22.325 3.5625 20.8555 3.5625 19C3.5625 17.1445 6.20469 15.7344 6.93203 13.9977C7.65938 12.2609 6.72422 9.45547 8.08984 8.08984C9.45547 6.72422 12.3203 7.62969 13.9977 6.93203C15.675 6.23438 17.1445 3.5625 19 3.5625C20.8555 3.5625 22.2656 6.20469 24.0023 6.93203C25.7391 7.65938 28.5445 6.72422 29.9102 8.08984C31.2758 9.45547 30.3703 12.3203 31.068 13.9977C31.7656 15.675 34.4375 17.1445 34.4375 19C34.4375 20.8555 31.7953 22.2656 31.068 24.0023C30.3406 25.7391 31.2758 28.5445 29.9102 29.9102C28.5445 31.2758 25.6797 30.3703 24.0023 31.068C22.325 31.7656 20.8555 34.4375 19 34.4375C17.1445 34.4375 15.7344 31.7953 13.9977 31.068C12.2609 30.3406 9.45547 31.2758 8.08984 29.9102Z" fill="currentColor"></path>
                                    <path d="M25.5312 15.4375L16.818 23.75L12.4687 19.5937M8.08984 29.9102C6.72422 28.5445 7.62969 25.6797 6.93203 24.0023C6.23437 22.325 3.5625 20.8555 3.5625 19C3.5625 17.1445 6.20469 15.7344 6.93203 13.9977C7.65937 12.2609 6.72422 9.45547 8.08984 8.08984C9.45547 6.72422 12.3203 7.62969 13.9977 6.93203C15.675 6.23437 17.1445 3.5625 19 3.5625C20.8555 3.5625 22.2656 6.20469 24.0023 6.93203C25.7391 7.65937 28.5445 6.72422 29.9102 8.08984C31.2758 9.45547 30.3703 12.3203 31.068 13.9977C31.7656 15.675 34.4375 17.1445 34.4375 19C34.4375 20.8555 31.7953 22.2656 31.068 24.0023C30.3406 25.7391 31.2758 28.5445 29.9102 29.9102C28.5445 31.2758 25.6797 30.3703 24.0023 31.068C22.325 31.7656 20.8555 34.4375 19 34.4375C17.1445 34.4375 15.7344 31.7953 13.9977 31.068C12.2609 30.3406 9.45547 31.2758 8.08984 29.9102Z" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </div>
                            <div class="card-content-wrap">
                                <span class="card-title-text">Closed Tickets</span>
                                <span class="card-value-num">{{ $dashboard['closed_count'] ?? 0 }}</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-md-3">
                    <a href="{{ route('support-ticket.index', array_merge(request()->query(), ['status' => 'rejected'])) }}" class="text-reset text-decoration-none">
                        <div class="ticket-status-card card-rejected">
                            <div class="card-icon-wrap">
                                <i class="icon-base bx bx-x-circle icon-md" style="font-size: 1.8rem;"></i>
                            </div>
                            <div class="card-content-wrap">
                                <span class="card-title-text">Rejected Tickets</span>
                                <span class="card-value-num">{{ $dashboard['rejected_count'] ?? 0 }}</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

     <div class="col-md-6 align-self-start">

        <div class="card ticket-matrix-card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title m-0 me-2">Tickets by Priority</h5>
       
      </div>
      <div class="card-datatable table-responsive">
        <table class="invoice-list-table table table-border-top-0">
          <thead>
            <tr>
              <th><span class="badge bg-label-secondary">Priority</span></th>
              @foreach($matrixStatuses as $status)
              <th><span class="badge {{ \App\Models\SupportTicketProgressStatus::matrixBadgeClass($status['slug']) }}">{{ $status['short'] }}</span></th>
              @endforeach
            </tr>
          </thead>
          <tbody>
              @foreach (['high' => 'High', 'normal' => 'Medium', 'low' => 'Low'] as $key => $label)
            <tr>
                <td class="fw-medium">{{ $label }}</td>
                @foreach($matrixStatuses as $status)
                <td align="center">{{ $dashboard['priorityMatrix'][$key][$status['slug']] ?? 0 }}</td>
                @endforeach
            </tr>
        @endforeach
          </tbody>
        </table>
      </div>
      
      <!-- Support Tickets by Status Chart -->
      <div class="card-body d-flex flex-column align-items-center justify-content-center border-top pt-4">
          <h6 class="mb-3 fw-semibold">Support Tickets by Status</h6>
          <canvas id="ticketStatusChart" height="250"></canvas>
      </div>
    </div>

     </div>

      <div class="col-md-6 align-self-start">

        <div class="card ticket-matrix-card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title m-0 me-2">Tickets by Divisions</h5>
       
      </div>
      <div class="card-datatable table-responsive dept-table-scroll">
        <table class="invoice-list-table table table-border-top-0">
          <thead>
            <tr>
              <th><span class="badge bg-label-secondary">Division</span></th>
              @foreach($matrixStatuses as $status)
              <th><span class="badge {{ \App\Models\SupportTicketProgressStatus::matrixBadgeClass($status['slug']) }}">{{ $status['short'] }}</span></th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @php
                $divisionsWithTickets = $divisions->filter(function ($division) use ($divisionMatrix) {
                    return collect($divisionMatrix[$division->id] ?? [])->sum() > 0;
                });
            @endphp
            @forelse ($divisionsWithTickets as $division)
                    <tr>
                        <td class="fw-medium dept-name-cell" title="{{ $division->division_name }}">
                            {{ $division->division_name }}
                        </td>

                        @foreach($matrixStatuses as $status)
                        <td align="center">
                            {{ $divisionMatrix[$division->id][$status['slug']] ?? 0 }}
                        </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $matrixStatuses->count() + 1 }}" class="text-center">
                            No tickets in any division
                        </td>
                    </tr>
                @endforelse
             
            
          </tbody>
        </table>
      </div>
    </div>

        <div class="card ticket-matrix-card mt-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0 me-2">Recent Support Tickets</h5>
                <a href="{{ route('support-ticket.index', request()->except('export_format')) }}" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                        <tr>
                            <td>
                                <a href="{{ route('support-ticket.show', $ticket->id) }}" class="fw-medium text-body recent-ticket-subject">
                                    {{ $ticket->displayTicketNumber() }} {{ $ticket->subject }}
                                </a>
                                <small class="text-muted d-block">
                                    {{ $ticket->branch?->name ?? 'No branch' }}
                                    · {{ $ticket->division?->division_name ?? 'No division' }}
                                </small>
                                <small class="text-muted d-block">
                                    {{ $ticket->creator?->name ?? 'Unknown' }}
                                    · {{ $ticket->created_at?->format('d M Y') }}
                                </small>
                            </td>
                            <td>{!! $ticket->priority_label !!}</td>
                            <td>{!! $ticket->status_label !!}</td>
                            <td class="text-nowrap">{{ $ticket->due_date?->format('d M Y') ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No tickets found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

     </div>


    </div>






 
    
@endsection

@section('page-script') 

<script>
    const ctx = document.getElementById('ticketStatusChart');

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: @json($statusData->pluck('label')->values()),
            datasets: [{
                data: @json($statusData->pluck('count')->values()),
                backgroundColor: @json($statusData->pluck('color')->values()),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });


     $('.select2').select2({
    placeholder: "Select Branch",
    allowClear: true,
    closeOnSelect: true, 
    width: '100%'
});

  const OLD_BRANCH = "{{ request('branch_id') }}";

  function loadBranches(companyId, selectedId = null) {
            const branchDropdown = $('#branch_id');
            branchDropdown.html('<option>Loading...</option>');

            if (!companyId) {
                branchDropdown.html('<option value="">Select Branch</option>');
                return;
            }

            $.get(
                "{{ route('ajax.getbranch.bycompany', ':id') }}".replace(':id', companyId),
                function (res) {
                    branchDropdown.empty().append('<option value="">Select Branch</option>');

                    if (res.data && res.data.length > 0) {
                        $.each(res.data, function (_, value) {
                            let selected = selectedId == value.id ? 'selected' : '';
                            branchDropdown.append(
                                `<option value="${value.id}" ${selected}>${value.name}</option>`
                            );
                        });
                    } else {
                        branchDropdown.append('<option value="">No branches found</option>');
                    }
                }
            ).fail(function () {
                branchDropdown.html('<option value="">Failed to load branches</option>');
            });
        }

        function loadCollaborators(branchId, selectedId = null) {
            const collaboratorDropdown = $('#collaborator_id');

            if (!branchId) {
                collaboratorDropdown.html('<option value="">All Collaborators</option>');
                return;
            }

            collaboratorDropdown.html('<option value="">Loading...</option>');

            $.get(
                "{{ route('ajax.getusers.bybranch', ':id') }}".replace(':id', branchId),
                { for_filter: 1, division_id: $('#division_id').val() || '' },
                function (res) {
                    collaboratorDropdown.empty().append('<option value="">All Collaborators</option>');

                    if (res.data && res.data.length > 0) {
                        $.each(res.data, function (_, value) {
                            let selected = selectedId == value.id ? 'selected' : '';
                            collaboratorDropdown.append(
                                `<option value="${value.id}" ${selected}>${value.name}</option>`
                            );
                        });
                    }
                }
            ).fail(function () {
                collaboratorDropdown.html('<option value="">Failed to load collaborators</option>');
            });
        }

        $('#company_id').on('change', function () {
            loadBranches(this.value);
            $('#collaborator_id').html('<option value="">All Collaborators</option>');
        });

        $('#branch_id').on('change', function () {
            loadCollaborators(this.value);
        });

        $('#division_id').on('change', function () {
            if ($('#branch_id').val()) {
                loadCollaborators($('#branch_id').val(), "{{ request('collaborator_id') }}");
            }
        });

        @if(request('branch_id'))
            loadCollaborators("{{ request('branch_id') }}", "{{ request('collaborator_id') }}");
        @endif

        $('#clearFilterBtn').on('click', function () {
            window.location.href = "{{ route('dashboard.supportticket') }}";
        });

        function submitDashboardExport(format) {
            const form = document.getElementById('supportTicketFilterForm');
            const exportFormat = document.getElementById('export_format');
            if (!form || !exportFormat) {
                return;
            }

            exportFormat.value = format;
            form.submit();
            exportFormat.value = '';
        }

        $('#downloadPdfBtn').on('click', function (e) {
            e.preventDefault();
            submitDashboardExport('pdf');
        });

        $('#downloadExcelBtn').on('click', function (e) {
            e.preventDefault();
            submitDashboardExport('excel');
        });

        $('#goBtn').on('click', function () {
            const exportFormat = document.getElementById('export_format');
            if (exportFormat) {
                exportFormat.value = '';
            }
        });

</script>

@endsection
