<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\API\LoginController;
use App\Http\Controllers\API\DepartmentController;
use App\Http\Controllers\API\ProductCategoryController;
use App\Http\Controllers\API\ProductSubCategoryController;
use App\Http\Controllers\API\MsuItemMasterController;
use App\Http\Controllers\API\UnitController as ApiUnitController;
use App\Http\Controllers\API\MsuPurchaseOrderController as ApiMsuPurchaseOrderController;
use App\Http\Controllers\API\MsuWorkOrderController as ApiMsuWorkOrderController;
use App\Http\Controllers\API\MsuWorkInvoiceController as ApiMsuWorkInvoiceController;
use App\Http\Controllers\API\DesignationController;
use App\Http\Controllers\API\AssetTypeController;
use App\Http\Controllers\API\BrandsController;
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\BranchController;
use App\Http\Controllers\API\VendorController;
use App\Http\Controllers\API\AssetController;
use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\AssetWarrantiesController;
use App\Http\Controllers\API\AssetAssignmentsController;
use App\Http\Controllers\API\AssetDocumentsController;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\API\AssetMaintenanceController;
use App\Http\Controllers\API\UserPermissionsController;
use App\Http\Controllers\API\AssetDashboardController;
use App\Http\Controllers\API\DeviceTokenController;
use App\Http\Controllers\API\DependencyController;
use App\Http\Controllers\API\AssetCategoiresController;
use App\Http\Controllers\API\AssetNamesController;
use App\Http\Controllers\API\SupportTicketsController;
use App\Http\Controllers\API\SupportTicketActionController;
use App\Http\Controllers\API\SupportTicketCommandsController;
use App\Http\Controllers\API\TicketDashboardController;


