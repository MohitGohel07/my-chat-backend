<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CallController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\MediaController;
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
    Route::post('users/sync-contacts', [UserController::class, 'syncContacts']);
    Route::apiResource('conversations', ConversationController::class)->only(['index', 'store', 'show']);
    Route::patch('conversations/{conversation}/disappearing', [ConversationController::class, 'updateDisappearing']);
    Route::patch('conversations/{conversation}/lock', [ConversationController::class, 'lock']);
    Route::get('conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::delete('conversations/{conversation}/messages', [MessageController::class, 'destroy']);
    Route::post('conversations/{conversation}/messages/{message}/react', [MessageController::class, 'react']);
    Route::patch('conversations/{conversation}/messages/{message}/pin', [MessageController::class, 'pin']);
    Route::delete('conversations/{conversation}/messages/{message}', [MessageController::class, 'destroySingle']);
    Route::post('upload', [MediaController::class, 'upload']);
    Route::get('calls', [CallController::class, 'index']);
    Route::post('calls', [CallController::class, 'store']);
});
