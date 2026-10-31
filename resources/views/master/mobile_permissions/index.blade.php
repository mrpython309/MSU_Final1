@extends('layouts/contentNavbarLayout')

@section('title', 'Mobile App Module Permissions')

@section('content')
<div class="row">
  <div class="col-12">
    {{-- Header Banner --}}
    <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: #fff;">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h4 class="fw-bold mb-1 text-white">
              <i class="bx bx-mobile-alt me-2"></i>Mobile App Module Permissions
            </h4>
            <p class="mb-0 text-white-50">
              Configure real-time module access for the Mobile Application (Dashboard, Assets, Asset Audit, Support Tickets).
            </p>
          </div>
          <div class="d-flex gap-2">
            <span class="badge bg-white text-primary px-3 py-2 fw-semibold">
              <i class="bx bx-check-shield me-1"></i> Enterprise RBAC
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    {{-- Module Legend Cards --}}
    <div class="row g-3 mb-4">
      @foreach($mobilePermissions as $key => $info)
        <div class="col-md-2 col-sm-4 col-6">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3 text-center">
              <div class="badge bg-label-{{ $info['color'] }} p-2 rounded-circle mb-2">
                <i class="{{ $info['icon'] }} fs-4"></i>
              </div>
              <h6 class="fw-bold mb-1 text-truncate">{{ $info['name'] }}</h6>
              <code class="small text-muted" style="font-size: 0.72rem;">{{ $key }}</code>
            </div>
          </div>
        </div>
      @endforeach
      <div class="col-md-2 col-sm-4 col-6">
        <div class="card h-100 border-0 shadow-sm bg-light">
          <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
            <span class="badge bg-label-secondary mb-1">Super Admin Note</span>
            <small class="text-muted" style="font-size: 0.72rem;">Super Admins automatically have full access to all 4 modules.</small>
          </div>
        </div>
      </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="nav-align-top mb-4">
      <ul class="nav nav-pills mb-3 gap-2" role="tablist">
        <li class="nav-item">
          <button type="button" class="nav-link active fw-semibold" role="tab" data-bs-toggle="tab" data-bs-target="#tab-roles">
            <i class="bx bx-shield me-1"></i> Role Permissions Matrix ({{ $roles->count() }} Roles)
          </button>
        </li>
      </ul>

      <div class="tab-content border-0 p-0">
        {{-- ==================== TAB 1: ROLES MATRIX ==================== --}}
        <div class="tab-pane fade show active" id="tab-roles" role="tabpanel">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
              <h5 class="mb-0 fw-bold">Role-Based Mobile Module Permissions</h5>
              <small class="text-muted">Changes apply immediately to all users assigned to that role.</small>
            </div>
            <div class="table-responsive text-nowrap">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="min-width: 200px;">Role</th>
                    @foreach($mobilePermissions as $key => $info)
                      <th class="text-center" style="min-width: 130px;">
                        <i class="{{ $info['icon'] }} text-{{ $info['color'] }} me-1"></i>
                        {{ $info['name'] }}
                      </th>
                    @endforeach
                    <th class="text-end" style="min-width: 140px;">Action</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($roles as $role)
                    @php
                      $rolePerms = $role->permissions->pluck('name')->toArray();
                      $isSuperAdmin = in_array(strtolower($role->name), ['superadmin', 'super admin', 'admin'], true);
                    @endphp
                    <tr>
                      <td>
                        <form method="POST" action="{{ route('mobile-permissions.update-role') }}" id="form-role-{{ $role->id }}">
                          @csrf
                          <input type="hidden" name="role_id" value="{{ $role->id }}">
                          <div>
                            <span class="fw-bold fs-6 text-dark">{{ $role->name }}</span>
                            @if($isSuperAdmin)
                              <span class="badge bg-label-danger ms-1">Full Bypass</span>
                            @endif
                          </div>
                          @if($role->description)
                            <small class="text-muted d-block text-truncate" style="max-width: 250px;">{{ $role->description }}</small>
                          @endif
                      </td>

                      @foreach($mobilePermissions as $key => $info)
                        @php
                          $hasPerm = in_array($key, $rolePerms, true) || $isSuperAdmin;
                        @endphp
                        <td class="text-center">
                          <div class="form-check form-switch d-inline-block">
                            <input
                              type="checkbox"
                              class="form-check-input role-perm-toggle"
                              name="permissions[]"
                              value="{{ $key }}"
                              form="form-role-{{ $role->id }}"
                              id="perm-{{ $role->id }}-{{ md5($key) }}"
                              {{ $hasPerm ? 'checked' : '' }}
                              {{ $isSuperAdmin ? 'disabled' : '' }}
                            >
                          </div>
                        </td>
                      @endforeach

                      <td class="text-end">
                        @if($isSuperAdmin)
                          <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Super Admin</button>
                        @else
                          <button type="submit" form="form-role-{{ $role->id }}" class="btn btn-sm btn-primary">
                            <i class="bx bx-save me-1"></i> Save
                          </button>
                        @endif
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
