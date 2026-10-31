@extends('layouts/contentNavbarLayout')

@section('title', 'Create Division')

@section('content')
<h4 class="py-3 breadcrumb-wrapper mb-4">
    Create Division
</h4>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0">New Division</h5>
                <a href="{{ route('division.index') }}" class="btn btn-secondary">
                    Back to list
                </a>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('division.store') }}">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Division Name <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control @error('division_name') is-invalid @enderror"
                                name="division_name"
                                value="{{ old('division_name') }}"
                                placeholder="Enter division name"
                            >
                            @error('division_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>
                                    Inactive
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="btn btn-primary">
                            Create Division
                        </button>
                        <a href="{{ route('division.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
