@extends('layouts/contentNavbarLayout')

@section('title', 'Import Asset')

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
    <span class="text-muted fw-light">Import Asset</span>
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
        <h5 class="mb-0">Import Asset</h5>
        <a href="{{route('asset.index')}}" class="btn btn-secondary">Back to list</a>
    </div>

    <div class="card-body">

        

        <div class="form-container">
            <form action="{{ route('asset.import.process') }}" method="POST" enctype="multipart/form-data"> 
    @csrf

    
    <div class="row g-3">

        

        <div class="col-md-4">
            <label class="form-label">Asset File  <span class="text-danger">*</span> </label>
            <input class="form-control @error('filename') is-invalid @enderror" type="file" id="assetfile" name="assetfile" accept=".csv,.xls,.xlsx" required>              
            @error('filename')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            
          </div>

        <div class="col-md-12 pt-4">
                <button type="submit" class="btn btn-primary ">Import</button>
        <a href="{{ route('asset.index') }}" class="btn btn-outline-secondary">Cancel</a>
   
     </div>


    

        

    </div>

    {{-- ACTIONS --}}
    <div class="mt-4 text-end">
       </div>
</form>


        </div>
    </div>
</div>

<div class="row mt-4">

 <div class="col-md-12">

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Asset Import Instructions</h5>
    </div>

    <div class="card-body">

        <a href="{{asset('sample_data_to_import.xlsx')}}" download="download" class="btn btn-primary mb-3">Download Sample File</a>

        <!-- File Format -->
        <h6 class="fw-bold">📌 File Format</h6>
        <ul>
            <li>Upload only <strong>Excel (.xlsx / .csv)</strong> files</li>
            <li>First row must contain <strong>column headers</strong></li>
            <li>Do not change column names or order</li>
        </ul>

        <!-- Required Columns -->
        <h6 class="fw-bold mt-3">📋 Required Columns</h6>
        <ul>
            <li>Asset Name</li>
            <li>Asset Code (must be unique)</li>
            <li>Serial No (must be unique)</li>
            <li>Asset Structure (Single / Group)</li>
            <li>Parent Category</li>
            <li>Category</li>
            <li>Asset Type</li>
            <li>Operational Status</li>
            <li>Condition</li>
            <li>Purchase Date (Format: <strong>25-Dec-2024</strong>)</li>
            <li>Purchase Cost</li>
            <li>Supplier</li>
            <li>Brand</li>
            <li>Depreciation Method</li>
        </ul>

        <!-- Accepted Values -->
        <h6 class="fw-bold mt-3">⚙️ Accepted Values</h6>

        <p class="mb-1"><strong>Asset Structure:</strong></p>
        <ul>
            <li>Single Asset</li>
            <li>Group Asset</li>
        </ul>

        <p class="mb-1"><strong>Asset Code Format:</strong></p>
        <ul>
            <li>Use alphanumeric codes (e.g., AC001234, ASSET001, EQ2024001)</li>
            <li>Must be unique across all assets</li>
            <li>Recommended: Use a consistent prefix followed by numbers</li>
        </ul>

        <p class="mb-1"><strong>Operational Status:</strong></p>
        <ul>
            <li>Working</li>
            <li>Under Maintenance</li>
            <li>Repair</li>
            <li>Damaged</li>
            <li>Not Working</li>
            <li>Retired</li>
            <li>Disposed</li>
        </ul>

         

        <p class="mb-1"><strong>Condition:</strong></p>
        <ul>
            <li>new (for New items)</li>
            <li>good (for Good condition)</li>
            <li>fair (for Fair condition)</li>
            <li>poor (for Poor condition)</li>
            <li>disposed (for Disposed condition)</li>
        </ul>

        <p class="mb-1"><strong>Common Asset Types:</strong></p>
        <ul>
            <li><strong>Computers & IT:</strong> Laptop, Desktop, Printer, Router, Switch, Monitor, Server</li>
            <li><strong>CCTV & Security:</strong> IP Cameras, Network Video Recorder, UPS, Counter TV, HD Cameras, 4G Cameras</li>
            <li><strong>Equipment:</strong> Projector, Biometric Device, Hard Disk, POE Switches</li>
            <li><strong>Vehicles:</strong> Scooter</li>
            <li><strong>Furniture:</strong> Sofa</li>
        </ul>

 

        <p class="mb-1"><strong>Depreciation Method:</strong></p>
        <ul>
            <li>No Depreciation</li>
            <li>Reducing Balance</li>
        </ul>

        <div class="alert alert-warning mt-2">
            If <strong>Reducing Balance</strong> is selected, Depreciation Rate (1–99) is required.
        </div>

        <!-- Data Mapping -->
        <h6 class="fw-bold mt-3">🔗 Data Mapping Rules</h6>
        <ul>
            <li>Parent Category, Category, Asset Type must already exist in system</li>
            <li>Category must belong to selected Parent Category</li>
            <li>Asset Type must match Parent Category &amp; Category</li>
            <li>Supplier and Brand must exist in system</li>
        </ul>

        <!-- Validations -->
        <h6 class="fw-bold mt-3">⚠️ Important Validations</h6>
        <ul>
            <li>Serial Number must be <strong>unique</strong></li>
            <li>Purchase Cost must be <strong>numeric and greater than 0</strong></li>
            <li>Items Count required for <strong>Group Asset</strong> only</li>
            <li>Date must be in valid format</li>
        </ul>

       

    </div>
</div>

     </div>
</div>
@endsection
