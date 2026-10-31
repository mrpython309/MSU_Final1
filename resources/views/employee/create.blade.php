@extends('layouts/contentNavbarLayout')

@section('title', 'Create User')

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
    <span class="text-muted fw-light">Create User</span>
</h4>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">New User</h5>
        <a href="{{route('employees.index')}}" class="btn btn-secondary">Back to list</a>
    </div>

    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Please fix the following:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="form-container">
            <form action="{{route('employees.store')}}" method="POST">
                @csrf

                {{-- BASIC DETAILS --}}
                <div class="form-section-title">Basic Details</div>
                <div class="row g-3">

                    <div class="col-md-4 mb-3">
                        <label class="form-label form-label-required">User Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Enter User Name" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Emp Code</label>
                        <input type="text" name="emp_code" class="form-control @error('emp_code') is-invalid @enderror" value="{{ old('emp_code') }}" placeholder="Enter Emp Code">
                        @error('emp_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>                    

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date of Joining</label>
                        <input type="date" name="date_of_joining" class="form-control @error('date_of_joining') is-invalid @enderror" value="{{ old('date_of_joining') }}">
                        @error('date_of_joining') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label form-label-required"> Mobile Number <span class="text-danger">*</span> </label>
                        <input type="text" name="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror" value="{{ old('mobile_number') }}" placeholder="Enter Mobile Number" required>
                        @error('mobile_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label form-label-required"> Email ID <span class="text-danger">*</span> </label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="Enter Email" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>                    

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Division</label>
                        <select name="division_id" id="division_id" class="form-select @error('division_id') is-invalid @enderror">
                            <option value="">Select Division</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}" @selected((string) old('division_id') === (string) $division->id)>{{ $division->division_name }}</option>
                            @endforeach
                        </select>
                        @error('division_id') <div class="invalid-feedback">{{ $message }}</div>  @enderror
                    </div>

                    
                      

                </div>

                {{-- USER DETAILS --}}
                <div class="form-section-title mt-4">User Account</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label form-label-required">Is User <span class="text-danger">*</span></label>
                        <select class="form-select" name="is_user" id="is_user">
                            <option value="yes" @selected(old('is_user', 'yes') === 'yes')>Yes</option>
                            <option value="no" @selected(old('is_user', 'yes') === 'no')>No</option>
                        </select>
                    </div>

                    <div class="col-md-4 {{ old('is_user', 'yes') === 'yes' ? '' : 'd-none' }}" id="password_div">
                        <label class="form-label form-label-required">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password" id="emp_password" class="form-control @error('password') is-invalid @enderror" {{ old('is_user', 'yes') === 'yes' ? 'required' : '' }}>
                            <button class="btn btn-outline-secondary" type="button" id="toggleEmpPassword"> <i class="bx bx-hide" id="empPasswordIcon"></i>
                            </button>
                        </div>
                        <div class="form-text">Password must be at least 8 characters</div>
                        @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>   
                    
                    <div class="col-md-4 mb-3 {{ old('is_user', 'yes') === 'yes' ? '' : 'd-none' }}" id="role_div">
                        <label class="form-label form-label-required"> Role <span class="text-danger">*</span> </label>
                        <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror" {{ old('is_user', 'yes') === 'yes' ? 'required' : '' }}>
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)> {{ $role->name }} </option>
                            @endforeach
                        </select>
                        @error('role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                   

                    <div class="col-md-4 mb-3">
                        <label class="form-label">
                            User Status <span class="text-danger">*</span>
                        </label>
                        <select name="employee_status" id="employee_status" class="form-select @error('employee_status') is-invalid @enderror">
                            <option value="active" @selected(old('employee_status', 'active') === 'active')> Active </option>
                            <option value="inactive" @selected(old('employee_status') === 'inactive')>  Inactive </option>
                        </select>
                        @error('employee_status')  <div class="invalid-feedback">{{ $message }}</div>  @enderror
                    </div>

                </div>

                {{-- ORGANIZATION DETAILS --}}
                <div class="form-section-title mt-4 " id="organization_title">Organization Details </div>
                <div class="row g-3" id="organization">
                   
                    <div class="col-md-4 mb-3">
                        <label class="form-label"> Company <span class="text-danger">*</span> </label>
                        <select name="company_id[]" id="company_id" class="form-select select2 @error('company_id') is-invalid @enderror" multiple required>
                            @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected(in_array((string) $company->id, array_map('strval', (array) old('company_id', [])), true))>  {{ $company->name }}  </option>
                            @endforeach
                        </select>
                        @error('company_id') <div class="invalid-feedback">{{ $message }}</div>  @enderror
                    </div>                    

                    <div class="col-md-4 mb-3">
                        <label class="form-label">  Branch <span class="text-danger">*</span> </label>
                        <select name="branch_id[]" id="branch_id" multiple class="form-select select2 @error('branch_id') is-invalid @enderror" required>
                            @forelse(($branches ?? collect()) as $branch)
                                <option value="{{ $branch->id }}"
                                    data-company-id="{{ $branch->company_id }}"
                                    @selected(in_array((string) $branch->id, array_map('strval', (array) old('branch_id', [])), true))>
                                    {{ $branch->name }}{{ $branch->company?->name ? ' ('.$branch->company->name.')' : '' }}
                                </option>
                            @empty
                                <option value="">Select company first</option>
                            @endforelse
                        </select>
                        @error('branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Departments</label>
                        <select name="department_id[]" id="department_id" multiple class="form-select select2 @error('department_id') is-invalid @enderror">
                            @foreach ($departments as $department)
                              <option value="{{$department->id}}" @selected(in_array((string) $department->id, array_map('strval', (array) old('department_id', [])), true))>{{$department->department_name}}</option>  
                            @endforeach                        
                        </select>
                        @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                </div>

                {{-- ACTIONS --}}
                <div class="mt-4 text-end">
                    <button class="btn btn-primary">Save User</button>
                    <a href="{{route('employees.index')}}" class="btn btn-outline-secondary">Cancel</a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
// INIT SELECT2
$('.select2').select2({
    placeholder: "Select",
    allowClear: true,
    closeOnSelect: true,
    dropdownParent: $('.form-container'),
    width: '100%'
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
