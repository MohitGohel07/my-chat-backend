<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
    Route::post('request-otp', [AuthController::class, 'requestOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
});

Route::get('config', ConfigController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', fn (\Illuminate\Http\Request $request) => $request->user());
    Route::match(['put', 'post', 'patch'], 'profile', [UserController::class, 'updateProfile']);
    Route::get('users', [UserController::class, 'index']);
    Route::apiResource('conversations', ConversationController::class)->only(['index', 'store', 'show']);
    Route::patch('conversations/{conversation}/disappearing', [ConversationController::class, 'updateDisappearing']);
    Route::get('conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::delete('conversations/{conversation}/messages', [MessageController::class, 'destroy']);
});
