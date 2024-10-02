<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Http\Controllers\Authentication\AuthController;
use App\Http\Controllers\Authentication\DeviceController;
use App\Http\Controllers\Authentication\EmailVerificationController;
use App\Http\Controllers\Authentication\ImpersonateController;
use App\Http\Controllers\Authentication\OAuthController;
use App\Http\Controllers\Authentication\PasswordController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'oauth/{provider}',
    'as' => 'oauth.provider.',
],
    static function (): void {
        Route::get('redirect', [OAuthController::class, 'redirect'])->name('redirect');
        Route::get('callback', [OAuthController::class, 'callback'])->name('callback');
        Route::get('de-auth', [OAuthController::class, 'deAuth'])->name('de-auth');
    }
);
Route::get('/oauth/deletion-status', [OAuthController::class, 'deletionStatus'])->name('oauth.deletion-status');

Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('register', [AuthController::class, 'register'])->name('register');
Route::post('password/forgot', [PasswordController::class, 'sendResetPasswordLink'])->middleware('throttle:5,1')->name('password.email');
Route::post('password/reset', [PasswordController::class, 'resetPassword'])->name('password.store');
Route::post('verification-notification', [EmailVerificationController::class, 'verificationNotification'])
    ->middleware('throttle:verification-notification')
    ->name('verification.send');
Route::get('verify-email/{user}', [EmailVerificationController::class, 'verifyEmail'])
    ->middleware(['throttle:6,1'])
    ->name('email.verify');

Route::middleware([
    'auth:sanctum',
    Illuminate\Auth\Middleware\EnsureEmailIsVerified::class
])->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout')
        ->withoutMiddleware(Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);
    Route::delete('devices/disconnect', [DeviceController::class, 'deviceDisconnect'])->name('devices.disconnect');
    Route::get('devices', [DeviceController::class, 'devices'])->name('devices');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::patch('notifications/{notification}/mark-unread', [NotificationController::class, 'markAsUnread'])->name('notifications.mark-unread');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

    Route::apiResource('user', UserController::class)->only(['index'])
        ->withoutMiddleware(Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);
    Route::patch('user', [UserController::class, 'update'])->name('user.update');
    Route::post('user/avatar', [UserController::class, 'updateAvatar'])->name('user.avatar');
    Route::post('password', [PasswordController::class, 'password'])->name('settings.password');

    Route::group(['prefix' => 's3/multipart', 'as' => 'uploads.'], static function (): void {
        Route::post('/', [UploadController::class, 'createUpload'])->name('create');
        Route::get('/{uploadId}', [UploadController::class, 'getUploadedParts'])->name('show');
        Route::get('/{uploadId}/{partNumber}', [UploadController::class, 'signPart'])
            ->middleware('throttle:400,1')
            ->name('sign');
        Route::post('/{uploadId}/complete', [UploadController::class, 'completeUpload'])->name('complete');
        Route::delete('/{uploadId}', [UploadController::class, 'cancelUpload'])->name('cancel');
    });

    Route::group(['middleware' => ['role:' . Role::Admin->value]], static function (): void {
        Route::post('impersonate/{user}', [ImpersonateController::class, 'impersonate'])->name('impersonate');

        Route::apiResource('images', ImageController::class)->only(['index', 'update', 'destroy', 'store']);
        Route::get('users', UsersController::class)->name('users');
    });

    Route::delete('impersonate', [ImpersonateController::class, 'stopImpersonating'])->name('impersonate.stop');
});
