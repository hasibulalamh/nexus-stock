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
use App\Http\Controllers\Branch\BranchController;
use App\Http\Controllers\Warehouse\WarehouseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'admin.dashboard' : 'login');
});

Route::get('login', [LoginController::class, 'view'])->name('login');
Route::post('login', [LoginController::class, 'login'])->name('login.submit');
Route::post('logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');



Route::group(['middleware' => ['auth', 'active'], 'prefix' => 'admin', 'as' => 'admin.'], function () {
//logout

// dashboard
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

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
        Route::post('change-status', [LocationController::class, 'changeStatus'])->middleware('can:locations.status')->name('changeStatus');
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
        Route::get('get-states/{countryId}', [WarehouseController::class, 'getStates'])->middleware('can:warehouses.create|warehouses.edit');
        Route::get('get-cities/{stateId}', [WarehouseController::class, 'getCities'])->middleware('can:warehouses.create|warehouses.edit');
        Route::get('get-upazilas/{cityId}', [WarehouseController::class, 'getUpazilas'])->middleware('can:warehouses.create|warehouses.edit');
        Route::get('get-zipcode/{upazilaId}', [WarehouseController::class, 'getZipcode'])->middleware('can:warehouses.create|warehouses.edit');
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
        Route::get('get-states/{countryId}', [BranchController::class, 'getStates'])->middleware('can:branches.create|branches.edit');
        Route::get('get-cities/{stateId}', [BranchController::class, 'getCities'])->middleware('can:branches.create|branches.edit');
        Route::get('get-upazilas/{cityId}', [BranchController::class, 'getUpazilas'])->middleware('can:branches.create|branches.edit');
        Route::get('get-zipcode/{upazilaId}', [BranchController::class, 'getZipcode'])->middleware('can:branches.create|branches.edit');
    });
});
