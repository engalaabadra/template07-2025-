<?php

use App\Http\Controllers\Auth\User\LoginController;
use App\Http\Controllers\Auth\User\RegisterController;
use App\Http\Controllers\Auth\User\RecoveryPasswordController;

use App\Http\Controllers\LanguageController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\BannerController;
use App\Http\Controllers\User\BoardController;
use App\Http\Controllers\User\ChatController;
use App\Http\Controllers\User\ContactController;
use App\Http\Controllers\User\FavoriteController;
use App\Http\Controllers\User\ReviewController;
use App\Http\Controllers\User\OrderController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\User\Geocode\CountryController;

/**
 * API Routes for User and Authentication.
 *
 * Defines endpoints for user registration, login, password recovery,
 * profile management, and other related resources.
 */

// Banners resource with only index method
Route::resource('banners', BannerController::class)->only(['index']);

// Boards resource with only index method
Route::resource('boards', BoardController::class)->only(['index']);

// Login route
Route::post('/login', [LoginController::class, 'login'])->name('login');

// Registration routes grouped under /register prefix
Route::prefix('register')->group(function () {
    Route::post('/', [RegisterController::class, 'register'])->name('register');

    // Registration operations
    Route::post('/check-code', [RegisterController::class, 'checkCodeRegister'])->name('check-code-register');
    Route::get('/resend-code', [RegisterController::class, 'resendCodeRegister'])->name('resend-code-register');
});

// Password recovery routes grouped under /recovery-by-password prefix
Route::prefix('recovery-by-password')->group(function () {
    Route::post('forgot-password', [RecoveryPasswordController::class, 'forgotPassword'])->name('forgot-password');

    // Recovery operations
    Route::post('check-code', [RecoveryPasswordController::class, 'checkCode'])->name('check-code-pass');
    Route::get('resend-code', [RecoveryPasswordController::class, 'resendCode'])->name('resend-code-pass');
    Route::post('reset-password', [RecoveryPasswordController::class, 'resetPassword'])->name('reset-password');
});

// Language routes grouped under /lang prefix with alias 'lang'
Route::prefix('lang')->as('lang')->group(function () {
    Route::get('switch/{lang}', [LanguageController::class, 'switchLang'])->name('switch');
    Route::get('all', [LanguageController::class, 'getAllLangs'])->name('all');
    Route::get('default', [LanguageController::class, 'defaultLang'])->name('default');
});

// Routes protected by 'auth:api' and 'role:user' middleware
Route::middleware(['auth:api', 'role:user'])->group(function () {

    // Profile related routes grouped under /profile prefix with alias 'profile.'
    Route::prefix('profile')->as('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('update-password', [ProfileController::class, 'updatePassword'])->name('update-password');

        // File upload routes under /profile/file prefix
        Route::prefix('/file')->group(function () {
            Route::post('/', [ProfileController::class, 'uploadFile'])->name('upload-file');
            Route::delete('/', [ProfileController::class, 'deleteFile'])->name('delete-file');
        });

        // Multiple files upload routes under /profile/files prefix
        Route::prefix('/files')->group(function () {
            Route::post('/', [ProfileController::class, 'uploadFiles'])->name('upload-files');
            Route::delete('/', [ProfileController::class, 'deleteFiles'])->name('delete-files');
        });
    });

    // Orders resource routes with both resource and customResource methods
    Route::resource('orders', OrderController::class);
    Route::customResource('orders', OrderController::class);

    // Reviews resource routes with both resource and customResource methods
    Route::resource('reviews', ReviewController::class);
    Route::customResource('reviews', ReviewController::class);

    // chats resource routes with both resource and customResource methods
    Route::resource('chats', ChatController::class);
    Route::customResource('chats', ChatController::class);
     Route::resource('countries', CountryController::class)->only(['index']);


    // Route::resource('banners', BannerController::class)->only(['index']);
    // Route::resource('boards', BoardController::class)->only(['index']);
    // Route::fullResource('chats', ChatController::class)->except(['show']);
    // Route::get('/', [NotificationController::class, 'index']);
    Route::resource('favorites', FavoriteController::class)->only(['index', 'store']);
    // Route::customizedResource('contacts', ContactController::class);

    // Logout route
    Route::delete('/logout', [LoginController::class, 'destroy']);

});
