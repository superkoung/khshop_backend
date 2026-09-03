<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Product_variantController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
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
            Route::patch('/avatar','updateAvatar');
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
        Route::controller(ProductController::class)->prefix('product')->group(function(){
            // api frontend
            Route::get('/filter','getFilter');

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
            Route::get('/','index');
            Route::post('/','store');
            Route::patch('/{id}','update');
            Route::delete('/{id}','delete');
            Route::delete('/','clear');
        });
    });
});



