@extends('layouts/contentNavbarLayout')

@section('title', 'Edit User')

@section('page-style')
<style>
    .form-container {
        background: #f8f9fa;
        border-radius: 0.5rem;
        padding: 2rem;
        border: 1px solid #e3e6f0;
        margin-bottom: 1.5rem;
    }
    /* .form-label-required::after {
        content: " *";
        color: #dc3545;
    } */
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

<h4 class="py-3 breadcrumb-wrapper mb-4">
    <span class="text-muted fw-light">Edit User</span>
</h4>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Update User</h5>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Back to list</a>
    </div>

    <div class="card-body">
        <div class="form-container">
            <form action="{{ route('employees.update', $employee->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- BASIC DETAILS --}}
                <div class="form-section-title">Basic Details</div>
                <div class="row g-3">

                    <div class="col-md-4 mb-3">
                        <label class="form-label">User Name *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $employee->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Emp Code</label>
                        <input type="text" name="emp_code" class="form-control @error('emp_code') is-invalid @enderror"
                            value="{{ old('emp_code', $employee->emp_code) }}">
                        @error('emp_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date of Joining</label>
                        <input type="date" name="date_of_joining" class="form-control"
                            value="{{ old('date_of_joining', optional($employee->date_of_joining)->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Mobile *</label>
                        <input type="text" name="mobile_number" class="form-control"
                            value="{{ old('mobile_number', $employee->mobile_number) }}" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control"
                            value="{{ old('email', $employee->email) }}" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Division</label>
                        <select name="division_id" class="form-select @error('division_id') is-invalid @enderror">
                            <option value="">Select Division</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}"
                                    {{ old('division_id', $employee->division_id) == $division->id ? 'selected' : '' }}>
                                    {{ $division->division_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('division_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>

                {{-- USER ACCOUNT --}}
                <div class="form-section-title mt-4">User Account</div>
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Is User *</label>
                        <select class="form-select" name="is_user" id="is_user">
                            <option value="no" {{ old('is_user', $employee->is_user) == 'no' ? 'selected' : '' }}>No</option>
                            <option value="yes" {{ old('is_user', $employee->is_user) == 'yes' ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                    <div class="col-md-4 @if($employee->is_user != 'yes') d-none @endif" id="password_div">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" id="emp_password" class="form-control">
                        <div class="form-text">Leave blank to keep current password</div>
                    </div>

                    <div class="col-md-4 mb-3 @if($employee->is_user != 'yes') d-none @endif" id="role_div">
                        <label class="form-label">Role *</label>
                        <select name="role_id" id="role_id" class="form-select" @if($employee->is_user == 'yes') required @endif>
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                               <option value="{{ $role->id }}"
    {{ old('role_id', $employee->roles->first()?->id ?? null) == $role->id ? 'selected' : '' }}>
    {{ $role->name }}
</option>
                            @endforeach
                        </select>
                    </div>

                     <div class="col-md-4 mb-3">
                        <label class="form-label">
                            User Status <span class="text-danger">*</span>
                        </label>
                        <select name="employee_status" id="employee_status" class="form-select @error('employee_status') is-invalid @enderror">
                            <option value="active" {{ old('employee_status', $employee->employee_status) == 'active' ? 'selected' : '' }}> Active </option>
                            <option value="inactive" {{ old('employee_status', $employee->employee_status) == 'inactive' ? 'selected' : '' }}>  Inactive </option>
                        </select>
                        @error('employee_status')  <div class="invalid-feedback">{{ $message }}</div>  @enderror
                    </div>

                </div>

                {{-- ORGANIZATION --}}
                <div class="form-section-title mt-4">Organization Details</div>
                <div class="row g-3">

                    @php
    $selectedCompanies = old(
        'company_id',
        $employee->companies->pluck('id')->toArray()
    );
@endphp

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Company *</label>
                        <select name="company_id[]" id="company_id" multiple class="form-select select2" required>
                            <option value="">Select</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}"
                                   {{ in_array($company->id, $selectedCompanies) ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                       @php
    $selectedBranches = old(
        'branch_id',
        $employee->branches->pluck('id')->toArray()
    );
@endphp

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Branch *</label>
                        <select name="branch_id[]" id="branch_id" multiple class="form-select select2" required>
                            @foreach ($branches as $branch)
                                <option value="{{$branch->id}}"
                                    data-company-id="{{ $branch->company_id }}"
                                    {{ in_array($branch->id, $selectedBranches) ? 'selected' : '' }}>
                                    {{$branch->name}}{{ $branch->company?->name ? ' ('.$branch->company->name.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                         @error('branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                     <div class="col-md-4 mb-3">
                        <label class="form-label">Departments</label>
                        <select name="department_id[]" id="department_id" multiple class="form-select select2 @error('department_id') is-invalid @enderror">
                            <option value="">Select Department</option>    
                            @foreach ($departments as $department)
                              <option value="{{$department->id}}" {{ in_array($department->id, old('department_id', $employee->departments->pluck('id')->toArray())) ? 'selected' : ''}} >{{$department->department_name}}</option>  
                            @endforeach                        
                        </select>
                        @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="mt-4 text-end">
                    <button class="btn btn-primary">Update User</button>
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection

@section('page-script')

<script>

$('.select2').select2({
    placeholder: "Select ",
    allowClear: true,
    dropdownParent: $('.form-container') // attach dropdown to container
});


// ADD HEADER WHEN DROPDOWN OPENS
$(document).on('select2:open', function (e) {

    let selectId = e.target.id;

    setTimeout(function () {

        let dropdown = $('.select2-container--open');

        // Remove old header
        dropdown.find('.select2-custom-header').remove();

        let header =
        '<div class="select2-custom-header" style="padding:8px;border-bottom:1px solid #ddd;background:#f8f9fa;">' +
        '<button type="button" class="btn btn-sm btn-primary me-2 select-all-btn" data-id="'+selectId+'">Select All</button>' +
        '<button type="button" class="btn btn-sm btn-danger clear-all-btn" data-id="'+selectId+'">Clear</button>' +
        '</div>';

        dropdown.find('.select2-dropdown').prepend(header);

    }, 10);

});


// SELECT ALL
$(document).on('click', '.select-all-btn', function (e) {

    e.preventDefault();
    e.stopPropagation();

    let selectId = $(this).data('id');
    let $select = $('#' + selectId);

    console.log(selectId);

    let values = [];

    $select.find('option').each(function () {

        if (this.value !== "" && !this.disabled) {
            values.push(this.value);
        }

    });
    
     

   setTimeout(function(){
    $select.val(values).trigger('change');
    // refresh dropdown
    $select.select2('close');
  //  $select.select2('open');
    },100);

        

    });


    // CLEAR ALL
    $(document).on('click', '.clear-all-btn', function (e) {

        e.preventDefault();
        e.stopPropagation();

        let selectId = $(this).data('id');

        $('#' + selectId).val(null).trigger('change.select2');

    });

    document.getElementById('is_user').addEventListener('change', function () {

    const show = this.value === 'yes';

    const passwordDiv = document.getElementById('password_div');
    const roleDiv = document.getElementById('role_div');

    const passwordInput = document.getElementById('emp_password');
    const roleSelect = document.getElementById('role_id');

    // Toggle UI
    passwordDiv.classList.toggle('d-none', !show);
    roleDiv.classList.toggle('d-none', !show);

    // Toggle Required Attribute
    passwordInput.required = show;
    roleSelect.required = show;

    // Reset Values When Hidden
    if (!show) {
        passwordInput.value = '';
        roleSelect.value = '';
    }
});

    const toggleEmpPassword = document.getElementById('toggleEmpPassword');
    const empPasswordInput = document.getElementById('emp_password');
    const empPasswordIcon = document.getElementById('empPasswordIcon');

    if (toggleEmpPassword) {
        toggleEmpPassword.addEventListener('click', function () {
            const isPassword = empPasswordInput.type === 'password';
            empPasswordInput.type = isPassword ? 'text' : 'password';
            empPasswordIcon.className = isPassword ? 'bx bx-show' : 'bx bx-hide';
        });
    }

</script>

<script>
    function loadBranchesForCompanies(selectedCompanies, selectedBranchIds) {
        let branchDropdown = $('#branch_id');
        selectedBranchIds = (selectedBranchIds || []).map(String);

        branchDropdown.html('<option value="">Loading...</option>');

        if (selectedCompanies && selectedCompanies.length > 0) {
            $.ajax({
                url: "{{ route('ajax.getbranch.withoutscope.bycompanies') }}",
                type: "POST",
                dataType: "json",
                data: {
                    company_ids: selectedCompanies,
                    _token: "{{ csrf_token() }}"
                },
                success: function (res) {
                    branchDropdown.empty();
                    const rows = res.data || [];

                    if (rows.length > 0) {
                        $.each(rows, function (key, value) {
                            const selected = selectedBranchIds.includes(String(value.id)) ? ' selected' : '';
                            branchDropdown.append(
                                `<option value="${value.id}" data-company-id="${value.company_id}"${selected}>${value.name} (${value.company_name})</option>`
                            );
                        });
                    } else {
                        branchDropdown.append('<option value="">No branches found</option>');
                    }

                    branchDropdown.trigger('change.select2');
                },
                error: function () {
                    branchDropdown.html('<option value="">Failed to load branches</option>');
                }
            });
        } else {
            branchDropdown.html('<option value="">Select company first</option>');
            branchDropdown.val(null).trigger('change');
        }
    }

    $('#company_id').on('change', function () {
        loadBranchesForCompanies($(this).val(), $('#branch_id').val() || []);
    });
</script>



@endsection
