<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\BranchLocation;
use App\Traits\ApiResponse;
use App\Models\AssetCategory;
use App\Models\User;
use App\Models\Company;
use App\Models\Department;

class DependencyController extends Controller
{
    use ApiResponse;
   public function getBranches($id){

    $brnches=Branch::visibleToUser(auth()->user())->where('company_id', $id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

    return $this->success($brnches);

   }

   public function getLocations($id){

     $locations = BranchLocation::where('branch_id', $id)
        ->select('id', 'location_name')
        ->orderBy('location_name')
        ->get();

    return $this->success($locations);

   }

   public function getAssets($id){

     $assets = Asset::where('branch_id', $id)
        ->notDisposed()
        ->select('id', 'asset_name')
        ->orderBy('asset_name')
        ->get();

    return $this->success($assets);

   }

   public function getAssetCategories(){

      $categories=AssetCategory::active()->WhereNULL('parent_id')->get();

      return $this->success($categories);

   }

   public function getAssetSubCategories($id){

      $subCategory = AssetCategory::where('parent_id', $id)
        ->Where('status', 'active')
        ->orderBy('category_name')
        ->get();

     return $this->success($subCategory);

   }


   public function getUsers($id){

     $users = User::query()
            ->activeEmployeesForBranch((int) $id)
            ->get();

    return $this->success($users);   

   }


   public function getCompanies(){

     $companies=Company::visibleToUser(auth()->user())->active()->get();

    return $this->success($companies);
   }

   public function assignedDepartments(){

     $departments=Department::VisibleToUser(auth()->user())->active()->get();

    return $this->success($departments);

   }
}
