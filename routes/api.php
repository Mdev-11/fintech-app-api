<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\VirtualCardController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\WalletController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/qrcode/resolve/{uuid}', [VirtualCardController::class, 'resolve'])->name('api.qrcode.resolve');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Wallet routes
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::post('/wallet/recharge', [WalletController::class, 'recharge']);
    Route::post('/wallet/transfer', [WalletController::class, 'transfer']);
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);

    // Transaction routes
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    

    // Virtual card routes
    Route::get('virtual-cards/', [VirtualCardController::class, 'show']); // Get current user's card
    Route::post('virtual-cards/deactivate', [VirtualCardController::class, 'deactivate']); // Deactivate
    Route::post('virtual-cards/regenerate', [VirtualCardController::class, 'regenerate']); // Regenerate UUID

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::post('/notifications/device-token', [NotificationController::class, 'updateDeviceToken']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

}); 