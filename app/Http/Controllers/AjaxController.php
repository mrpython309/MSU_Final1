<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\AssetCategory;
use App\Models\ProductSubCategory;
use App\AssetStatus;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Log;

class AjaxController extends Controller
{

    public function getBranchesWithoutScope($companyId){

        return response()->json([
                    'data' => Branch::where('company_id', $companyId)
                        ->select('id', 'name')
                        ->orderBy('name')
                        ->get()
                ]);
    }
    public function getBranches($companyId)
    {
        return response()->json([
            'data' => Branch::visibleToUser(auth()->user())->where('company_id', $companyId)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        ]);
    }

    public function getLocations($branchId)
    {

       $locations = BranchLocation::where('branch_id', $branchId)
        ->select('id', 'location_name')
        ->orderBy('location_name')
        ->get();

    return response()->json([
        'status' => true,
        'count'  => $locations->count(),
        'data'   => $locations
    ]);
    }

    public function getAssets($branchId){

        $data = Asset::where('branch_id', $branchId)
            ->notDisposed()
            ->select('id', 'asset_name', 'serial_no', 'asset_code', 'company_id', 'branch_id', 'department_id')
            ->orderBy('asset_name')
            ->get()
            ->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'asset_name' => $asset->asset_name,
                    'serial_no' => $asset->serial_no,
                    'asset_code' => $asset->asset_code,
                    'company_id' => $asset->company_id,
                    'branch_id' => $asset->branch_id,
                    'department_id' => $asset->department_id,
                ];
            });

        return response()->json([
            'data' => $data,
        ]);

    }

    public function getSubCategories($category)
    {
        return response()->json([
            'data' => AssetCategory::where('parent_id', $category)
                ->select('id', 'category_name')
                ->orderBy('category_name')
                ->get()
        ]);
    }

    public function getProductSubCategories($category)
    {
        return response()->json([
            'data' => ProductSubCategory::query()
                ->where('product_category_id', $category)
                ->where('status', 'active')
                ->select('id', 'sub_category_name')
                ->orderBy('sub_category_name')
                ->get(),
        ]);
    }

    public function getAssetTypes($category)
    {
        return response()->json([
            'data' => AssetType::where('sub_category_id', $category)
                ->select('id', 'asset_type_name')
                ->orderBy('asset_type_name')
                ->get()
        ]);
    }


    public function getBrancheswithoutScopeByCompanies(Request $request){
        
        $branches = Branch::whereIn('company_id', $request->company_ids ?? [])
        ->where('status', 'active')
        ->with('company:id,name')
        ->orderBy('name')
        ->get()
        ->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'company_id' => $branch->company_id,
                'company_name' => $branch->company->name ?? ''
            ];
        });

         return response()->json([
        'success' => true,
        'data' => $branches
        ]);
    }

    public function getBranchesByCompanies(Request $request){

         $branches = Branch::visibleToUser(auth()->user())->whereIn('company_id', $request->company_ids)
        ->with('company:id,name')
        ->get()
        ->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'company_id' => $branch->company_id,
                'company_name' => $branch->company->name
            ];
        });


         return response()->json([
        'success' => true,
        'data' => $branches
        ]);

    }

    public function getUsers(Request $request, $branchId){
        if ((int) $request->query('assignees', 0) === 1) {
            $employees = User::query()
                ->activeEmployeesForBranch((int) $branchId)
                ->get(['id', 'name']);

            return response()->json([
                'data' => $employees->map(static fn (User $employee) => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                ]),
            ]);
        }

        $forFilter = (int) $request->query('for_filter', 0) === 1;
        $viewerId = (int) (auth()->id() ?? 0);

         $users = User::query()
            ->where('employee_status', 'active')
            ->when(! $forFilter && $viewerId > 0, static fn ($q) => $q->where('id', '!=', $viewerId))
            ->when($forFilter, static fn ($q) => $q->visibleToUser(auth()->user()))
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            });

        $departmentId = (int) $request->query('department_id', 0);
        $divisionId = (int) $request->query('division_id', 0);
        $strictDepartment = (int) $request->query('strict_department', 0) === 1;
        if ($strictDepartment && $departmentId <= 0) {
            return response()->json([
                'data' => [],
            ]);
        }
                    if ($departmentId > 0) {
            $users->whereHas('departments', function ($q) use ($departmentId) {
                $q->where('departments.id', $departmentId);
            });
        }

        if ($divisionId > 0) {
            $users->where('division_id', $divisionId);
        }

        $users = $users->get();

        return response()->json([
            'data' => $users
        ]);

    }
    /**
     * Departments that have at least one active user linked to the branch.
     */
    public function getDepartmentsByBranch($branchId)
    {
        $query = Department::query()
            ->active()
            ->whereHas('users', function ($q) use ($branchId) {
                $q->where('employee_status', 'active')
                    ->employeesInBranch((int) $branchId);
            });

        $user = auth()->user();
        if ($user && ! $user->hasRole('superadmin')) {
            $query->visibleToUser($user);
        }

        $departments = $query
            ->orderBy('department_name')
            ->get(['id', 'department_name']);

        return response()->json([
            'data' => $departments,
        ]);
    }

    /**
     * All rows from Settings → Employees (same scope as employees index: users except system user).
     */
    public function getSettingsEmployees()
    {
        $users = User::query()
            ->where('id', '!=', 1)
            ->orderBy('name')
            ->with(['departments:id,department_name'])
            ->get(['id', 'name', 'emp_code']);

        return response()->json([
            'data' => $users,
        ]);
    }

}
