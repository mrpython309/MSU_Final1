<?php
use App\Http\Controllers\MobilePermissionController;
use App\Http\Controllers\AjaxController;
use App\Http\Controllers\authentications\ForgotPasswordBasic;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\authentications\RegisterBasic;
use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Dashboard\Analytics;
use App\Http\Controllers\DealerLocation\DealerLocationController;
use App\Http\Controllers\Department\DepartmentController;
use App\Http\Controllers\Division\DivisionController;
use App\Http\Controllers\ProductCategory\ProductCategoryController;
use App\Http\Controllers\ProductCategory\ProductSubCategoryController;
use App\Http\Controllers\Unit\UnitController;
use App\Http\Controllers\PaymentTerm\PaymentTermController;
use App\Http\Controllers\MiscellaneousCharge\MiscellaneousChargeController;
use App\Http\Controllers\Hsn\HsnController;
use App\Http\Controllers\EmployeeMaster\EmployeeMasterController;
use App\Http\Controllers\AssetCustodian\AssetCustodianController;
use App\Http\Controllers\ShippingDetail\ShippingDetailController;


use App\Http\Controllers\ItemMasterController;
use App\Http\Controllers\ItemMaster\MsuItemMasterController;
use App\Http\Controllers\MaterialRequestController;
use App\Http\Controllers\MaterialIssueController;
use App\Http\Controllers\Designation\DesignationController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\layouts\Blank;
use App\Http\Controllers\layouts\Container;
use App\Http\Controllers\layouts\Fluid;
use App\Http\Controllers\layouts\WithoutMenu;
use App\Http\Controllers\layouts\WithoutNavbar;
use App\Http\Controllers\pages\AccountSettingsAccount;
use App\Http\Controllers\pages\AccountSettingsConnections;
use App\Http\Controllers\pages\AccountSettingsNotifications;
use App\Http\Controllers\pages\MiscError;
use App\Http\Controllers\pages\MiscUnderMaintenance;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Segment\SegmentController;
use App\Http\Controllers\Segment\SubSegmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\Brands\BrandController;
use App\Http\Controllers\CostCentres\CostCentreController;
use App\Http\Controllers\AssetTypes\AssetTypeController;
use App\Http\Controllers\Location\BranchLocationController;
use App\Http\Controllers\Branch\BranchController;
use App\Http\Controllers\Assets\AssetController;
use App\Http\Controllers\Vendor\VendorController;
use App\Http\Controllers\Assets\AssetAssignmentController;
use App\Http\Controllers\Assets\AssetWarrantyController;
use App\Http\Controllers\Employee\EmployeesController;
use App\Http\Controllers\Employee\EmployeeStaffController;
use App\Http\Controllers\Assets\AssetCategoryController;
use App\Http\Controllers\Assets\AssetNameController;
use App\Http\Controllers\Assets\AssetDocumentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Assets\AssetMaintenanceController;
use App\Http\Controllers\Assets\AssetReplacementController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\Dashboard\AssetDashboardController;
use App\Http\Controllers\ApprovalTemplateController;
use App\Http\Controllers\IndentController;
use App\Http\Controllers\GrnController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProcurementDashboardController;
use App\Http\Controllers\ProcurementReportController;
use App\Http\Controllers\PurchaseOfficerDashboardController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\MsuPurchaseOrder\MsuPurchaseOrderController;
use App\Http\Controllers\MsuGrn\MsuGrnController;
use App\Http\Controllers\MsuWorkOrderController;
use App\Http\Controllers\MsuWorkInvoiceController;
use App\Http\Controllers\PurchaseRequisitionController;
use App\Http\Controllers\RfqController;
use App\Http\Controllers\VendorQuotationController;
use App\Http\Controllers\PaymentRequestController;
use App\Http\Controllers\QualityInspectionController;
use App\Http\Controllers\SupportTicket\SupportTicketController;
use App\Http\Controllers\SupportTicket\SupportTicketProgressStatusController;
use App\Http\Controllers\Dashboard\SupportTicketDashboardController;
use Illuminate\Support\Facades\Route;

// layout
Route::get('seed-item-perms', function() { require base_path('seed_item_permissions.php'); return 'done'; });

Route::get('/layouts/without-menu', [WithoutMenu::class, 'index'])->name('layouts-without-menu');
Route::get('/layouts/without-navbar', [WithoutNavbar::class, 'index'])->name('layouts-without-navbar');
Route::get('/layouts/fluid', [Fluid::class, 'index'])->name('layouts-fluid');
Route::get('/layouts/container', [Container::class, 'index'])->name('layouts-container');
Route::get('/layouts/blank', [Blank::class, 'index'])->name('layouts-blank');

// pages
Route::get('/pages/account-settings-account', [AccountSettingsAccount::class, 'index'])->name('pages-account-settings-account');
Route::get('/pages/account-settings-notifications', [AccountSettingsNotifications::class, 'index'])->name('pages-account-settings-notifications');
Route::get('/pages/account-settings-connections', [AccountSettingsConnections::class, 'index'])->name('pages-account-settings-connections');
Route::get('/pages/misc-error', [MiscError::class, 'index'])->name('pages-misc-error');
Route::get('/pages/misc-under-maintenance', [MiscUnderMaintenance::class, 'index'])->name('pages-misc-under-maintenance');

