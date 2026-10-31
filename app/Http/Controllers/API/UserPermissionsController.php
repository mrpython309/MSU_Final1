<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Spatie\Permission\Models\Permission;

class UserPermissionsController extends Controller
{
    use ApiResponse;

    /**
     * Map of isolated mobile module permissions to the client-side permission keys expected by the Flutter app.
     */
    private const MOBILE_MAP = [
        'mobile.dashboard:view' => 'dashboard:view',
        'mobile.asset:view' => 'asset:view',
        'mobile.audit:view' => 'audit:view',
        'mobile.support-ticket:view' => 'support-ticket:view',
        'mobile.support-ticket:create' => 'support-ticket:create',
    ];

    public function userPermissions()
    {
        if (!auth()->check()) {
            return $this->error('You are not logged in');
        }

        $user = auth()->user();
        if (!$user) {
            return $this->success([]);
        }

        // Super Admin / Admin role bypass - full access
        if ($user->hasAnyRole(['superadmin', 'Super Admin', 'Admin', 'admin']) || $user->id === 1) {
            $allPerms = Permission::pluck('name')->unique()->values()->toArray();
            $allPerms = array_values(array_unique(array_merge($allPerms, array_values(self::MOBILE_MAP))));
            return $this->success($allPerms);
        }

        $user->load('permissions', 'roles.permissions');
        $userPerms = $user->getAllPermissions()->pluck('name')->unique()->values()->toArray();

        // Target client keys that are strictly managed by mobile permissions
        $mobileTargetKeys = array_values(self::MOBILE_MAP);

        // Filter out any web-based instances of the mobile keys so web permissions don't bleed into mobile
        $finalPerms = array_values(array_diff($userPerms, $mobileTargetKeys));

        // Grant each mobile key IF AND ONLY IF the user has the corresponding isolated mobile.* permission
        foreach (self::MOBILE_MAP as $mobileKey => $clientKey) {
            if (in_array($mobileKey, $userPerms, true)) {
                $finalPerms[] = $clientKey;
                $finalPerms[] = $mobileKey;
            }
        }

        return $this->success(array_values(array_unique($finalPerms)));
    }
}
