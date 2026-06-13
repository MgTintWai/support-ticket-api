<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::get('/health', fn () => response()->json([
        'success' => true,
        'message' => 'Service is healthy.',
        'data' => ['status' => 'ok'],
    ]));

    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/users/agents', [UserController::class, 'agents']);

        Route::apiResource('organizations', OrganizationController::class);

        Route::apiResource('tickets', TicketController::class);
        Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
        Route::patch('/tickets/{ticket}/assign', [TicketController::class, 'assign']);

        Route::get('/tickets/{ticket}/comments', [CommentController::class, 'index']);
        Route::post('/tickets/{ticket}/comments', [CommentController::class, 'store']);
    });
});