// authentication
Route::get('/login', [LoginBasic::class, 'index'])->name('login');
Route::post('/login', [LoginBasic::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginBasic::class, 'logout'])->name('logout');
Route::get('/auth/register-basic', [RegisterBasic::class, 'index'])->name('auth-register-basic');
Route::get('/auth/forgot-password-basic', [ForgotPasswordBasic::class, 'index'])->name('auth-reset-password-basic');

Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/', [Analytics::class, 'index'])
        ->name('dashboard');
    Route::get('dashboard/export', [Analytics::class, 'export'])
        ->name('dashboard.export')
        ->middleware('permission:dashboard:view');
    Route::get('dashboard/export-pdf', [Analytics::class, 'exportPdf'])
        ->name('dashboard.export-pdf')
        ->middleware('permission:dashboard:view');

    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');





    // ========== COMPANY MASTER ==========

    Route::prefix('companies')->name('companies.')->group(function () {

        Route::get('/', [CompanyController::class, 'index'])->name('index')->middleware('permission:companies:view');
        Route::get('/export', [CompanyController::class, 'export'])->name('export')->middleware('permission:companies:view');
        Route::get('/export-pdf', [CompanyController::class, 'exportPdf'])->name('export-pdf')->middleware('permission:companies:view');
        Route::get('/create', [CompanyController::class, 'create'])->name('create')->middleware('permission:companies:create');
        Route::post('/', [CompanyController::class, 'store'])->name('store')->middleware('permission:companies:create');
        Route::get('/{company}', [CompanyController::class, 'show'])->name('show')->middleware('permission:companies:view');
        Route::get('/{company}/edit', [CompanyController::class, 'edit'])->name('edit')->middleware('permission:companies:edit');
        Route::put('/{company}', [CompanyController::class, 'update'])->name('update')->middleware('permission:companies:edit');
        Route::delete('/{company}', [CompanyController::class, 'destroy'])->name('destroy')->middleware('permission:companies:delete');
    });

    // =============== Roles And Permisson Routes ================
    Route::prefix('roles')->name('roles.')->group(function () {

        Route::get('/', [RoleController::class, 'index'])->name('index')->middleware('permission:roles:view');
        Route::get('create', [RoleController::class, 'create'])->name('create')->middleware('permission:roles:create');
        Route::post('/', [RoleController::class, 'store'])->name('store')->middleware('permission:roles:create');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit')->middleware('permission:roles:edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update')->middleware('permission:roles:edit');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy')->middleware('permission:roles:delete');
    });

    Route::prefix('permissions')->name('permissions.')->group(function () {

        Route::get('/', [PermissionController::class, 'index'])->name('index')->middleware('permission:permissions:view');

        Route::middleware(['permission:permissions:create'])->group(function () {
            Route::get('create', [PermissionController::class, 'create'])->name('create');
            Route::post('/', [PermissionController::class, 'store'])->name('store');
        });

        Route::middleware(['permission:permissions:edit'])->group(function () {
            Route::get('/{permission}/edit', [PermissionController::class, 'edit'])->name('edit');
            Route::put('/{permission}', [PermissionController::class, 'update'])->name('update');
        });

        Route::middleware(['permission:permissions:delete'])->group(function () {
            Route::post('/delete-duplicates', [PermissionController::class, 'deleteDuplicates'])->name('delete-duplicates');
            Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('users')->name('users.')->group(function () {

        Route::get('/', [UserController::class, 'index'])->name('index')->middleware('permission:users:view');
        Route::get('create', [UserController::class, 'create'])->name('create')->middleware('permission:users:create');
        Route::get('{user}', [UserController::class, 'show'])->name('show')->middleware('permission:users:view');
        Route::post('', [UserController::class, 'store'])->name('store')->middleware('permission:users:create');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('permission:users:edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware('permission:users:edit');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('permission:users:delete');
    });

    Route::get('users/{user}/roles', [UserRoleController::class, 'edit'])->name('users.roles.edit')->middleware('permission:users:edit');
    Route::post('users/{user}/roles', [UserRoleController::class, 'update'])->name('users.roles.update')->middleware('permission:users:edit');

    // ========== MOBILE APP MODULE PERMISSIONS ==========
    Route::get('mobile-permissions', [MobilePermissionController::class, 'index'])->name('mobile-permissions.index')->middleware('permission:roles:view');
    Route::post('mobile-permissions/role', [MobilePermissionController::class, 'updateRolePermissions'])->name('mobile-permissions.update-role')->middleware('permission:roles:edit');
    Route::post('mobile-permissions/user', [MobilePermissionController::class, 'updateUserPermissions'])->name('mobile-permissions.update-user')->middleware('permission:users:edit');
    Route::post('mobile-permissions/user-reset', [MobilePermissionController::class, 'resetUserPermissions'])->name('mobile-permissions.reset-user')->middleware('permission:users:edit');

    // ========== DEALER LOCATION ==========
    Route::prefix('dealer-locations')->name('dealer-locations.')->group(function () {

        Route::get('/', [DealerLocationController::class, 'index'])->name('index')->middleware('permission:dealer-locations:view');
        Route::get('create', [DealerLocationController::class, 'create'])->name('create')->middleware('permission:dealer-locations:create');
        Route::post('/', [DealerLocationController::class, 'store'])->name('store')->middleware('permission:dealer-locations:create');
        Route::get('/{dealerLocation}', [DealerLocationController::class, 'show'])->name('show')->middleware('permission:dealer-locations:view');
        Route::get('/{dealerLocation}/edit', [DealerLocationController::class, 'edit'])->name('edit')->middleware('permission:dealer-locations:edit');
        Route::put('/{dealerLocation}', [DealerLocationController::class, 'update'])->name('update')->middleware('permission:dealer-locations:edit');
        Route::delete('/{dealerLocation}', [DealerLocationController::class, 'destroy'])->name('destroy')->middleware('permission:dealer-locations:delete');
    });

    // ========== ITEM STOCK ==========
    Route::get('item-dashboard', [App\Http\Controllers\ItemDashboardController::class, 'index'])
        ->name('item-dashboard.index')
        ->middleware('permission:item-dashboard:view');
    Route::get('item-dashboard/export', [App\Http\Controllers\ItemDashboardController::class, 'export'])
        ->name('item-dashboard.export')
        ->middleware('permission:item-dashboard:view');
    Route::get('item-dashboard/export-pdf', [App\Http\Controllers\ItemDashboardController::class, 'exportPdf'])
        ->name('item-dashboard.export-pdf')
        ->middleware('permission:item-dashboard:view');
    Route::get('item-stocks/export', [App\Http\Controllers\ItemStockController::class, 'export'])->name('item-stocks.export');
    Route::get('item-stocks/export-pdf', [App\Http\Controllers\ItemStockController::class, 'exportPdf'])->name('item-stocks.export-pdf');
    Route::resource('item-stocks', App\Http\Controllers\ItemStockController::class)->only(['index', 'show']);

    // ========== STOCK ADJUSTMENTS ==========
    Route::get('stock-adjustments/export', [App\Http\Controllers\MsuStockAdjustmentController::class, 'export'])
        ->name('stock-adjustments.export');
    Route::get('stock-adjustments/export-pdf', [App\Http\Controllers\MsuStockAdjustmentController::class, 'exportPdf'])
        ->name('stock-adjustments.export-pdf');
    Route::resource('stock-adjustments', App\Http\Controllers\MsuStockAdjustmentController::class)->only(['index', 'create', 'store', 'show']);

    // ========== SEGMENTS ==========

    Route::prefix('segments')->name('segments.')->group(function () {

        Route::get('/', [SegmentController::class, 'index'])->name('index')->middleware('permission:segments:view');
        Route::get('/create', [SegmentController::class, 'create'])->name('create')->middleware('permission:segments:create');
        Route::post('/', [SegmentController::class, 'store'])->name('store')->middleware('permission:segments:create');
        Route::get('/{segment}', [SegmentController::class, 'show'])->name('show')->middleware('permission:segments:view');
        Route::get('/{segment}/edit', [SegmentController::class, 'edit'])->name('edit')->middleware('permission:segments:edit');
        Route::put('/{segment}', [SegmentController::class, 'update'])->name('update')->middleware('permission:segments:edit');
        Route::delete('/{segment}', [SegmentController::class, 'destroy'])->name('destroy')->middleware('permission:segments:delete');
        Route::get('/check-code/{code}', [SegmentController::class, 'checkCode'])->name('check-code')->middleware('permission:segments:checkcode');
    });

    // ========== DESIGNATION ==========
    Route::prefix('designation')->name('designation.')->group(function () {

        Route::get('/', [DesignationController::class, 'index'])
            ->name('index')
            ->middleware('permission:designation:view');

        Route::get('/export', [DesignationController::class, 'export'])
            ->name('export')
            ->middleware('permission:designation:view');

        Route::get('/export-pdf', [DesignationController::class, 'exportPdf'])
            ->name('export-pdf')
            ->middleware('permission:designation:view');

        Route::get('/create', [DesignationController::class, 'create'])
            ->name('create')
            ->middleware('permission:designation:create');

        Route::get('/{designation}/edit', [DesignationController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:designation:edit');

        Route::post('/', [DesignationController::class, 'store'])
            ->name('store')
            ->middleware('permission:designation:create');

        Route::delete('/{designation}', [DesignationController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:designation:delete');

        Route::put('/{designation}', [DesignationController::class, 'update'])
            ->name('update')
            ->middleware('permission:designation:edit');

        Route::put('/', [DesignationController::class, 'updateStatus'])
            ->name('status')
            ->middleware('permission:designation:status');
    });

    // ========== DEPARTMENT ==========
    Route::prefix('department')->name('department.')->group(function () {

        Route::get('/', [DepartmentController::class, 'index'])
            ->name('index')
            ->middleware('permission:department:view');

        Route::get('/export', [DepartmentController::class, 'export'])
            ->name('export')
            ->middleware('permission:department:view');

        Route::get('/export-pdf', [DepartmentController::class, 'exportPdf'])
            ->name('export-pdf')
            ->middleware('permission:department:view');

        Route::get('/create', [DepartmentController::class, 'create'])
            ->name('create')
            ->middleware('permission:department:create');

        Route::post('/', [DepartmentController::class, 'store'])
            ->name('store')
            ->middleware('permission:department:create');

        Route::get('/{department}/edit', [DepartmentController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:department:edit');

        Route::put('/{department}', [DepartmentController::class, 'update'])
            ->name('update')
            ->middleware('permission:department:edit');

        Route::delete('/{department}', [DepartmentController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:department:delete');

        Route::put('/', [DepartmentController::class, 'updateStatus'])
            ->name('status')
            ->middleware('permission:department:status');;
    });

    // ========== DIVISION ==========
    Route::prefix('division')->name('division.')->group(function () {

        Route::get('/', [DivisionController::class, 'index'])
            ->name('index')
            ->middleware('permission:division:view');

        Route::get('/export', [DivisionController::class, 'export'])
            ->name('export')
            ->middleware('permission:division:view');

        Route::get('/export-pdf', [DivisionController::class, 'exportPdf'])
            ->name('export-pdf')
            ->middleware('permission:division:view');

        Route::get('/create', [DivisionController::class, 'create'])
            ->name('create')
            ->middleware('permission:division:create');

        Route::post('/', [DivisionController::class, 'store'])
            ->name('store')
            ->middleware('permission:division:create');

        Route::get('/{division}/edit', [DivisionController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:division:edit');

        Route::put('/{division}', [DivisionController::class, 'update'])
            ->name('update')
            ->middleware('permission:division:edit');

        Route::delete('/{division}', [DivisionController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:division:delete');

        Route::put('/', [DivisionController::class, 'updateStatus'])
            ->name('status')
            ->middleware('permission:division:status');
    });

    // ========== PRODUCT CATEGORY ==========
    Route::prefix('product-categories')->name('product-categories.')->group(function () {

        Route::get('/', [ProductCategoryController::class, 'index'])
            ->name('index')
            ->middleware('permission:product-category:view');

        Route::get('/export', [ProductCategoryController::class, 'export'])
            ->name('export')
            ->middleware('permission:product-category:view');

        Route::get('/export-pdf', [ProductCategoryController::class, 'exportPdf'])
            ->name('export-pdf')
            ->middleware('permission:product-category:view');

        Route::get('/create', [ProductCategoryController::class, 'create'])
            ->name('create')
            ->middleware('permission:product-category:create');

        Route::post('/', [ProductCategoryController::class, 'store'])
            ->name('store')
            ->middleware('permission:product-category:create');

        Route::get('/{productCategory}/edit', [ProductCategoryController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:product-category:edit');

        Route::put('/{productCategory}', [ProductCategoryController::class, 'update'])
            ->name('update')
            ->middleware('permission:product-category:edit');

        Route::delete('/{productCategory}', [ProductCategoryController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:product-category:delete');

        Route::put('/', [ProductCategoryController::class, 'updateStatus'])
            ->name('status')
            ->middleware('permission:product-category:status');
    });

    // ========== UNIT ==========
    Route::prefix('units')->name('units.')->group(function () {

        Route::get('/', [UnitController::class, 'index'])
            ->name('index')
            ->middleware('permission:unit:view');

        Route::get('/export', [UnitController::class, 'export'])
            ->name('export')
            ->middleware('permission:unit:view');

        Route::get('/export-pdf', [UnitController::class, 'exportPdf'])
            ->name('export-pdf')
            ->middleware('permission:unit:view');

        Route::get('/create', [UnitController::class, 'create'])
            ->name('create')
            ->middleware('permission:unit:create');

        Route::post('/', [UnitController::class, 'store'])
            ->name('store')
            ->middleware('permission:unit:create');

        Route::get('/{unit}/edit', [UnitController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:unit:edit');

        Route::put('/{unit}', [UnitController::class, 'update'])
            ->name('update')
            ->middleware('permission:unit:edit');

        Route::delete('/{unit}', [UnitController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:unit:delete');

        Route::put('/', [UnitController::class, 'updateStatus'])
            ->name('status')
            ->middleware('permission:unit:status');
    });

    // ========== PAYMENT TERMS ==========
    Route::prefix('payment-terms')->name('payment-terms.')->group(function () {
        Route::get('/', [PaymentTermController::class, 'index'])->name('index')->middleware('permission:payment-term:view');
        Route::get('/export', [PaymentTermController::class, 'export'])->name('export')->middleware('permission:payment-term:view');
        Route::get('/export-pdf', [PaymentTermController::class, 'exportPdf'])->name('export-pdf')->middleware('permission:payment-term:view');
        Route::get('/create', [PaymentTermController::class, 'create'])->name('create')->middleware('permission:payment-term:create');
        Route::post('/', [PaymentTermController::class, 'store'])->name('store')->middleware('permission:payment-term:create');
        Route::get('/list-manage', [PaymentTermController::class, 'listForManage'])
            ->name('list-manage')
            ->middleware('permission:payment-term:view|msu-purchase-order:create|msu-purchase-order:edit');
        Route::post('/bulk-save', [PaymentTermController::class, 'bulkSave'])
            ->name('bulk-save')
            ->middleware('permission:payment-term:create|payment-term:edit|msu-purchase-order:create|msu-purchase-order:edit');
        Route::get('/{paymentTerm}/edit', [PaymentTermController::class, 'edit'])->name('edit')->middleware('permission:payment-term:edit');
        Route::put('/{paymentTerm}', [PaymentTermController::class, 'update'])->name('update')->middleware('permission:payment-term:edit');
        Route::delete('/{paymentTerm}', [PaymentTermController::class, 'destroy'])->name('destroy')->middleware('permission:payment-term:delete');
        Route::put('/', [PaymentTermController::class, 'updateStatus'])->name('status')->middleware('permission:payment-term:status');
    });

    // ========== MISCELLANEOUS CHARGES ==========
    Route::prefix('miscellaneous-charges')->name('miscellaneous-charges.')->group(function () {
        Route::get('/', [MiscellaneousChargeController::class, 'index'])->name('index')->middleware('permission:miscellaneous-charge:view');
        Route::get('/export', [MiscellaneousChargeController::class, 'export'])->name('export')->middleware('permission:miscellaneous-charge:view');
        Route::get('/export-pdf', [MiscellaneousChargeController::class, 'exportPdf'])->name('export-pdf')->middleware('permission:miscellaneous-charge:view');
        Route::get('/create', [MiscellaneousChargeController::class, 'create'])->name('create')->middleware('permission:miscellaneous-charge:create');
        Route::post('/', [MiscellaneousChargeController::class, 'store'])->name('store')->middleware('permission:miscellaneous-charge:create');
        Route::get('/list-manage', [MiscellaneousChargeController::class, 'listForManage'])
            ->name('list-manage')
            ->middleware('permission:miscellaneous-charge:view|msu-purchase-order:create|msu-purchase-order:edit');
        Route::post('/bulk-save', [MiscellaneousChargeController::class, 'bulkSave'])
            ->name('bulk-save')
            ->middleware('permission:miscellaneous-charge:create|miscellaneous-charge:edit|msu-purchase-order:create|msu-purchase-order:edit');
        Route::get('/{miscellaneousCharge}/edit', [MiscellaneousChargeController::class, 'edit'])->name('edit')->middleware('permission:miscellaneous-charge:edit');
        Route::put('/{miscellaneousCharge}', [MiscellaneousChargeController::class, 'update'])->name('update')->middleware('permission:miscellaneous-charge:edit');
        Route::delete('/{miscellaneousCharge}', [MiscellaneousChargeController::class, 'destroy'])->name('destroy')->middleware('permission:miscellaneous-charge:delete');
        Route::put('/', [MiscellaneousChargeController::class, 'updateStatus'])->name('status')->middleware('permission:miscellaneous-charge:status');
    });

    // ========== HSN ==========
    Route::prefix('hsns')->name('hsns.')->group(function () {
        Route::get('/', [HsnController::class, 'index'])->name('index')->middleware('permission:hsn:view');
        Route::get('/export', [HsnController::class, 'export'])->name('export')->middleware('permission:hsn:view');
        Route::get('/export-pdf', [HsnController::class, 'exportPdf'])->name('export-pdf')->middleware('permission:hsn:view');
        Route::get('/create', [HsnController::class, 'create'])->name('create')->middleware('permission:hsn:create');
        Route::post('/', [HsnController::class, 'store'])->name('store')->middleware('permission:hsn:create');
        Route::get('/{hsn}/edit', [HsnController::class, 'edit'])->name('edit')->middleware('permission:hsn:edit');
        Route::put('/{hsn}', [HsnController::class, 'update'])->name('update')->middleware('permission:hsn:edit');
        Route::delete('/{hsn}', [HsnController::class, 'destroy'])->name('destroy')->middleware('permission:hsn:delete');
        Route::put('/', [HsnController::class, 'updateStatus'])->name('status')->middleware('permission:hsn:status');
    });

    // ========== EMPLOYEE MASTER ==========
    Route::prefix('employee-masters')->name('employee-masters.')->group(function () {
        Route::get('/', [EmployeeMasterController::class, 'index'])->name('index')->middleware('permission:employee-master:view');
        Route::get('/export', [EmployeeMasterController::class, 'export'])->name('export')->middleware('permission:employee-master:view');
        Route::get('/export-pdf', [EmployeeMasterController::class, 'exportPdf'])->name('export-pdf')->middleware('permission:employee-master:view');
        Route::get('/create', [EmployeeMasterController::class, 'create'])->name('create')->middleware('permission:employee-master:create');
        Route::post('/', [EmployeeMasterController::class, 'store'])->name('store')->middleware('permission:employee-master:create');
        Route::get('/{employeeMaster}/edit', [EmployeeMasterController::class, 'edit'])->name('edit')->middleware('permission:employee-master:edit');
        Route::put('/{employeeMaster}', [EmployeeMasterController::class, 'update'])->name('update')->middleware('permission:employee-master:edit');
        Route::delete('/{employeeMaster}', [EmployeeMasterController::class, 'destroy'])->name('destroy')->middleware('permission:employee-master:delete');
        Route::put('/', [EmployeeMasterController::class, 'updateStatus'])->name('status')->middleware('permission:employee-master:status');
    });

    // ========== ASSET CUSTODIAN ==========
    Route::prefix('asset-custodians')->name('asset-custodians.')->group(function () {
        Route::get('/', [AssetCustodianController::class, 'index'])->name('index')->middleware('permission:asset-custodian:view');
        Route::get('/export', [AssetCustodianController::class, 'export'])->name('export')->middleware('permission:asset-custodian:view');
        Route::get('/export-pdf', [AssetCustodianController::class, 'exportPdf'])->name('export-pdf')->middleware('permission:asset-custodian:view');
        Route::get('/create', [AssetCustodianController::class, 'create'])->name('create')->middleware('permission:asset-custodian:create');
        Route::post('/quick-store', [AssetCustodianController::class, 'quickStore'])->name('quick-store');
        Route::post('/', [AssetCustodianController::class, 'store'])->name('store')->middleware('permission:asset-custodian:create');
        Route::get('/{assetCustodian}/edit', [AssetCustodianController::class, 'edit'])->name('edit')->middleware('permission:asset-custodian:edit');
        Route::put('/{assetCustodian}', [AssetCustodianController::class, 'update'])->name('update')->middleware('permission:asset-custodian:edit');
        Route::delete('/{assetCustodian}', [AssetCustodianController::class, 'destroy'])->name('destroy')->middleware('permission:asset-custodian:delete');
        Route::put('/', [AssetCustodianController::class, 'updateStatus'])->name('status')->middleware('permission:asset-custodian:status');
    });

    // ========== SHIPPING DETAILS ==========
    Route::prefix('shipping-details')->name('shipping-details.')->group(function () {
        Route::get('/', [ShippingDetailController::class, 'index'])->name('index')->middleware('permission:shipping-detail:view');
        Route::get('/export', [ShippingDetailController::class, 'export'])->name('export')->middleware('permission:shipping-detail:view');
        Route::get('/export-pdf', [ShippingDetailController::class, 'exportPdf'])->name('export-pdf')->middleware('permission:shipping-detail:view');
        Route::get('/create', [ShippingDetailController::class, 'create'])->name('create')->middleware('permission:shipping-detail:create');
        Route::post('/', [ShippingDetailController::class, 'store'])->name('store')->middleware('permission:shipping-detail:create');
        Route::get('/{shippingDetail}/edit', [ShippingDetailController::class, 'edit'])->name('edit')->middleware('permission:shipping-detail:edit');
        Route::put('/{shippingDetail}', [ShippingDetailController::class, 'update'])->name('update')->middleware('permission:shipping-detail:edit');
        Route::delete('/{shippingDetail}', [ShippingDetailController::class, 'destroy'])->name('destroy')->middleware('permission:shipping-detail:delete');
        Route::put('/', [ShippingDetailController::class, 'updateStatus'])->name('status')->middleware('permission:shipping-detail:status');
    });

    // ========== MSU PURCHASE ORDERS ==========
    Route::get('msu-purchase-orders/vendor-address/{vendor}', [MsuPurchaseOrderController::class, 'vendorAddress'])
        ->name('msu-purchase-orders.vendor-address')
        ->middleware('permission:msu-purchase-order:view|msu-purchase-order:create|msu-purchase-order:edit');
    Route::get('msu-purchase-orders/branch-details/{branch}', [MsuPurchaseOrderController::class, 'branchDetails'])
        ->name('msu-purchase-orders.branch-details')
        ->middleware('permission:msu-purchase-order:view|msu-purchase-order:create|msu-purchase-order:edit');
    Route::get('msu-purchase-orders/preview-po-number', [MsuPurchaseOrderController::class, 'previewPoNumber'])
        ->name('msu-purchase-orders.preview-po-number')
        ->middleware('permission:msu-purchase-order:view|msu-purchase-order:create|msu-purchase-order:edit');
    Route::get('msu-purchase-orders/export', [MsuPurchaseOrderController::class, 'export'])
        ->name('msu-purchase-orders.export')
        ->middleware('permission:msu-purchase-order:view');
    Route::get('msu-purchase-orders/export-pdf', [MsuPurchaseOrderController::class, 'exportPdf'])
        ->name('msu-purchase-orders.export-pdf')
        ->middleware('permission:msu-purchase-order:view');
    Route::get('msu-purchase-orders/{msuPurchaseOrder}/pdf', [MsuPurchaseOrderController::class, 'pdf'])
        ->name('msu-purchase-orders.pdf')
        ->middleware('permission:msu-purchase-order:view');
    Route::get('msu-purchase-orders/{msuPurchaseOrder}/excel', [MsuPurchaseOrderController::class, 'excel'])
        ->name('msu-purchase-orders.excel')
        ->middleware('permission:msu-purchase-order:view');
    Route::post('msu-purchase-orders/{msuPurchaseOrder}/approve', [MsuPurchaseOrderController::class, 'approve'])
        ->name('msu-purchase-orders.approve')
        ->middleware('permission:msu-purchase-order:approve');
    Route::post('msu-purchase-orders/{msuPurchaseOrder}/cancel', [MsuPurchaseOrderController::class, 'cancel'])
        ->name('msu-purchase-orders.cancel')
        ->middleware('permission:msu-purchase-order:cancel');
    Route::resource('msu-purchase-orders', MsuPurchaseOrderController::class);

    // ========== MSU WORK ORDERS ==========
    Route::get('msu-work-orders/preview-wo-number', [MsuWorkOrderController::class, 'previewWoNumber'])
        ->name('msu-work-orders.preview-wo-number')
        ->middleware('permission:msu-work-order:view|msu-work-order:create|msu-work-order:edit');
    Route::get('msu-work-orders/export', [MsuWorkOrderController::class, 'export'])
        ->name('msu-work-orders.export')
        ->middleware('permission:msu-work-order:view');
    Route::get('msu-work-orders/export-pdf', [MsuWorkOrderController::class, 'exportPdf'])
        ->name('msu-work-orders.export-pdf')
        ->middleware('permission:msu-work-order:view');
    Route::get('msu-work-orders/{msuWorkOrder}/pdf', [MsuWorkOrderController::class, 'pdf'])
        ->name('msu-work-orders.pdf')
        ->middleware('permission:msu-work-order:view');
    Route::get('msu-work-orders/{msuWorkOrder}/excel', [MsuWorkOrderController::class, 'excel'])
        ->name('msu-work-orders.excel')
        ->middleware('permission:msu-work-order:view');
    Route::post('msu-work-orders/{msuWorkOrder}/cancel', [MsuWorkOrderController::class, 'cancel'])
        ->name('msu-work-orders.cancel')
        ->middleware('permission:msu-work-order:cancel');

    Route::post('msu-work-orders/{msuWorkOrder}/approve', [MsuWorkOrderController::class, 'approve'])
        ->name('msu-work-orders.approve')
        ->middleware('permission:msu-work-order:approve');
    Route::resource('msu-work-orders', MsuWorkOrderController::class);

    // ========== MSU WORK INVOICES ==========
    Route::get('msu-work-invoices/export', [MsuWorkInvoiceController::class, 'export'])
        ->name('msu-work-invoices.export')
        ->middleware('permission:msu-work-invoice:view');
    Route::get('msu-work-invoices/export-pdf', [MsuWorkInvoiceController::class, 'exportPdf'])
        ->name('msu-work-invoices.export-pdf')
        ->middleware('permission:msu-work-invoice:view');
    Route::get('msu-work-invoices/{msuWorkInvoice}/pdf', [MsuWorkInvoiceController::class, 'pdf'])
        ->name('msu-work-invoices.pdf')
        ->middleware('permission:msu-work-invoice:view');
    Route::get('msu-work-invoices/{msuWorkInvoice}/excel', [MsuWorkInvoiceController::class, 'excel'])
        ->name('msu-work-invoices.excel')
        ->middleware('permission:msu-work-invoice:view');
    Route::get('msu-work-invoices/{msuWorkInvoice}/download-document', [MsuWorkInvoiceController::class, 'downloadDocument'])
        ->name('msu-work-invoices.download-document')
        ->middleware('permission:msu-work-invoice:view');
    Route::resource('msu-work-invoices', MsuWorkInvoiceController::class);

    // ========== MSU GRN (Goods Receipt) ==========
    Route::get('msu-grns/po-items/{msuPurchaseOrder}', [MsuGrnController::class, 'poItems'])
        ->name('msu-grns.po-items')
        ->middleware('permission:msu-grn:view|msu-grn:create');
    Route::get('msu-grns/preview-grn-number', [MsuGrnController::class, 'previewGrnNumber'])
        ->name('msu-grns.preview-grn-number')
        ->middleware('permission:msu-grn:view|msu-grn:create');
    Route::get('msu-grns/pos-by-company', [MsuGrnController::class, 'getPosByCompany'])
        ->name('msu-grns.pos-by-company')
        ->middleware('permission:msu-grn:view|msu-grn:create');
    Route::get('msu-grns/export', [MsuGrnController::class, 'export'])
        ->name('msu-grns.export')
        ->middleware('permission:msu-grn:view');
    Route::get('msu-grns/export-pdf', [MsuGrnController::class, 'exportPdf'])
        ->name('msu-grns.export-pdf')
        ->middleware('permission:msu-grn:view');
    Route::get('msu-grns/{msuGrn}/pdf', [MsuGrnController::class, 'pdf'])
        ->name('msu-grns.pdf')
        ->middleware('permission:msu-grn:view');
    Route::get('msu-grns/{msuGrn}/excel', [MsuGrnController::class, 'excel'])
        ->name('msu-grns.excel')
        ->middleware('permission:msu-grn:view');
    Route::post('msu-grns/{msuGrn}/add-to-stock', [MsuGrnController::class, 'addToStock'])
        ->name('msu-grns.add-to-stock')
        ->middleware('permission:msu-grn:approve|msu-grn:cancel');
    Route::post('msu-grns/{msuGrn}/approve-asset', [MsuGrnController::class, 'approveAsset'])
        ->name('msu-grns.approve-asset')
        ->middleware('permission:msu-grn:approve');
    Route::post('msu-grns/{msuGrn}/cancel-asset', [MsuGrnController::class, 'cancelAsset'])
        ->name('msu-grns.cancel-asset')
        ->middleware('permission:msu-grn:cancel');
    Route::get('msu-grns/{msuGrn}/edit', [MsuGrnController::class, 'edit'])
        ->name('msu-grns.edit')
        ->middleware('permission:msu-grn:edit|msu-grn:create');
    Route::put('msu-grns/{msuGrn}', [MsuGrnController::class, 'update'])
        ->name('msu-grns.update')
        ->middleware('permission:msu-grn:edit|msu-grn:create');
    Route::resource('msu-grns', MsuGrnController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    // ========== MATERIAL REQUEST & ISSUE ==========
    Route::get('material-requests/export', [MaterialRequestController::class, 'export'])
        ->name('material-requests.export')
        ->middleware('permission:material_requests:view');
    Route::get('material-requests/export-pdf', [MaterialRequestController::class, 'exportPdf'])
        ->name('material-requests.export-pdf')
        ->middleware('permission:material_requests:view');
    Route::get('material-requests/{materialRequest}/pdf', [MaterialRequestController::class, 'pdf'])
        ->name('material-requests.pdf')
        ->middleware('permission:material_requests:view');
    Route::get('material-requests/{materialRequest}/excel', [MaterialRequestController::class, 'excel'])
        ->name('material-requests.excel')
        ->middleware('permission:material_requests:view');
    Route::post('material-requests/{id}/submit', [MaterialRequestController::class, 'submit'])
        ->name('material-requests.submit')
        ->middleware('permission:material_requests:approve');
    Route::post('material-requests/{id}/cancel', [MaterialRequestController::class, 'cancel'])
        ->name('material-requests.cancel')
        ->middleware('permission:material_requests:cancel');
    Route::resource('material-requests', MaterialRequestController::class);
    
    Route::get('material-issues/export', [MaterialIssueController::class, 'export'])
        ->name('material-issues.export')
        ->middleware('permission:material_issues:view');
    Route::get('material-issues/export-pdf', [MaterialIssueController::class, 'exportPdf'])
        ->name('material-issues.export-pdf')
        ->middleware('permission:material_issues:view');
    Route::get('material-issues/{materialIssue}/pdf', [MaterialIssueController::class, 'pdf'])
        ->name('material-issues.pdf')
        ->middleware('permission:material_issues:view');
    Route::get('material-issues/{materialIssue}/excel', [MaterialIssueController::class, 'excel'])
        ->name('material-issues.excel')
        ->middleware('permission:material_issues:view');
    Route::post('material-issues/{id}/cancel', [MaterialIssueController::class, 'cancel'])
        ->name('material-issues.cancel');
    Route::get('material-issues/get-material-request-items/{id}', [MaterialIssueController::class, 'getMaterialRequestItems'])
        ->name('material-issues.get-material-request-items');
    Route::resource('material-issues', MaterialIssueController::class);

    // ========== PRODUCT SUB CATEGORY ==========
    Route::prefix('product-sub-categories')->name('product-sub-categories.')->group(function () {

        Route::get('/', [ProductSubCategoryController::class, 'index'])
            ->name('index')
            ->middleware('permission:product-sub-category:view');

        Route::get('/export', [ProductSubCategoryController::class, 'export'])
            ->name('export')
            ->middleware('permission:product-sub-category:view');

        Route::get('/export-pdf', [ProductSubCategoryController::class, 'exportPdf'])
            ->name('export-pdf')
            ->middleware('permission:product-sub-category:view');

        Route::get('/create', [ProductSubCategoryController::class, 'create'])
            ->name('create')
            ->middleware('permission:product-sub-category:create');

        Route::post('/', [ProductSubCategoryController::class, 'store'])
            ->name('store')
            ->middleware('permission:product-sub-category:create');

        Route::get('/{productSubCategory}/edit', [ProductSubCategoryController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:product-sub-category:edit');

        Route::put('/{productSubCategory}', [ProductSubCategoryController::class, 'update'])
            ->name('update')
            ->middleware('permission:product-sub-category:edit');

        Route::delete('/{productSubCategory}', [ProductSubCategoryController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:product-sub-category:delete');

        Route::put('/', [ProductSubCategoryController::class, 'updateStatus'])
            ->name('status')
            ->middleware('permission:product-sub-category:status');
    });

    Route::prefix('subsegment')->name('subsegment.')->group(function () {

        Route::get('/', [SubSegmentController::class, 'index'])
            ->name('index')
            ->middleware('permission:subsegments:view');

        Route::get('/create', [SubSegmentController::class, 'create'])
            ->name('create')
            ->middleware('permission:subsegments:create');

        Route::post('/', [SubSegmentController::class, 'store'])
            ->name('store')
            ->middleware('permission:subsegments:create');

        Route::get('/{subsegment}', [SubSegmentController::class, 'show'])
            ->name('show')
            ->middleware('permission:subsegments:view');

        Route::get('/{subsegment}/edit', [SubSegmentController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:subsegments:edit');

        Route::put('/{subsegment}', [SubSegmentController::class, 'update'])
            ->name('update')
            ->middleware('permission:subsegments:edit');

        Route::delete('/{subsegment}', [SubSegmentController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:subsegments:delete');

        Route::put('/', [SubSegmentController::class, 'updateStatus'])
            ->name('status')
            ->middleware('permission:subsegments:status');;
    });

    /*

    Route::prefix('employee')->name('employee.')->group(function () {

        Route::get('/', [EmployeeController::class, 'index'])
            ->name('index')
            ->middleware('permission:employee:view');

        Route::get('/create', [EmployeeController::class, 'create'])
            ->name('create')
            ->middleware('permission:employee:create');

        Route::post('/', [EmployeeController::class, 'store'])
            ->name('store')
            ->middleware('permission:employee:create');

        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:employee:edit');

        Route::post('/{employee}/update', [EmployeeController::class, 'update'])
            ->name('update')
            ->middleware('permission:employee:edit');

        Route::get('/{employee}/bySegment', [EmployeeController::class, 'getBySegment'])
            ->name('bySegment')
            ->middleware('permission:employee:view');

        Route::get('/{employee}/byLocations', [EmployeeController::class, 'byLocations'])
            ->name('byLocations')
            ->middleware('permission:employee:view');

        Route::get('by-multiple-companies', [EmployeeController::class, 'byMultipleCompanies'])
            ->name('byMultipleCompanies')->middleware('permission:employee:view');

    }); */


    Route::get('dashboard/asset', [AssetDashboardController::class, 'index'])->name('dashboard.asset');

    Route::get('dashboard/supportticket', [SupportTicketDashboardController::class, 'index'])
        ->name('dashboard.supportticket')
        ->middleware('permission:dashboard.supportticket:view');

    Route::resource('employees', EmployeesController::class)->names('employees');

    Route::resource('employee-staff', EmployeeStaffController::class)
        ->names('employee-staff')
        ->parameters(['employee-staff' => 'employee']);

    Route::get('ajax/getbranch/withoutscope/{company}', [AjaxController::class, 'getBranchesWithoutScope'])->name('ajax.getbranch.withoutscope');

    Route::post('ajax/getbranch/withoutscope/bycompanies', [AjaxController::class, 'getBrancheswithoutScopeByCompanies'])->name('ajax.getbranch.withoutscope.bycompanies');

    Route::post('ajax/getbranch/bycompanies', [AjaxController::class, 'getBranchesByCompanies'])->name('ajax.getbranch.bycompanies');

    Route::get('ajax/getbranch/bycompany/{company}', [AjaxController::class, 'getBranches'])->name('ajax.getbranch.bycompany');

    Route::get('ajax/getlocation/bybranch/{branch}', [AjaxController::class, 'getLocations'])->name('ajax.getlocation.bybranch');

    Route::get('ajax/getasset/bybranch/{branch}', [AjaxController::class, 'getAssets'])->name('ajax.getasset.bybranch');

    Route::get('ajax/getusers/bybranch/{branch}', [AjaxController::class, 'getUsers'])->name('ajax.getusers.bybranch');
    Route::get('ajax/getdepartments/bybranch/{branch}', [AjaxController::class, 'getDepartmentsByBranch'])->name('ajax.getdepartments.bybranch');

    Route::get('ajax/getsubcategory/bycategory/{category}', [AjaxController::class, 'getSubCategories'])->name('ajax.getsubcategory.bycategory');

    Route::get('ajax/getproductsubcategory/bycategory/{category}', [AjaxController::class, 'getProductSubCategories'])->name('ajax.getproductsubcategory.bycategory');

    Route::get('ajax/getassettype/byassetcategory/{category}', [AjaxController::class, 'getAssetTypes'])->name('ajax.getassettype.byassetcategory');

    Route::get('brands/export', [BrandController::class, 'export'])->name('brands.export');
    Route::get('brands/export-pdf', [BrandController::class, 'exportPdf'])->name('brands.export-pdf');

    Route::put('brands', [BrandController::class, 'updateStatus'])
        ->name('brands.status');

    Route::post('brand/quick-store', [BrandController::class, 'quickStore'])->name('brand.quick-store');

    Route::resource('brands', BrandController::class);

    Route::post('cost-centres/quick-store', [CostCentreController::class, 'quickStore'])->name('cost-centres.quick-store');
    Route::get('cost-centres/export', [CostCentreController::class, 'export'])->name('cost-centres.export');
    Route::get('cost-centres/export-pdf', [CostCentreController::class, 'exportPdf'])->name('cost-centres.export-pdf');
    Route::put('cost-centres/status', [CostCentreController::class, 'updateStatus'])->name('cost-centres.status');
    Route::resource('cost-centres', CostCentreController::class)->parameters([
        'cost-centres' => 'costCentre',
    ]);

    Route::put('asset-types/status', [AssetTypeController::class, 'updateStatus'])->name('asset-types.status');

    Route::resource('asset-types', AssetTypeController::class);

    Route::get('asset-names/suggest', [AssetNameController::class, 'suggest'])->name('asset-names.suggest');
    Route::post('asset-names/quick-store', [AssetNameController::class, 'quickStore'])->name('asset-names.quick-store');
    Route::put('asset-names/status', [AssetNameController::class, 'updateStatus'])->name('asset-names.status');
    Route::resource('asset-names', AssetNameController::class);

    Route::post('location/quick-store', [BranchLocationController::class, 'quickStore'])->name('location.quick-store');
    Route::get('location/export', [BranchLocationController::class, 'export'])->name('location.export');
    Route::get('location/export-pdf', [BranchLocationController::class, 'exportPdf'])->name('location.export-pdf');
    Route::put('location', [BranchLocationController::class, 'updateStatus'])->name('location.status');

    Route::resource('location', BranchLocationController::class);

    Route::get('branch/export', [BranchController::class, 'export'])->name('branch.export');
    Route::get('branch/export-pdf', [BranchController::class, 'exportPdf'])->name('branch.export-pdf');
    Route::post('branch/quick-store', [BranchController::class, 'quickStore'])->name('branch.quick-store');

    Route::resource('branch', BranchController::class);

    Route::put('asset-category/status', [AssetCategoryController::class, 'updateStatus'])->name('asset-category.status');

    Route::resource('asset-category', AssetCategoryController::class);

    // ========== ASSET MASTER (legacy item_masters) ==========
    Route::get('asset-masters/export', [ItemMasterController::class, 'export'])->name('asset-masters.export');
    Route::get('asset-masters/export-pdf', [ItemMasterController::class, 'exportPdf'])->name('asset-masters.export-pdf');
    Route::get('asset-masters/get-asset-types', [ItemMasterController::class, 'getAssetTypes'])->name('asset-masters.get-asset-types');
    Route::resource('asset-masters', ItemMasterController::class)->parameters([
        'asset_master' => 'itemMaster',
    ]);

    // ========== ITEM MASTER (MSU Purchase Order) ==========
    Route::get('item-masters/get-sub-categories', [MsuItemMasterController::class, 'getSubCategories'])->name('item-masters.get-sub-categories');
    Route::put('item-masters/status', [MsuItemMasterController::class, 'updateStatus'])->name('item-masters.status');
    Route::get('item-masters/export', [MsuItemMasterController::class, 'export'])->name('item-masters.export');
    Route::get('item-masters/export-pdf', [MsuItemMasterController::class, 'exportPdf'])->name('item-masters.export-pdf');
    Route::resource('item-masters', MsuItemMasterController::class)->parameters([
        'item-masters' => 'msuItemMaster',
    ]);

    Route::post('asset/assignment/bulk', [AssetAssignmentController::class, 'assignBulkProcess'])->name('asset.assign.bulk.process');

    Route::get('asset/assignment/bulk', [AssetAssignmentController::class, 'assignBulk'])->name('asset.assign.bulk');

    Route::get('asset/assignment/assign-targets/{type}', [AssetAssignmentController::class, 'getTargets'])->name('asset.assign.targets');

    Route::get('asset/assignment/accept/{assignment}', [AssetAssignmentController::class, 'acceptassignment'])->name('asset.assignment.accept');

    Route::put('asset/assignment/accept/{assignment}', [AssetAssignmentController::class, 'acceptassetassignment'])->name('asset.assignment.accept.update');

    Route::get('asset/assignment/reject/{assignment}', [AssetAssignmentController::class, 'rejectassignment'])->name('asset.assignment.reject');

    Route::put('asset/assignment/reject/{assignment}', [AssetAssignmentController::class, 'rejectassetassignment'])->name('asset.assignment.reject.update');

    Route::get('asset/assignment/returnaccept/{assignment}', [AssetAssignmentController::class, 'acceptReturn'])->name('asset.assignment.return.accept');

    Route::put('asset/assignment/returnaccept/{assignment}', [AssetAssignmentController::class, 'acceptReturnUpdate'])->name('asset.assignment.return.accept.update');

    Route::get('asset/assignment/return/{assignment}', [AssetAssignmentController::class, 'returnassignment'])->name('asset.assignment.return');

    Route::put('asset/assignment/return/{assignment}', [AssetAssignmentController::class, 'returnassetassignment'])->name('asset.assignment.return.confirm');

    Route::put('asset/assignment/{assignment}/assign-date', [AssetAssignmentController::class, 'updateAssignDate'])
        ->middleware('permission:asset.assignment:edit')
        ->name('asset.assignment.assign-date');

    Route::resource('asset/assignment', AssetAssignmentController::class)->names('asset.assignment');

    Route::resource('asset/warranties', AssetWarrantyController::class)->names('asset.warranty');

    Route::resource('asset/document', AssetDocumentController::class)->names('asset.document');

    Route::resource('asset/maintenance', AssetMaintenanceController::class)->names('asset.maintenance');

    Route::resource('asset/asset-replacement', AssetReplacementController::class)->names('asset.replacement');

    Route::get('asset-depreciation/export', [AssetController::class, 'exportDepreciation'])->name('asset.depreciation.export');
    Route::get('asset-depreciation/export-pdf', [AssetController::class, 'exportDepreciationPdf'])->name('asset.depreciation.export-pdf');
    Route::get('asset-depreciation', [AssetController::class, 'depreciation'])->name('asset.depreciation');

    Route::get('asset/import/move-valid-data', [AssetController::class, 'moveValidData'])->name('asset.import.movevaliddata');

    Route::get('asset/import/preview', [AssetController::class, 'importPreview'])->name('asset.import.preview');

    Route::get('asset/export', [AssetController::class, 'export'])->name('asset.export');
    Route::get('asset/export-pdf', [AssetController::class, 'exportPdf'])->name('asset.export-pdf');
    Route::get('asset/{asset}/export', [AssetController::class, 'exportDetail'])->name('asset.detail-export');
    Route::get('asset/{asset}/export-pdf', [AssetController::class, 'exportDetailPdf'])->name('asset.detail-export-pdf');
    Route::get('asset/{asset}/handover-pdf', [AssetController::class, 'exportHandoverPdf'])->name('asset.handover-export-pdf');
    Route::get('asset/{asset}/qr-label-pdf', [AssetController::class, 'exportQrLabelPdf'])->name('asset.qr-label-pdf');
    Route::get('asset/{asset}/print-label', [AssetController::class, 'printAssetLabel'])->name('asset.print-label');
    Route::post('asset/{asset}/print-label', [AssetController::class, 'sendAssetLabel'])->name('asset.print-label.send');
    Route::get('asset/{asset}/print-label/commands', [AssetController::class, 'downloadAssetLabelCommands'])->name('asset.print-label.commands');

    Route::get('asset/preview-code', [AssetController::class, 'previewAssetCode'])->name('asset.preview-code');

    Route::get('asset/import', [AssetController::class, 'import'])->name('asset.import');

    Route::post('asset/import', [AssetController::class, 'importAsset'])->name('asset.import.process');

    Route::resource('asset', AssetController::class);
    Route::post('asset/{asset}/audit', [AssetController::class, 'recordAuditWeb'])->name('asset.audit.store');

    Route::get('vendor/export', [VendorController::class, 'export'])->name('vendor.export');
    Route::get('vendor/export-pdf', [VendorController::class, 'exportPdf'])->name('vendor.export-pdf');
    Route::post('vendor/quick-store', [VendorController::class, 'quickStore'])->name('vendor.quick-store');

    Route::resource('brands', BrandController::class);

    Route::resource('vendor', VendorController::class);

    Route::resource('settings', SettingsController::class);

    // Indent Management Routes
    Route::prefix('indents')->name('indents.')->group(function () {
        Route::get('/dashboard', [IndentController::class, 'dashboard'])
            ->name('dashboard')
            ->middleware('permission:indents:view');
        Route::get('/approvals', [IndentController::class, 'approvalsDashboard'])
            ->name('approvals')
            ->middleware('permission:indents:view');
        Route::get('/', [IndentController::class, 'index'])
            ->name('index')
            ->middleware('permission:indents:view');
        Route::get('/create', [IndentController::class, 'create'])
            ->name('create')
            ->middleware('permission:indents:create');
        Route::get('/items/suggest', [IndentController::class, 'suggestItems'])
            ->name('items.suggest')
            ->middleware('permission:indents:create|indents:edit');
        Route::get('/assets/search', [IndentController::class, 'searchMyAssets'])
            ->name('assets.search')
            ->middleware('permission:indents:create|indents:edit');
        Route::get('/fields/suggest', [IndentController::class, 'suggestFields'])
            ->name('fields.suggest')
            ->middleware('permission:indents:create|indents:edit');
        Route::post('/', [IndentController::class, 'store'])
            ->name('store')
            ->middleware('permission:indents:create');
        Route::get('/{indent}', [IndentController::class, 'show'])
            ->name('show')
            ->middleware('permission:indents:view');
        Route::get('/{indent}/edit', [IndentController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:indents:edit|indents:create');
        Route::put('/{indent}', [IndentController::class, 'update'])
            ->name('update')
            ->middleware('permission:indents:edit|indents:create');
        Route::delete('/{indent}', [IndentController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:indents:delete');
        // Authorization for approve/reject/send-back is enforced inside ApprovalWorkflowService
        // against the indent's currently assigned_permission (dynamic per workflow step).
        Route::post('/{indent}/approve', [IndentController::class, 'approve'])
            ->name('approve')
            ->middleware('permission:indents:approve|indents:view');
        Route::post('/{indent}/reject', [IndentController::class, 'reject'])
            ->name('reject')
            ->middleware('permission:indents:reject|indents:approve|indents:view');
        Route::post('/{indent}/send-back', [IndentController::class, 'sendBack'])
            ->name('send-back')
            ->middleware('permission:indents:approve|indents:view');
        Route::post('/{indent}/submit', [IndentController::class, 'submit'])->name('submit')->middleware('permission:indents:create');
        Route::post('/{indent}/cancel', [IndentController::class, 'cancel'])->name('cancel')->middleware('permission:indents:edit');
        Route::get('/{indent}/print', [IndentController::class, 'print'])->name('print')->middleware('permission:indents:view');
        Route::get('/{indent}/pdf', [IndentController::class, 'pdf'])->name('pdf')->middleware('permission:indents:view');
        Route::get('/{indent}/excel', [IndentController::class, 'excel'])->name('excel')->middleware('permission:indents:view');
    });

    // Settings → Approval Templates (permission-based; assign via Roles)
    Route::prefix('approval-templates')->name('approval-templates.')->middleware('permission:approval-templates:view|approval-templates:manage')->group(function () {
        Route::get('/', [ApprovalTemplateController::class, 'index'])->name('index');
        Route::post('/', [ApprovalTemplateController::class, 'store'])
            ->name('store')
            ->middleware('permission:approval-templates:create|approval-templates:manage');
        Route::put('/{approvalTemplate}', [ApprovalTemplateController::class, 'update'])
            ->name('update')
            ->middleware('permission:approval-templates:edit|approval-templates:manage');
        Route::post('/{approvalTemplate}/toggle', [ApprovalTemplateController::class, 'toggle'])
            ->name('toggle')
            ->middleware('permission:approval-templates:edit|approval-templates:manage');
        Route::delete('/{approvalTemplate}', [ApprovalTemplateController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:approval-templates:delete|approval-templates:manage');
        Route::post('/{approvalTemplate}/steps', [ApprovalTemplateController::class, 'storeStep'])
            ->name('steps.store')
            ->middleware('permission:approval-templates:edit|approval-templates:manage');
        Route::put('/steps/{step}', [ApprovalTemplateController::class, 'updateStep'])
            ->name('steps.update')
            ->middleware('permission:approval-templates:edit|approval-templates:manage');
        Route::post('/steps/{step}/move', [ApprovalTemplateController::class, 'moveStep'])
            ->name('steps.move')
            ->middleware('permission:approval-templates:edit|approval-templates:manage');
        Route::delete('/steps/{step}', [ApprovalTemplateController::class, 'destroyStep'])
            ->name('steps.destroy')
            ->middleware('permission:approval-templates:edit|approval-templates:manage');
    });

    Route::get('procurement/dashboard', [ProcurementDashboardController::class, 'index'])
        ->name('procurement.dashboard')
        ->middleware('permission:procurement.dashboard:view|purchase-management.dashboard|indents:view|purchase-requisition.view|purchase-requisitions:view|grn.view|grns:view');

    // Purchase Management (Purchase Officer)
    Route::get('purchase-management/dashboard', [PurchaseOfficerDashboardController::class, 'index'])
        ->name('purchase-management.dashboard')
        ->middleware('permission:purchase-management.dashboard|purchase-requisition.view|purchase-requisitions:view');

    Route::prefix('purchase-requisitions')->name('purchase-requisitions.')->middleware('permission:purchase-requisition.view|purchase-requisitions:view')->group(function () {
        Route::get('/', [PurchaseRequisitionController::class, 'index'])->name('index');
        Route::post('/{purchaseRequisition}/close', [PurchaseRequisitionController::class, 'close'])
            ->name('close')
            ->middleware('permission:purchase-requisition.process|purchase-requisitions:process|purchase-requisition.close|purchase-requisition.manual-close|purchase-requisitions:manual-close');
        Route::post('/{purchaseRequisition}/transfer', [PurchaseRequisitionController::class, 'transfer'])
            ->name('transfer')
            ->middleware('permission:purchase-requisition.process|purchase-requisitions:process|purchase-requisition.transfer-from-store|purchase-requisitions:transfer-from-store');
        Route::get('/{purchaseRequisition}/transfer-availability', [PurchaseRequisitionController::class, 'transferAvailability'])
            ->name('transfer-availability')
            ->middleware('permission:purchase-requisition.process|purchase-requisitions:process|purchase-requisition.transfer-from-store|purchase-requisitions:transfer-from-store');
        Route::post('/{purchaseRequisition}/process', [PurchaseRequisitionController::class, 'process'])
            ->name('process')
            ->middleware('permission:purchase-requisition.process|purchase-requisitions:process');
        Route::get('/{purchaseRequisition}', [PurchaseRequisitionController::class, 'show'])->name('show');
    });

    Route::prefix('rfqs')->name('rfqs.')->middleware('permission:rfq.view|rfqs:view|rfq.create|rfqs:create|purchase-requisition.proceed-to-rfq|purchase-requisitions:proceed-to-rfq|purchase-requisition.process|purchase-requisitions:process')->group(function () {
        Route::get('/', [RfqController::class, 'index'])->name('index');
        Route::get('/create', [RfqController::class, 'create'])->name('create')->middleware('permission:rfq.create|rfqs:create|purchase-requisition.proceed-to-rfq|purchase-requisitions:proceed-to-rfq|purchase-requisition.process|purchase-requisitions:process');
        Route::post('/', [RfqController::class, 'store'])->name('store')->middleware('permission:rfq.create|rfqs:create|purchase-requisition.proceed-to-rfq|purchase-requisitions:proceed-to-rfq|purchase-requisition.process|purchase-requisitions:process');
        Route::get('/purchase-requisitions/{purchaseRequisition}/data', [RfqController::class, 'prData'])->name('purchase-requisitions.data')->middleware('permission:rfq.create|rfqs:create|purchase-requisition.proceed-to-rfq|purchase-requisitions:proceed-to-rfq|purchase-requisition.process|purchase-requisitions:process');
        Route::get('/{rfq}/edit', [RfqController::class, 'edit'])->name('edit')->middleware('permission:rfq.edit|rfqs:edit');
        Route::put('/{rfq}', [RfqController::class, 'update'])->name('update')->middleware('permission:rfq.edit|rfqs:edit');
        Route::delete('/{rfq}', [RfqController::class, 'destroy'])->name('destroy')->middleware('permission:rfq.delete|rfqs:delete');
        Route::get('/{rfq}/attachments/{attachment}/{mode?}', [RfqController::class, 'attachment'])
            ->name('attachments.show')
            ->where('mode', 'view|download');
        Route::get('/{rfq}/pdf', [RfqController::class, 'pdf'])->name('pdf')->middleware('permission:rfq.view|rfqs:view');
        Route::get('/{rfq}/excel', [RfqController::class, 'excel'])->name('excel')->middleware('permission:rfq.view|rfqs:view');
        Route::get('/{rfq}', [RfqController::class, 'show'])->name('show');
        Route::get('/{rfq}/compare', [VendorQuotationController::class, 'compare'])->name('compare')->middleware('permission:quotation.compare|quotations:compare');
        Route::post('/{rfq}/invite', [RfqController::class, 'invite'])->name('invite')->middleware('permission:rfq.send|rfqs:send|rfq.edit|rfqs:edit');
        Route::post('/{rfq}/send', [RfqController::class, 'send'])->name('send')->middleware('permission:rfq.send|rfqs:send|rfq.edit|rfqs:edit|rfq.create|rfqs:create');
        Route::post('/{rfq}/close', [RfqController::class, 'close'])->name('close')->middleware('permission:rfq.close|rfq.edit|rfqs:edit');
        Route::get('/{rfq}/quotations/create', [VendorQuotationController::class, 'create'])->name('quotations.create')->middleware('permission:quotation.add|quotations:create');
        Route::post('/{rfq}/quotations', [VendorQuotationController::class, 'store'])->name('quotations.store')->middleware('permission:quotation.add|quotations:create');
        Route::get('/{rfq}/quotations/{quotation}', [VendorQuotationController::class, 'show'])->name('quotations.show');
    });

    Route::post('vendor-quotations/{quotation}/select', [VendorQuotationController::class, 'select'])
        ->name('vendor-quotations.select')
        ->middleware('permission:quotation.select-vendor|quotations:select-vendor|quotations.select-vendor|quotation:select-vendor|vendor-quotations.select|vendor-quotations:select|select-vendor|select vendor|quotation.select|quotations:select|rfqs:select-vendor|rfq.select-vendor');
    Route::get('vendor-quotations/{quotation}/negotiation', [VendorQuotationController::class, 'negotiationData'])
        ->name('vendor-quotations.negotiation.show')
        ->middleware('permission:quotation.negotiate|quotation.compare|quotations:compare|quotation.view|quotations:view');
    Route::post('vendor-quotations/{quotation}/negotiation', [VendorQuotationController::class, 'saveNegotiation'])
        ->name('vendor-quotations.negotiation.save')
        ->middleware('permission:quotation.negotiate');

    Route::get('/procurement/items/history', [PurchaseOrderController::class, 'itemHistory'])
        ->name('procurement.items.history');

    Route::prefix('purchase-orders')->name('purchase-orders.')->middleware('permission:purchase-order.view|purchase-orders:view')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create')->middleware('permission:purchase-order.create|purchase-orders:create');
        Route::post('/', [PurchaseOrderController::class, 'store'])->name('store')->middleware('permission:purchase-order.create|purchase-orders:create');
        Route::get('/items/search', [PurchaseOrderController::class, 'searchItems'])->name('items.search')->middleware('permission:purchase-order.create|purchase-orders:create|purchase-order.edit|purchase-orders:edit');
        Route::get('/vendor-rates', [PurchaseOrderController::class, 'searchVendorRates'])->name('vendor-rates')->middleware('permission:purchase-order.create|purchase-orders:create|purchase-order.edit|purchase-orders:edit');
        Route::get('/purchase-requisitions/{purchaseRequisition}/data', [PurchaseOrderController::class, 'prData'])->name('purchase-requisitions.data')->middleware('permission:purchase-order.create|purchase-orders:create|purchase-order.edit|purchase-orders:edit');
        Route::get('/indents/{indent}/data', [PurchaseOrderController::class, 'indentData'])->name('indents.data')->middleware('permission:purchase-order.create|purchase-orders:create|purchase-order.edit|purchase-orders:edit');
        Route::get('/create-from-quotation/{quotation}', [PurchaseOrderController::class, 'createFromQuotation'])->name('create-from-quotation')->middleware('permission:purchase-order.create|purchase-orders:create');
        Route::post('/create-from-quotation/{quotation}', [PurchaseOrderController::class, 'storeFromQuotation'])->name('store-from-quotation')->middleware('permission:purchase-order.create|purchase-orders:create');
        Route::get('/{purchaseOrder}/print', [PurchaseOrderController::class, 'print'])->name('print')->middleware('permission:purchase-order.print|purchase-order.view|purchase-orders:view');
        Route::post('/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])->name('submit')->middleware('permission:purchase-order.create|purchase-orders:create|purchase-order.submit');
        // Workflow assignment is enforced in PurchaseOrderApprovalService; route only needs approve/view (not create).
        Route::post('/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('approve')
            ->middleware('permission:purchase-order.approve|purchase-orders:approve|purchase-order.view|purchase-orders:view');
        Route::post('/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->name('reject')
            ->middleware('permission:purchase-order.reject|purchase-order.approve|purchase-orders:approve|purchase-order.view|purchase-orders:view');
        Route::post('/{purchaseOrder}/send-back', [PurchaseOrderController::class, 'sendBack'])->name('send-back')
            ->middleware('permission:purchase-order.send-back|purchase-order.approve|purchase-orders:approve|purchase-order.view|purchase-orders:view');
        Route::get('/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('edit')->middleware('permission:purchase-order.edit|purchase-orders:edit');
        Route::put('/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('update')->middleware('permission:purchase-order.edit|purchase-orders:edit');
        Route::delete('/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('destroy')->middleware('permission:purchase-order.delete|purchase-orders:delete');
        Route::post('/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('cancel')->middleware('permission:purchase-order.cancel');
        Route::get('/{purchaseOrder}/payments/summary', [PurchaseOrderController::class, 'paymentSummary'])
            ->name('payments.summary')
            ->middleware('permission:purchase-order.payment|purchase-orders:payment|purchase-order.pay');
        Route::post('/{purchaseOrder}/payments', [PurchaseOrderController::class, 'storePayment'])
            ->name('payments.store')
            ->middleware('permission:purchase-order.payment|purchase-orders:payment|purchase-order.pay');
        Route::get('/payments/{payment}/attachment/{mode?}', [PurchaseOrderController::class, 'paymentAttachment'])
            ->name('payments.attachment')
            ->where('mode', 'view|download');
        Route::post('/{purchaseOrder}/payment-request', [PaymentRequestController::class, 'storeFromPo'])
            ->name('payment-request.store')
            ->middleware('permission:payment.create|payments:create|purchase-order.create|purchase-orders:create|purchase-order.edit|purchase-orders:edit');
        Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('show');
    });

    Route::post('purchase-orders/{purchaseOrder}/payment-request', [PaymentRequestController::class, 'storeFromPo'])
        ->name('payment-request.store')
        ->middleware('permission:payment.create|payments:create|purchase-order.create|purchase-orders:create|purchase-order.edit|purchase-orders:edit');

    Route::prefix('payments')->name('payments.')->middleware('permission:payment.view|payments:view|purchase-order.payment|purchase-orders:payment|purchase-order.view|purchase-orders:view')->group(function () {
        Route::get('/', [PaymentRequestController::class, 'index'])->name('index');
        Route::get('/{paymentRequest}', [PaymentRequestController::class, 'show'])->name('show');
        Route::post('/{paymentRequest}/approve', [PaymentRequestController::class, 'approve'])->name('approve')->middleware('permission:payment.approve|payments:approve|purchase-order.payment|purchase-orders:payment|purchase-order.pay');
        Route::post('/{paymentRequest}/reject', [PaymentRequestController::class, 'reject'])->name('reject')->middleware('permission:payment.approve|payments:approve|purchase-order.payment|purchase-orders:payment|purchase-order.pay');
        Route::post('/{paymentRequest}/pay', [PaymentRequestController::class, 'pay'])->name('pay')->middleware('permission:payment.pay|payments:pay|purchase-order.payment|purchase-orders:payment|purchase-order.pay');
    });

    Route::prefix('grns')->name('grns.')->middleware('permission:grn.view|grns:view')->group(function () {
        Route::get('/', [GrnController::class, 'index'])->name('index');
        Route::get('/create/{purchaseOrder}', [GrnController::class, 'create'])->name('create')->middleware('permission:grn.create|grns:create');
        Route::post('/create/{purchaseOrder}', [GrnController::class, 'store'])->name('store')->middleware('permission:grn.create|grns:create');
        Route::get('/{grn}', [GrnController::class, 'show'])->name('show');
        Route::get('/{grn}/documents/{document}/{mode?}', [GrnController::class, 'document'])
            ->name('documents.show')
            ->where('mode', 'view|download');
        Route::post('/{grn}/approve', [GrnController::class, 'approve'])->name('approve')->middleware('permission:grn.approve|grn.inspect|grns:approve');
        Route::post('/{grn}/create-assets', [QualityInspectionController::class, 'createAssets'])->name('create-assets')->middleware('permission:quality-inspection.perform|quality-inspections:perform|grn.approve|grns:approve|asset:create|asset.create');
        Route::post('/{grn}/items/{item}/create-assets', [QualityInspectionController::class, 'createItemAssets'])->name('create-item-assets')->middleware('permission:quality-inspection.perform|quality-inspections:perform|grn.approve|grns:approve|asset:create|asset.create');
    });

    Route::prefix('quality-inspections')->name('quality-inspections.')->middleware('permission:quality-inspection.view|quality-inspections:view|quality-inspection.perform|quality-inspections:perform')->group(function () {
        Route::get('/', [QualityInspectionController::class, 'index'])->name('index');
        Route::get('/{grn}', [QualityInspectionController::class, 'show'])->name('show');
        Route::post('/{grn}/items/{item}/inspect', [QualityInspectionController::class, 'inspectItem'])->name('inspect-item')->middleware('permission:quality-inspection.perform|quality-inspections:perform');
        Route::post('/{grn}/inspect-batch', [QualityInspectionController::class, 'inspectBatch'])->name('inspect-batch')->middleware('permission:quality-inspection.perform|quality-inspections:perform');
        Route::post('/{grn}/create-assets', [QualityInspectionController::class, 'createAssets'])->name('create-assets')->middleware('permission:quality-inspection.perform|quality-inspections:perform|asset:create|asset.create');
        Route::post('/{grn}/items/{item}/create-assets', [QualityInspectionController::class, 'createItemAssets'])->name('create-item-assets')->middleware('permission:quality-inspection.perform|quality-inspections:perform|asset:create|asset.create');
    });

    Route::get('procurement/reports', [ProcurementReportController::class, 'index'])
        ->name('procurement.reports')
        ->middleware('permission:purchase-report.view|procurement.reports:view');
    Route::get('procurement/reports/{report}', [ProcurementReportController::class, 'show'])
        ->name('procurement.reports.show')
        ->middleware('permission:purchase-report.view|procurement.reports:view');

    Route::any('reports/companywiseassets', [ReportsController::class, 'companywiseassets'])->name('reports.companywiseassets');

    Route::any('reports/branchwiseassets', [ReportsController::class, 'branchwiseassets'])->name('reports.branchwiseassets');

    Route::any('reports/brandwiseassets', [ReportsController::class, 'brandwiseassets'])->name('reports.brandwiseassets');

    Route::any('reports/vendorwiseassets', [ReportsController::class, 'vendorwiseassets'])->name('reports.vendorwiseassets');

    Route::any('reports/locationwiseassets', [ReportsController::class, 'locationwiseassets'])->name('reports.locationwiseassets');

    Route::any('reports/categorywiseassets', [ReportsController::class, 'categorywiseassets'])->name('reports.categorywiseassets');

    Route::any('reports/typewiseassets', [ReportsController::class, 'typewiseassets'])->name('reports.typewiseassets');

    Route::any('reports/assetwisesupportticket', [ReportsController::class, 'assetwisesupportticket'])->name('reports.assetwisesupportticket');
    Route::any('reports/branchwisesupportticket', [ReportsController::class, 'branchwisesupportticket'])->name('reports.branchwisesupportticket');
    Route::any('reports/departmentwisesupportticket', [ReportsController::class, 'departmentwisesupportticket'])->name('reports.departmentwisesupportticket');
    Route::any('reports/userwisesupportticket', [ReportsController::class, 'userwisesupportTicket'])->name('reports.userwisesupportticket');

    Route::get('support-ticket/preview-ticket-number', [SupportTicketController::class, 'previewTicketNumber'])->name('support-ticket.preview-ticket-number');
    Route::post('support-ticket/reopen/{ticket}', [SupportTicketController::class, 'reopenTicket'])->name('support-ticket.reopenticket');
    Route::post('support-ticket/close/{ticket}', [SupportTicketController::class, 'closeTicket'])->name('support-ticket.closeticket');
    Route::post('support-ticket/reject/{ticket}', [SupportTicketController::class, 'rejectTicket'])->name('support-ticket.rejectticket');
    Route::get('support-ticket/accept/{ticket}', [SupportTicketController::class, 'acceptTicket'])->name('support-ticket.accept');
    Route::post('support-ticket/complete/{ticket}', [SupportTicketController::class, 'completeTicket'])->name('support-ticket.complete');
    Route::post('support-ticket/exit/{ticket}', [SupportTicketController::class, 'exitTicket'])->name('support-ticket.exit');
    Route::put('support-ticket/assign/{ticket}', [SupportTicketController::class, 'assignTicket'])->name('support-ticket.assignticket');
    Route::post('support-ticket/reply/{ticket}', [SupportTicketController::class, 'replyTicket'])->name('support-ticket.replyticket');
    Route::put('support-ticket/update-status/{ticket}', [SupportTicketController::class, 'updateStatus'])->name('support-ticket.status.update');
    Route::get('support-ticket/progress-statuses', [SupportTicketProgressStatusController::class, 'index'])->name('support-ticket.progress-statuses.index');
    Route::post('support-ticket/progress-statuses', [SupportTicketProgressStatusController::class, 'store'])->name('support-ticket.progress-statuses.store');
    Route::put('support-ticket/progress-statuses/{progressStatus}', [SupportTicketProgressStatusController::class, 'update'])->name('support-ticket.progress-statuses.update');
    Route::delete('support-ticket/progress-statuses/{progressStatus}', [SupportTicketProgressStatusController::class, 'destroy'])->name('support-ticket.progress-statuses.destroy');
    Route::resource('support-ticket', SupportTicketController::class)->names('support-ticket');
});

Route::post('/set-global-company', [\App\Http\Controllers\GlobalFilterController::class, 'setCompany'])->name('set-global-company');

// Optimization Route for Live Server
Route::get('/optimize-live', function() {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    \Illuminate\Support\Facades\Artisan::call('optimize');
    return 'Optimization complete. The project should now run much faster!';
});
