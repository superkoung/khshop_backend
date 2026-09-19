<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Product_variantController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\SaleReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\wishlistController;
use App\Http\Controllers\SystemSettingController;
use Illuminate\Support\Facades\Route;

// |=======================|
// |     API Version 1     |
// |=======================|
Route::prefix('v1')->group(function(){
    // |=======================|
    // |     Authentication    |
    // |=======================|
    Route::prefix('auth')->group(function(){
        // *** auth public ***
        Route::controller(AuthController::class)->group(function(){
            Route::post('/register','register');
            Route::post('/login','login');
        });

        Route::controller(EmailVerificationController::class)->prefix('email')->group(function(){
            Route::post('/verify','verifyEmail');
            Route::post('/resend-otp','resendOtp');
        });

        Route::controller(PasswordResetController::class)->prefix('password')->group(function(){
            Route::post('/forgot','forgotPassword');
            Route::post('/verify-otp','verifyOtp');
            Route::post('/reset','resetPassword');
            Route::post('/resend-otp','resendOtp');
        });

        // *** auth protected ***
        Route::middleware('auth:sanctum')->group(function(){
            Route::post('/logout',[AuthController::class,'logout']);
        });
    });
    // |====================|
    // |     Users          |
    // |====================|

    // *** protected route
    Route::middleware('auth:sanctum')->group(function(){
        // *** Profile ***
        Route::controller(ProfileController::class)->prefix('profile')->group(function(){
            Route::get('/','show');
            Route::put('/','update');
            Route::delete('/','delete');
            Route::post('/avatar','updateAvatar');
            Route::delete('/avatar','deleteAvatar');
            Route::post('/change-password','changePassword');
        });
        // *** Users ***
        Route::controller(UserController::class)->middleware('role:admin,superAdmin')->prefix('user')->group(function(){
            Route::get('/','index');
            Route::post('/','store');
            Route::get('/{id}','show');
            Route::patch('/{id}','update');
            Route::delete('/{id}','destroy');
        });
        // *** Addresses ***
        Route::controller(UserAddressController::class)->prefix('addresses')->group(function(){
            Route::get('/','index');
            Route::post('/','store');
            Route::get('/{id}','show');
            Route::patch('/{id}','update');
            Route::delete('/{id}','destroy');
            Route::patch('/{id}/default','setDefault');
        });
    });
    // Home page
    Route::get('/home',[HomeController::class,'index']);
    // |====================|
    // |     Products       |
    // |====================|
    // *** categories
    // *** public route

    // *** protected route
    // *** Categories
    // Public: navigation menu
    Route::controller(CategoryController::class)->prefix('category')->group(function(){
        Route::get('/menu','getNavMenu');
    });
    // Protected: CRUD
    Route::middleware('auth:sanctum')->group(function(){
        Route::controller(CategoryController::class)->prefix('category')->group(function(){
            Route::get('/','index');
            Route::post('/','store');
            Route::get('/trash','trashed');
            Route::get('/{id}','show');
            Route::patch('/{id}','update');
            Route::delete('/{id}','destroy');
            Route::patch('/{id}/restore','restore');
            Route::delete('/{id}/force-delete','forceDelete');
        });
    });
    // *** Brands
    Route::middleware('auth:sanctum')->group(function(){
        Route::controller(BrandController::class)->prefix('brand')->group(function(){
            Route::get('/','index');
            Route::post('/','store');
            Route::get('/trash','trashed');
            Route::get('/{brand}','show');
            Route::patch('/{brand}','update');
            Route::delete('/{brand}','destroy');
            Route::patch('/{brand}/restore','restore');
            Route::delete('/{brand}/force-delete','forceDelete');
        });
    });
    // *** products
    Route::middleware('auth:sanctum')->group(function(){

        Route::controller(ProductController::class)->group(function () {
            Route::prefix('product')->group(function(){
                Route::post('/filter', 'getFilter');
                Route::post('/list', 'index');
                Route::get('/{slug}', 'show');
            });
            Route::prefix('admin/product')->middleware('role:admin,superAdmin,staff')->group(function(){
                Route::get('/colors', 'adminColors');
                Route::get('/sizes', 'adminSizes');
                Route::get('/brands', 'adminBrands');
                Route::get('/categories', 'adminCategories');
                Route::get('/trash', 'trashed');
                Route::get('/{id}','adminShow');
                Route::get('/','adminIndex');
                Route::post('/', 'adminStore');
                Route::patch('/{id}', 'adminUpdate');
                Route::delete('/{id}', 'adminDestroy');
                Route::patch('/{id}/restore', 'restore');
                Route::delete('/{id}/force-delete', 'forceDelete');

            });

        });
    });
    // *** product variants
    Route::middleware('auth:sanctum')->group(function(){
        Route::controller(Product_variantController::class)->prefix('variant')->group(function(){
            Route::get('/','index');
            Route::post('/','store');
            Route::get('/trash','trashed');
            Route::get('/{brand}','show');
            Route::patch('/{brand}','update');
            Route::delete('/{brand}','destroy');
            Route::patch('/{brand}/restore','restore');
            Route::delete('/{brand}/force-delete','forceDelete');
        });
    });

    // *** Suppliers
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(SupplierController::class)->middleware('role:admin,superAdmin')->prefix('admin/supplier')->group(function(){
            Route::get('/','index');
            Route::post('/','store');
            Route::get('/{id}','show');
            Route::patch('/{id}','update');
            Route::delete('/{id}','destroy');
        });
    });

    // *** Inventory
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(InventoryController::class)->middleware('role:admin,superAdmin,staff')->prefix('admin/inventory')->group(function(){
            Route::get('/','index');
            Route::get('/low-stock','getLowStock');
            Route::patch('/{id}/stock','updateStock');
        });
    });

    // *** Admin Orders
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(AdminOrderController::class)->middleware('role:admin,superAdmin,staff')->prefix('admin/order')->group(function(){
            Route::get('/','index');
            Route::get('/{id}','show');
            Route::patch('/{id}/status','updateStatus');
            Route::patch('/{id}/cancel','cancel');
        });
    });

    // *** Admin Customers
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(AdminCustomerController::class)->middleware('role:admin,superAdmin,staff')->prefix('admin/customer')->group(function(){
            Route::get('/','index');
            Route::get('/{id}','show');
            Route::patch('/{id}/status','updateStatus');
        });
    });

    // *** Admin Sale Reports
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(SaleReportController::class)->middleware('role:admin,superAdmin,staff')->prefix('admin/sale-report')->group(function(){
            Route::get('/data/{period}','getSalesData');
            Route::get('/chart/{period}','getSalesChart');
            Route::get('/top-products','getTopProducts');
            Route::get('/top-customers','getTopCustomers');
        });
    });

    // *** Admin Dashboard
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(DashboardController::class)->middleware('role:admin,superAdmin,staff')->prefix('admin/dashboard')->group(function(){
            Route::get('/','index');
        });
    });

    // *** Admin Banners
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(BannerController::class)->middleware('role:admin,superAdmin')->prefix('admin/banner')->group(function(){
            Route::get('/','index');
            Route::get('/{id}','show');
            Route::post('/','store');
            Route::patch('/{id}','update');
            Route::delete('/{id}','destroy');
            Route::patch('/{id}/status','updateStatus');
        });
    });

    // *** Admin Collections
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(CollectionController::class)->middleware('role:admin,superAdmin')->prefix('admin/collection')->group(function(){
            Route::get('/','index');
            Route::get('/{id}','show');
            Route::post('/','store');
            Route::patch('/{id}','update');
            Route::delete('/{id}','destroy');
            Route::patch('/{id}/status','updateStatus');
            Route::post('/{id}/products','assignProducts');
            Route::delete('/{id}/products/{productId}','removeProduct');
        });
    });

    // *** Admin Settings
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(SystemSettingController::class)->middleware('role:admin,superAdmin')->prefix('admin/settings')->group(function(){
            Route::get('/','index');
            Route::put('/','update');
        });
    });

    // *** Cart
    Route::middleware('auth:sanctum')->group(function(){
        Route::controller(CartController::class)->prefix('cart')->group(function(){
            Route::post('/merge','merge');

            Route::get('/','index');
            Route::post('/','store');
            Route::patch('/{id}','update');
            Route::delete('/{id}','destroy');
            Route::delete('/','clear');
        });
    });

    // *** Wishlist ***
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(wishlistController::class)->prefix('wishlist')->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::post('/merge', 'merge');
                Route::delete('/{id}', 'destroy');
                Route::delete('/', 'clear');
        });
    });
    // *** Order ***
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(OrderController::class)->prefix('order')->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::get('/{id}', 'show');
                Route::patch('/{id}/cancel', 'cancel');
            });
    });

    //review
    // Public
    Route::get('/products/{productId}/reviews',[ReviewController::class, 'index']);
    // Protected
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/products/{productId}/reviews',[ReviewController::class, 'store']);
        Route::patch('/reviews/{id}',[ReviewController::class, 'update']);
        Route::delete('/reviews/{id}',[ReviewController::class, 'destroy']);
    });
});



