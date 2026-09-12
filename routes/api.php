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
use App\Http\Controllers\UserController;
use App\Http\Controllers\wishlistController;
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
        });
        // *** Users ***
        Route::controller(UserController::class)->middleware('role:admin,superAdmin')->prefix('user')->group(function(){
            Route::get('/','index');
            Route::post('/','store');
            Route::get('/{id}','show');
            Route::patch('/{id}','update');
            Route::delete('/{id}','destroy');
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
    // Route::middleware('auth:sanctum')->group(function(){
            Route::controller(CategoryController::class)->prefix('category')->group(function(){
                //end point front end
                Route::get('/menu','getNavMenu');

                Route::get('/','index');
                Route::post('/','store');
                Route::get('/trash','trashed');
                Route::get('/{id}','show');
                Route::patch('/{id}','update');
                Route::delete('/{id}','destroy');
                Route::patch('/{id}/restore','restore');
                Route::delete('/{id}/force-delete','forceDelete');

            });
    // });
    // *** Brands
    // Route::middleware('auth:sanctum')->group(function(){
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
    // });
    // *** products
    // Route::middleware('auth:sanctum')->group(function(){

        Route::controller(ProductController::class)->prefix('product')->group(function () {
                Route::post('/filter', 'getFilter');
                Route::post('/list', 'index');

                Route::post('/', 'store');
                Route::get('/trash', 'trashed');
                Route::patch('/{id}/restore', 'restore');
                Route::delete('/{id}/force-delete', 'forceDelete');


                Route::get('/{slug}', 'show');

                Route::patch('/{id}', 'update');
                Route::delete('/{id}', 'destroy');
        });
    // });
    // *** product variants
    // Route::middleware('auth:sanctum')->group(function(){
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
    // });
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



