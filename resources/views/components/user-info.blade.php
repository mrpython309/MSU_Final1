@php
    $user?->loadMissing('designation');
@endphp
<div class="d-flex align-items-center mb-3">
  <div class="avatar me-4">
    <i class="bx bx-user fs-3"></i>
  </div>
  <div>
    <h6 class="mb-1">{{ $user->name ?? '-' }}</h6>
    <small>{{ $user->designation->designation_name ?? '-' }}</small>
  </div>
</div>
