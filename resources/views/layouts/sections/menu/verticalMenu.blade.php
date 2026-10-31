@php
  use Illuminate\Support\Facades\Route;

  $menuUser = auth()->user();

  $menuIsSuperAdmin = false;
  try {
      $menuIsSuperAdmin = (bool) $menuUser?->hasAnyRole(['superadmin', 'Super Admin']);
  } catch (\Throwable) {
      $menuIsSuperAdmin = false;
  }

  $menuCan = function (?string $permissionExpression) use ($menuUser, $menuIsSuperAdmin): bool {
      if ($menuIsSuperAdmin) {
          return true;
      }

      if (! $menuUser || $permissionExpression === null || trim($permissionExpression) === '') {
          return false;
      }

      $parts = array_values(array_filter(array_map('trim', explode('|', $permissionExpression))));

      return $parts !== [] && $menuUser->canAny($parts);
  };

  $hasAnyModulePermission = function ($slug) use ($menuUser, $menuIsSuperAdmin): bool {
      if ($menuIsSuperAdmin) return true;
      if (! $menuUser || ! $slug || is_array($slug)) return false;
      
      // Remove common resource route suffixes
      $moduleName = preg_replace('/\.(index|create|edit|show|store|update|destroy)$/', '', $slug);
      $moduleNameUnderscore = str_replace('-', '_', $moduleName);
      
      // Handle plural/singular mismatches (e.g. msu-work-orders vs msu-work-order:view)
      $moduleNameSingular = rtrim($moduleName, 's');
      $moduleNameUnderscoreSingular = rtrim($moduleNameUnderscore, 's');

      $userPermissions = $menuUser->getAllPermissions()->pluck('name');
      foreach ($userPermissions as $p) {
          $pStr = (string) $p;
          // Require exact namespace match using colon to prevent prefix bleed (e.g. "dashboard" matching "dashboard.asset")
          if (str_starts_with($pStr, $moduleName . ':') || 
              str_starts_with($pStr, $moduleNameUnderscore . ':') ||
              str_starts_with($pStr, $moduleNameSingular . ':') ||
              str_starts_with($pStr, $moduleNameUnderscoreSingular . ':')) {
              return true;
          }
      }
      return false;
  };

  $menuChildVisible = function ($child) use ($menuUser, $menuCan, $menuIsSuperAdmin, $hasAnyModulePermission): bool {
      if (! $menuUser) {
          return false;
      }

      if ($menuIsSuperAdmin) {
          return true;
      }

      if (isset($child->role) && ! empty($child->role)) {
          $roles = array_filter(array_map('trim', explode('|', (string) $child->role)));

          return $roles !== [] && $menuUser->hasAnyRole($roles);
      }

      if (isset($child->permission) && ! empty($child->permission)) {
          return $menuCan((string) $child->permission);
      }

      if (isset($child->slug) && ! empty($child->slug) && ! is_array($child->slug)) {
          return $hasAnyModulePermission((string) $child->slug);
      }

      // No permission/slug configured — hide by default so only assigned modules show.
      return false;
  };
@endphp
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

  <!-- ! Hide app brand if navbar-full -->
  <div class="app-brand demo">
    <a href="{{ url('/') }}" class="app-brand-link">
      <span class="app-brand-logo demo">@include('_partials.macros')</span>
      <!-- <span class="app-brand-text demo menu-text fw-bold ms-2">{{ config('variables.templateName') }}</span> -->
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
      <i class="icon-base bx bx-chevron-left icon-sm d-flex align-items-center justify-content-center"></i>
    </a>
  </div>

  <div class="menu-divider mt-0"></div>
  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    @foreach ($menuData[0]->menu as $menu)
      {{-- menu headers --}}
      @if (isset($menu->menuHeader))
        <li class="menu-header small text-uppercase">
          <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
        </li>
      @else
        @php
          if (! empty($menu->hidden)) {
              continue;
          }

          $showMenu = false;

          if ($menuIsSuperAdmin) {
              $showMenu = true;
          } elseif (isset($menu->permission) && ! empty($menu->permission)) {
              $showMenu = $menuCan((string) $menu->permission);
          } elseif (isset($menu->slug) && ! empty($menu->slug) && ! is_array($menu->slug)) {
              $showMenu = $hasAnyModulePermission((string) $menu->slug);
          }

          $hasVisibleChild = false;
          if (isset($menu->submenu) && is_array($menu->submenu)) {
              foreach ($menu->submenu as $child) {
                  if ($menuChildVisible($child)) {
                      $hasVisibleChild = true;
                      break;
                  }
              }
          }

          // Parent with only slug-array (no own permission): show when any child is allowed.
          if (! $showMenu && $hasVisibleChild) {
              $showMenu = true;
          }

          // Top-level item with neither permission nor visible children: hide.
          if (! $showMenu && ! $hasVisibleChild) {
              continue;
          }
        @endphp

        {{-- active menu method --}}
        @php
          $activeClass = '';
          $currentRouteName = Route::currentRouteName();
          $slugs = isset($menu->slug)
              ? (is_array($menu->slug) ? $menu->slug : [$menu->slug])
              : [];

          foreach ($slugs as $slug) {
              $slugRoute = str_replace('/', '.', $slug);

              if (
                  $currentRouteName === $slugRoute ||
                  str_starts_with((string) $currentRouteName, $slugRoute.'.')
              ) {
                  $activeClass = isset($menu->submenu) ? 'active open' : 'active';
                  break;
              }
          }

          if (empty($activeClass) && isset($menu->submenu)) {
              foreach ($menu->submenu as $child) {
                  if (isset($child->slug)) {
                      $childSlug = str_replace('/', '.', $child->slug);

                      if (
                          $currentRouteName === $childSlug ||
                          str_starts_with((string) $currentRouteName, $childSlug.'.')
                      ) {
                          $activeClass = 'active open';
                          break;
                      }
                  }
              }
          }
        @endphp

        <li class="menu-item {{ $activeClass }}">
          <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
            class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
            @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
            @isset($menu->icon)
              <i class="{{ $menu->icon }}"></i>
            @endisset
            <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
            @isset($menu->badge)
              <div class="badge rounded-pill bg-{{ $menu->badge[0] }} text-uppercase ms-auto">{{ $menu->badge[1] }}</div>
            @endisset
          </a>

          @isset($menu->submenu)
            @include('layouts.sections.menu.submenu', ['menu' => $menu->submenu])
          @endisset
        </li>
      @endif
    @endforeach
  </ul>

</aside>
