<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DesignationController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\StockLevelController;
use App\Http\Controllers\Admin\StockAlertController;
use App\Http\Controllers\Admin\StockUnitController;
use App\Http\Controllers\Admin\OpeningStockRequestController;
use App\Http\Controllers\Admin\AdjustmentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StockCountController;
use App\Http\Controllers\Admin\TransferController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\GoodsReceiptController;
use App\Http\Controllers\Branch\BranchController;
use App\Http\Controllers\Warehouse\WarehouseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Services\LandingService;
use App\Http\Controllers\NotificationController;

Route::get('/', function () {
    return Auth::check()
        ? app(LandingService::class)->redirect(Auth::user())
        : redirect()->route('login');
});

Route::get('login', [LoginController::class, 'view'])->name('login');
Route::post('login', [LoginController::class, 'login'])->name('login.submit');
Route::post('logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});



Route::group(['middleware' => ['auth', 'active'], 'prefix' => 'admin', 'as' => 'admin.'], function () {
//logout

// dashboard
    Route::get('dashboard', [DashboardController::class, 'dashboard'])
        ->middleware('can:dashboard.view')
        ->name('dashboard');

    // role
    Route::get('roles', [RoleController::class, 'list'])->middleware('can:roles.view')->name('roles.list');
    Route::get('roles/create', [RoleController::class, 'create'])->middleware('can:roles.create')->name('roles.create');
    Route::post('roles/store', [RoleController::class, 'store'])->middleware('can:roles.create')->name('roles.store');
    Route::get('roles/edit/{id}', [RoleController::class, 'edit'])->middleware('can:roles.edit')->name('roles.edit');
    Route::put('roles/update/{id}', [RoleController::class, 'update'])->middleware('can:roles.edit')->name('roles.update');
    Route::delete('roles/delete/{id}', [RoleController::class, 'delete'])->middleware('can:roles.delete')->name('roles.delete');
    Route::get('roles/audit-log/{id}', [RoleController::class, 'auditLog'])->middleware('can:roles.view')->name('roles.audit-log');
    Route::get('activity-log', [RoleController::class, 'activityLog'])->middleware('can:roles.view')->name('activity-log');
    Route::get('roles/permissions/assign/{id}', [RoleController::class, 'permissionAssign'])->middleware('can:roles.assign')->name('roles.permissions.assign');
    Route::post('roles/permissions/assign/store/{id}', [RoleController::class, 'permissionAssignStore'])->middleware('can:roles.assign')->name('roles.permissions.assign.store');

    // permission (created by PermissionSeeder only)
    Route::get('permissions', [PermissionController::class, 'list'])->middleware('can:permissions.view')->name('permissions.list');
    Route::get('permissions/edit/{id}', [PermissionController::class, 'edit'])->middleware('can:permissions.edit')->name('permissions.edit');
    Route::put('permissions/update/{id}', [PermissionController::class, 'update'])->middleware('can:permissions.edit')->name('permissions.update');

    // user
    Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
        Route::get('list', [UserController::class, 'list'])->middleware('can:users.view')->name('list');
        Route::get('create', [UserController::class, 'create'])->middleware('can:users.create')->name('create');
        Route::post('store', [UserController::class, 'store'])->middleware('can:users.create')->name('store');
        Route::get('edit/{id}', [UserController::class, 'edit'])->middleware('can:users.edit')->name('edit');
        Route::put('update/{id}', [UserController::class, 'update'])->middleware('can:users.edit')->name('update');
        Route::patch('status/{id}', [UserController::class, 'toggleStatus'])->middleware('can:users.status')->name('status');
        Route::delete('delete/{id}', [UserController::class, 'delete'])->middleware('can:users.delete')->name('delete');
    });

    // department
    Route::get('departments', [DepartmentController::class, 'list'])->middleware('can:departments.view')->name('departments.list');
    Route::get('departments/create', [DepartmentController::class, 'create'])->middleware('can:departments.create')->name('departments.create');
    Route::post('departments/store', [DepartmentController::class, 'store'])->middleware('can:departments.create')->name('departments.store');
    Route::get('departments/edit/{id}', [DepartmentController::class, 'edit'])->middleware('can:departments.edit')->name('departments.edit');
    Route::put('departments/update/{id}', [DepartmentController::class, 'update'])->middleware('can:departments.edit')->name('departments.update');
    Route::delete('departments/delete/{id}', [DepartmentController::class, 'delete'])->middleware('can:departments.delete')->name('departments.delete');

    // designation
    Route::prefix('designations')->name('designations.')->group(function () {
        Route::get('list', [DesignationController::class, 'list'])->middleware('can:designations.view')->name('list');
        Route::get('create', [DesignationController::class, 'create'])->middleware('can:designations.create')->name('create');
        Route::post('store', [DesignationController::class, 'store'])->middleware('can:designations.create')->name('store');
        Route::get('edit/{id}', [DesignationController::class, 'edit'])->middleware('can:designations.edit')->name('edit');
        Route::put('update/{id}', [DesignationController::class, 'update'])->middleware('can:designations.edit')->name('update');
        Route::delete('delete/{id}', [DesignationController::class, 'delete'])->middleware('can:designations.delete')->name('delete');
    });

    // location
    Route::group(['prefix' => 'locations', 'as' => 'locations.'], function () {
        Route::get('list', [LocationController::class, 'list'])->middleware('can:locations.view')->name('list');
        Route::get('create', [LocationController::class, 'create'])->middleware('can:locations.create')->name('create');
        Route::post('store', [LocationController::class, 'store'])->middleware('can:locations.create')->name('store');
        Route::post('import/apply', [LocationController::class, 'applyImport'])->middleware('can:locations.create')->name('import.apply');
        Route::post('change-status', [LocationController::class, 'changeStatus'])->middleware('can:locations.status')->name('changeStatus');
    });

    Route::group(['prefix' => 'areas', 'as' => 'areas.'], function () {
        Route::get('list', [AreaController::class, 'index'])->middleware('can:areas.view')->name('list');
        Route::get('create', [AreaController::class, 'create'])->middleware('can:areas.create')->name('create');
        Route::post('store', [AreaController::class, 'store'])->middleware('can:areas.create')->name('store');
        Route::get('edit/{area}', [AreaController::class, 'edit'])->middleware('can:areas.edit')->name('edit');
        Route::put('update/{area}', [AreaController::class, 'update'])->middleware('can:areas.edit')->name('update');
        Route::delete('delete/{area}', [AreaController::class, 'destroy'])->middleware('can:areas.delete')->name('delete');
        Route::patch('status/{area}', [AreaController::class, 'toggleStatus'])->middleware('can:areas.edit')->name('status');
        Route::get('import', [AreaController::class, 'import'])->middleware('can:areas.import')->name('import');
        Route::post('import/preview', [AreaController::class, 'preview'])->middleware('can:areas.import')->name('import.preview');
        Route::post('import/apply', [AreaController::class, 'applyImport'])->middleware('can:areas.import')->name('import.apply');
    });

    Route::group(['prefix' => 'stock', 'as' => 'stock.'], function () {
        Route::get('levels', [StockLevelController::class, 'index'])->middleware('can:stock.view')->name('levels');
        Route::get('movements', [StockLevelController::class, 'movements'])->middleware('can:stock.movements.view')->name('movements');
        Route::get('opening', [StockLevelController::class, 'opening'])->middleware('can:stock.opening')->name('opening');
        Route::post('opening', [StockLevelController::class, 'storeOpening'])->middleware('can:stock.opening')->name('opening.store');
        Route::get('requests', [OpeningStockRequestController::class, 'index'])->middleware('permission:stock.opening|stock.opening.approve')->name('requests.index');
        Route::get('requests/{openingStockRequest}', [OpeningStockRequestController::class, 'show'])->middleware('permission:stock.opening|stock.opening.approve')->name('requests.show');
        Route::post('requests/{openingStockRequest}/approve', [OpeningStockRequestController::class, 'approve'])->middleware('can:stock.opening.approve')->name('requests.approve');
        Route::post('requests/{openingStockRequest}/reject', [OpeningStockRequestController::class, 'reject'])->middleware('can:stock.opening.approve')->name('requests.reject');
        Route::post('requests/{openingStockRequest}/cancel', [OpeningStockRequestController::class, 'cancel'])->middleware('can:stock.opening')->name('requests.cancel');
        Route::post('requests/{openingStockRequest}/resubmit', [OpeningStockRequestController::class, 'resubmit'])->middleware('can:stock.opening')->name('requests.resubmit');
        Route::get('alerts', [StockAlertController::class, 'index'])->middleware('can:stock.alerts.view')->name('alerts');
        Route::get('units/labels', [StockUnitController::class, 'labels'])->middleware('can:stock.view')->name('units.labels');
        Route::get('units/lookup', [StockUnitController::class, 'lookup'])->middleware('can:stock.view')->name('units.lookup');
        Route::post('levels/{stockLevel}/minimum', [StockLevelController::class, 'setMinimum'])->middleware('can:stock.thresholds')->name('levels.minimum');
    });

    // transfers (warehouse -> supplied branch) and returns (branch -> warehouse)
    Route::group(['prefix' => 'transfers', 'as' => 'transfers.'], function () {
        Route::get('/', [TransferController::class, 'index'])->middleware('can:transfers.view')->name('index');
        Route::get('create', [TransferController::class, 'create'])->middleware('can:transfers.create')->name('create');
        Route::post('store', [TransferController::class, 'store'])->middleware('can:transfers.create')->name('store');
        Route::get('backorders', [TransferController::class, 'backorders'])->middleware('can:transfers.view')->name('backorders');
        Route::post('discrepancies/{transferDiscrepancy}/resolve', [TransferController::class, 'resolveDiscrepancy'])
            ->middleware('can:stock.discrepancies.resolve')->name('discrepancies.resolve');
        Route::get('{transfer}', [TransferController::class, 'show'])->middleware('can:transfers.view')->name('show');
        Route::get('{transfer}/delivery-note', [TransferController::class, 'deliveryNote'])->middleware('can:transfers.view')->name('delivery-note');
        Route::post('{transfer}/send', [TransferController::class, 'send'])->middleware('can:transfers.send')->name('send');
        Route::post('{transfer}/receive', [TransferController::class, 'receive'])->middleware('can:transfers.receive')->name('receive');
        Route::post('{transfer}/cancel', [TransferController::class, 'cancel'])->middleware('can:transfers.cancel')->name('cancel');
    });

    // stock adjustments (maker-checker)
    Route::group(['prefix' => 'stock/adjustments', 'as' => 'adjustments.'], function () {
        Route::get('/', [AdjustmentController::class, 'index'])->middleware('can:adjustments.view')->name('index');
        Route::get('create', [AdjustmentController::class, 'create'])->middleware('can:adjustments.create')->name('create');
        Route::post('store', [AdjustmentController::class, 'store'])->middleware('can:adjustments.create')->name('store');
        Route::get('{stockAdjustment}', [AdjustmentController::class, 'show'])->middleware('can:adjustments.view')->name('show');
        Route::post('{stockAdjustment}/approve', [AdjustmentController::class, 'approve'])->middleware('can:adjustments.approve')->name('approve');
        Route::post('{stockAdjustment}/reject', [AdjustmentController::class, 'reject'])->middleware('can:adjustments.approve')->name('reject');
        Route::post('{stockAdjustment}/cancel', [AdjustmentController::class, 'cancel'])->middleware('can:adjustments.create')->name('cancel');
    });

    // stock counts
    Route::group(['prefix' => 'stock/counts', 'as' => 'counts.'], function () {
        Route::get('/', [StockCountController::class, 'index'])->middleware('can:counts.view')->name('index');
        Route::get('create', [StockCountController::class, 'create'])->middleware('can:counts.create')->name('create');
        Route::post('store', [StockCountController::class, 'store'])->middleware('can:counts.create')->name('store');
        Route::get('{stockCount}', [StockCountController::class, 'show'])->middleware('can:counts.view')->name('show');
        Route::get('{stockCount}/print', [StockCountController::class, 'print'])->middleware('can:counts.view')->name('print');
        Route::post('{stockCount}/submit', [StockCountController::class, 'submit'])->middleware('can:counts.create')->name('submit');
        Route::post('{stockCount}/approve', [StockCountController::class, 'approve'])->middleware('can:counts.approve')->name('approve');
        Route::post('{stockCount}/acknowledge-drift', [StockCountController::class, 'acknowledgeDrift'])->middleware('can:counts.create')->name('acknowledge-drift');
        Route::post('{stockCount}/cancel', [StockCountController::class, 'cancel'])->middleware('can:counts.create')->name('cancel');
    });

    // reports and stock KPIs
    Route::group(['prefix' => 'reports', 'as' => 'reports.'], function () {
        Route::get('/', [ReportController::class, 'index'])->middleware('can:reports.view')->name('index');
        Route::get('valuation', [ReportController::class, 'valuation'])->middleware('can:reports.view')->name('valuation');
        Route::get('low-stock', [ReportController::class, 'lowStock'])->middleware('can:reports.view')->name('low-stock');
        Route::get('movements', [ReportController::class, 'movements'])->middleware('can:reports.view')->name('movements');
        Route::get('transfers', [ReportController::class, 'transfers'])->middleware('can:reports.view')->name('transfers');
        Route::get('discrepancies', [ReportController::class, 'discrepancies'])->middleware('can:reports.view')->name('discrepancies');
        Route::get('purchases', [ReportController::class, 'purchases'])->middleware('can:reports.view')->name('purchases');
        Route::get('adjustments', [ReportController::class, 'adjustments'])->middleware('can:reports.view')->name('adjustments');
        Route::get('backorders', [ReportController::class, 'backorders'])->middleware('can:reports.view')->name('backorders');
        Route::get('stock-on-hand', [ReportController::class, 'stockOnHand'])->middleware('can:reports.view')->name('stock-on-hand');
    });
    Route::get('stock/kpi', [ReportController::class, 'kpi'])->middleware('can:stock.kpi.view')->name('stock.kpi');

    // suppliers, purchase orders and goods receipts
    Route::group(['prefix' => 'suppliers', 'as' => 'suppliers.'], function () {
        Route::get('/', [SupplierController::class, 'index'])->middleware('can:suppliers.view')->name('index');
        Route::get('create', [SupplierController::class, 'create'])->middleware('can:suppliers.create')->name('create');
        Route::post('store', [SupplierController::class, 'store'])->middleware('can:suppliers.create')->name('store');
        Route::get('{supplier}/edit', [SupplierController::class, 'edit'])->middleware('can:suppliers.edit')->name('edit');
        Route::put('{supplier}', [SupplierController::class, 'update'])->middleware('can:suppliers.edit')->name('update');
        Route::delete('{supplier}', [SupplierController::class, 'destroy'])->middleware('can:suppliers.delete')->name('delete');
    });

    Route::group(['prefix' => 'purchases', 'as' => 'purchases.'], function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->middleware('can:purchases.view')->name('index');
        Route::get('create', [PurchaseOrderController::class, 'create'])->middleware('can:purchases.create')->name('create');
        Route::post('store', [PurchaseOrderController::class, 'store'])->middleware('can:purchases.create')->name('store');
        Route::get('{purchaseOrder}', [PurchaseOrderController::class, 'show'])->middleware('can:purchases.view')->name('show');
        Route::get('{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->middleware('can:purchases.edit')->name('edit');
        Route::put('{purchaseOrder}', [PurchaseOrderController::class, 'update'])->middleware('can:purchases.edit')->name('update');
        Route::post('{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->middleware('can:purchases.approve')->name('approve');
        Route::post('{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('can:purchases.cancel')->name('cancel');
        Route::get('{purchaseOrder}/print', [PurchaseOrderController::class, 'print'])->middleware('can:purchases.view')->name('print');
    });

    Route::group(['prefix' => 'receiving', 'as' => 'receiving.'], function () {
        Route::get('/', [GoodsReceiptController::class, 'index'])->middleware('can:receiving.view')->name('index');
        Route::get('create', [GoodsReceiptController::class, 'create'])->middleware('can:receiving.create')->name('create');
        Route::post('store', [GoodsReceiptController::class, 'store'])->middleware('can:receiving.create')->name('store');
        Route::get('{goodsReceipt}', [GoodsReceiptController::class, 'show'])->middleware('can:receiving.view')->name('show');
        Route::post('{goodsReceipt}/post', [GoodsReceiptController::class, 'post'])->middleware('can:receiving.create')->name('post');
        Route::post('{goodsReceipt}/supplier-return', [GoodsReceiptController::class, 'supplierReturn'])->middleware('can:receiving.create')->name('supplier-return');
        Route::get('{goodsReceipt}/print', [GoodsReceiptController::class, 'print'])->middleware('can:receiving.view')->name('print');
    });

    // category
    Route::group(['prefix' => 'categories', 'as' => 'categories.'], function () {
        Route::get('list', [CategoryController::class, 'list'])->middleware('can:categories.view')->name('list');
        Route::get('create', [CategoryController::class, 'create'])->middleware('can:categories.create')->name('create');
        Route::post('store', [CategoryController::class, 'store'])->middleware('can:categories.create')->name('store');
        Route::get('edit/{category}', [CategoryController::class, 'edit'])->middleware('can:categories.edit')->name('edit');
        Route::put('update/{category}', [CategoryController::class, 'update'])->middleware('can:categories.edit')->name('update');
        Route::delete('delete/{category}', [CategoryController::class, 'delete'])->middleware('can:categories.delete')->name('delete');
        Route::post('generate-slug', [CategoryController::class, 'generateSlug'])->middleware('can:categories.create')->name('generate-slug');
    });

    // brand
    Route::group(['prefix' => 'brands', 'as' => 'brands.'], function () {
        Route::get('list', [BrandController::class, 'list'])->middleware('can:brands.view')->name('list');
        Route::get('create', [BrandController::class, 'create'])->middleware('can:brands.create')->name('create');
        Route::post('store', [BrandController::class, 'store'])->middleware('can:brands.create')->name('store');
        Route::get('edit/{brand}', [BrandController::class, 'edit'])->middleware('can:brands.edit')->name('edit');
        Route::put('update/{brand}', [BrandController::class, 'update'])->middleware('can:brands.edit')->name('update');
        Route::delete('delete/{brand}', [BrandController::class, 'delete'])->middleware('can:brands.delete')->name('delete');
        Route::post('generate-slug', [BrandController::class, 'generateSlug'])->middleware('can:brands.create')->name('generate-slug');
    });

    // product
    Route::group(['prefix' => 'products', 'as' => 'products.'], function () {
        Route::get('list', [ProductController::class, 'list'])->middleware('can:products.view')->name('list');
        Route::get('create', [ProductController::class, 'create'])->middleware('can:products.create')->name('create');
        Route::post('store', [ProductController::class, 'store'])->middleware('can:products.create')->name('store');
        Route::get('edit/{product}', [ProductController::class, 'edit'])->middleware('can:products.edit')->name('edit');
        Route::put('update/{product}', [ProductController::class, 'update'])->middleware('can:products.edit')->name('update');
        Route::delete('delete/{product}', [ProductController::class, 'delete'])->middleware('can:products.delete')->name('delete');
        Route::get('{product}/barcode', [ProductController::class, 'barcode'])->middleware('can:products.view')->name('barcode');
        Route::post('barcode/bulk', [ProductController::class, 'bulkBarcode'])->middleware('can:products.view')->name('barcode.bulk');
    });

    // warehouse
    Route::group(['prefix' => 'warehouse', 'as' => 'warehouse.'], function () {
        Route::get('list', [WarehouseController::class, 'list'])->middleware('can:warehouses.view')->name('list');
        Route::get('create', [WarehouseController::class, 'create'])->middleware('can:warehouses.create')->name('create');
        Route::post('store', [WarehouseController::class, 'store'])->middleware('can:warehouses.create')->name('store');
        Route::get('edit/{id}', [WarehouseController::class, 'edit'])->middleware('can:warehouses.edit')->name('edit');
        Route::put('update/{id}', [WarehouseController::class, 'update'])->middleware('can:warehouses.edit')->name('update');
        Route::delete('delete/{id}', [WarehouseController::class, 'delete'])->middleware('can:warehouses.delete')->name('delete');

        // location data
        Route::get('get-states/{countryId}', [WarehouseController::class, 'getStates'])->middleware('permission:warehouses.create|warehouses.edit');
        Route::get('get-cities/{stateId}', [WarehouseController::class, 'getCities'])->middleware('permission:warehouses.create|warehouses.edit');
        Route::get('get-upazilas/{cityId}', [WarehouseController::class, 'getUpazilas'])->middleware('permission:warehouses.create|warehouses.edit');
        Route::get('get-zipcode/{upazilaId}', [WarehouseController::class, 'getZipcode'])->middleware('permission:warehouses.create|warehouses.edit');
        Route::get('get-areas/{cityId}', [WarehouseController::class, 'getAreas'])->middleware('permission:warehouses.create|warehouses.edit');
    });

    // branch
    Route::group(['prefix' => 'branch', 'as' => 'branch.'], function () {
        Route::get('list', [BranchController::class, 'list'])->middleware('can:branches.view')->name('list');
        Route::get('create', [BranchController::class, 'create'])->middleware('can:branches.create')->name('create');
        Route::post('store', [BranchController::class, 'store'])->middleware('can:branches.create')->name('store');
        Route::get('edit/{id}', [BranchController::class, 'edit'])->middleware('can:branches.edit')->name('edit');
        Route::put('update/{id}', [BranchController::class, 'update'])->middleware('can:branches.edit')->name('update');
        Route::delete('delete/{id}', [BranchController::class, 'delete'])->middleware('can:branches.delete')->name('delete');

        // location data
        Route::get('get-states/{countryId}', [BranchController::class, 'getStates'])->middleware('permission:branches.create|branches.edit');
        Route::get('get-cities/{stateId}', [BranchController::class, 'getCities'])->middleware('permission:branches.create|branches.edit');
        Route::get('get-upazilas/{cityId}', [BranchController::class, 'getUpazilas'])->middleware('permission:branches.create|branches.edit');
        Route::get('get-zipcode/{upazilaId}', [BranchController::class, 'getZipcode'])->middleware('permission:branches.create|branches.edit');
        Route::get('get-areas/{cityId}', [BranchController::class, 'getAreas'])->middleware('permission:branches.create|branches.edit');
    });
});