Route::prefix('v1')->group(function () {
    Route::get('divisions', function () {
        return response()->json([
            'success' => true,
            'data' => \App\Models\Division::active()->orderBy('division_name')->get()
        ]);
    });
    Route::get('support-tickets/preview-ticket-number', [\App\Http\Controllers\SupportTicket\SupportTicketController::class, 'previewTicketNumber']);


    Route::post('/login', [LoginController::class, 'login']);

    Route::post('devicetoken/update', [DeviceTokenController::class, 'updateDeviceToken'])->name('api.devicetoken.update');

     Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function(){

        Route::get('getbranch/bycompany/{id}',[DependencyController::class,'getBranches'])->name('api.getbranch.bycompany');

        Route::get('getlocation/bybranch/{id}',[DependencyController::class,'getLocations'])->name('api.getlocation.bybranch');

        Route::get('getasset/bybranch/{id}',[DependencyController::class,'getAssets'])->name('api.getasset.bybranch');

        Route::get('getassetcategories',[DependencyController::class,'getAssetCategories'])->name('api.getassetcategories');

        Route::get('getsubcategory/bycategory/{id}',[DependencyController::class,'getAssetSubCategories'])->name('api.getsubcategories.bycategory');

        Route::get('getusers/bybranch/{id}',[DependencyController::class,'getUsers'])->name('api.getusers.bybranch');

        Route::get('getcompanies',[DependencyController::class,'getCompanies'])->name('api.getcompanies');

        Route::get('assigneddepartments',[DependencyController::class,'assignedDepartments'])->name('api.assigneddepartments');

        Route::resource('departments',DepartmentController::class)->names('api.departments');

        Route::resource('product-categories', ProductCategoryController::class)->names('api.product-categories');

        Route::resource('product-sub-categories', ProductSubCategoryController::class)->names('api.product-sub-categories');

        Route::resource('item-masters', MsuItemMasterController::class)->names('api.item-masters');

        Route::resource('units', ApiUnitController::class)->names('api.units');

        Route::resource('msu-purchase-orders', ApiMsuPurchaseOrderController::class)->names('api.msu-purchase-orders');

        Route::resource('msu-work-orders', ApiMsuWorkOrderController::class)->names('api.msu-work-orders');

        Route::resource('msu-work-invoices', ApiMsuWorkInvoiceController::class)->names('api.msu-work-invoices');

        Route::resource('designations',DesignationController::class)->names('api.designations');

        Route::resource('asset-categories',AssetCategoiresController::class)->names('api.asset-categories');

        Route::resource('asset-names',AssetNamesController::class)->names('api.asset-names');

        Route::resource('asset-types',AssetTypeController::class)->names('api.asset-types');

        Route::resource('brands',BrandsController::class)->names('api.brands');

        Route::resource('locations',LocationController::class)->names('api.locations');

        Route::resource('branches',BranchController::class)->names('api.branches');

        Route::resource('vendors',VendorController::class)->names('api.vendors');

        Route::get('assets/operating-status', [AssetController::class, 'operatingStatus'])->name('api.assets.operating-status');

        Route::get('assets/status', [AssetController::class, 'status'])->name('api.assets.status');

        Route::get('assets/condition', [AssetController::class, 'condition'])->name('api.assets.condition');

        Route::get('assets/warranties/warranty-types', [AssetWarrantiesController::class, 'warrantyTypes'])->name('api.assets.warranties.warranty-types');

        Route::get('assets/maintenance/reportedby',[AssetMaintenanceController::class,'assetMaintenceReportedBy'])->name('api.assets.maintenance.reportedby');

        Route::resource('assets/maintenance',AssetMaintenanceController::class)->names('api.assets.maintenance');

        Route::resource('assets/warranties',AssetWarrantiesController::class)->names('api.assets.warranties');

        Route::put('assets/assignments/accept/{id}', [AssetAssignmentsController::class, 'acceptAssignment'])->name('api.assets.assignments.accept');

        Route::put('assets/assignments/reject/{id}', [AssetAssignmentsController::class, 'rejectAssignment'])->name('api.assets.assignments.reject');

        Route::put('assets/assignments/return/{id}', [AssetAssignmentsController::class, 'returnAssignment'])->name('api.assets.assignments.return');

        Route::put('assets/assignments/returnaccept/{id}', [AssetAssignmentsController::class, 'returnAcceptAssignment'])->name('api.assets.assignments.returnaccept');


        Route::resource('assets/assignments',AssetAssignmentsController::class)->names('api.assets.assignments');

        Route::resource('assets/documents',AssetDocumentsController::class)->names('api.assets.documents');

        Route::get('assets/nextassetcode',[AssetController::class,'getnextassetcode'])->name('api.assets.getassetcode');

        Route::post('assets/{id}/audit', [AssetController::class, 'audit'])->name('api.assets.audit');
        Route::get('assets/{id}/audits', [AssetController::class, 'auditHistory'])->name('api.assets.audits');

        Route::get('audit/stats', [\App\Http\Controllers\API\AuditApiController::class, 'getStats'])->name('api.audit.stats');
        Route::get('audit/list', [\App\Http\Controllers\API\AuditApiController::class, 'list'])->name('api.audit.list');
        Route::get('audit/asset/{id}', [\App\Http\Controllers\API\AuditApiController::class, 'getAssetDetails'])->name('api.audit.asset');
        Route::get('audit/lookup', [\App\Http\Controllers\API\AuditApiController::class, 'lookup'])->name('api.audit.lookup');
        Route::post('audit/submit', [\App\Http\Controllers\API\AuditApiController::class, 'submit'])->name('api.audit.submit');
        Route::get('audit/history/{id}', [\App\Http\Controllers\API\AuditApiController::class, 'getHistory'])->name('api.audit.history');

        Route::resource('assets',AssetController::class)->names('api.assets');

        Route::resource('companies',CompanyController::class)->names('api.companies');

        Route::get('user-permissions', [UserPermissionsController::class, 'userPermissions'])->name('api.user-permissions');

        Route::post('/logout', [LoginController::class, 'logout']);

        Route::get('dashboard/assets', [AssetDashboardController::class, 'assets'])->name('api.dashboard.assets');

        Route::get('dashboard/items', [\App\Http\Controllers\API\ItemDashboardController::class, 'index'])->name('api.dashboard.items');

        Route::resource('item-stocks', \App\Http\Controllers\API\ItemStockController::class)->only(['index', 'show'])->names('api.item-stocks');

        Route::resource('stock-adjustments', \App\Http\Controllers\API\MsuStockAdjustmentController::class)->only(['index', 'store', 'show'])->names('api.stock-adjustments');

        Route::get('support-tickets/assetcode/{id}', [SupportTicketActionController::class, 'getAssetCode'])->name('api.support-tickets.assetcode');
        Route::post('support-tickets/accept/{id}', [SupportTicketActionController::class, 'acceptTicket'])->name('api.support-tickets.accept');
        Route::post('support-tickets/close/{id}', [SupportTicketActionController::class, 'closeTicket'])->name('api.support-tickets.close');
        Route::post('support-tickets/reopen/{id}', [SupportTicketActionController::class, 'reopenTicket'])->name('api.support-tickets.reopen');
        Route::post('support-tickets/complete/{id}', [SupportTicketActionController::class, 'completeTicket'])->name('api.support-tickets.complete');
        Route::post('support-tickets/exit/{id}', [SupportTicketActionController::class, 'exitTicket'])->name('api.support-tickets.exit');
        Route::post('support-tickets/assign/{id}', [SupportTicketActionController::class, 'assignTicket'])->name('api.support-tickets.assign');
        Route::post('support-tickets/update-status/{id}', [SupportTicketActionController::class, 'updateStatus'])->name('api.support-tickets.updatestatus');
        Route::get('support-tickets/assignedlist/{id}',[SupportTicketActionController::class,'getAssignedPersons'])->name('api.support-tickets.assignedpersons');
        Route::get('support-tickets/activitylogs/{id}',[SupportTicketActionController::class,'getActivityLogs'])->name('api.support-tickets.activitylogs');
        Route::get('support-tickets/assignableusers/{id}',[SupportTicketActionController::class,'getAssignableUsers'])->name('api.support-tickets.assignableusers');
        Route::get('support-tickets/status', [SupportTicketsController::class, 'status'])->name('api.support-tickets.status');
        Route::get('support-tickets/priority', [SupportTicketsController::class, 'priority'])->name('api.support-tickets.priority');
        Route::resource('support-tickets/commands',SupportTicketCommandsController::class)->names('api.support-tickets.commands');
        Route::resource('support-tickets',SupportTicketsController::class)->names('api.support-tickets');

     });


});

Route::post('/login', [AuthController::class, 'login']);

// Authentication Routes (No Token Required)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
});

// User CRUD Routes (No Token Required)
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/statistics', [UserController::class, 'statistics']);
    Route::get('/company/{companyId}', [UserController::class, 'getUsersByCompany']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
    Route::patch('/{id}/activate', [UserController::class, 'activate']);
    Route::patch('/{id}/deactivate', [UserController::class, 'deactivate']);
});
    