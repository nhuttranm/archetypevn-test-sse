<?php

use App\Http\Controllers\Api\ApprovalRuleController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

/* |-------------------------------------------------------------------------- | API Routes |-------------------------------------------------------------------------- */

// Public routes
Route::post('/login', [AuthController::class , 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class , 'logout']);
    Route::get('/user', [AuthController::class , 'user']);

    // Lookups
    Route::get('/departments', [LookupController::class , 'departments']);
    Route::get('/vendors', [LookupController::class , 'vendors']);

    // Dashboard
    Route::get('/purchase-orders/dashboard', [PurchaseOrderController::class , 'dashboard']);

    // Purchase Orders CRUD
    Route::apiResource('purchase-orders', PurchaseOrderController::class);

    // Purchase Order Actions
    Route::post('/purchase-orders/{purchase_order}/submit', [PurchaseOrderController::class , 'submit']);
    Route::post('/purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class , 'approve']);
    Route::post('/purchase-orders/{purchase_order}/reject', [PurchaseOrderController::class , 'reject']);
    Route::post('/purchase-orders/{purchase_order}/revise', [PurchaseOrderController::class , 'revise']);

    // Audit Logs
    Route::get('/audit-logs', [AuditLogController::class , 'index']);
    Route::get('/audit-logs/{purchaseOrder}', [AuditLogController::class , 'show']);

    // Approval Rules (Workflow Config)
    Route::get('/approval-rules/chain', [ApprovalRuleController::class , 'chain']);
    Route::apiResource('approval-rules', ApprovalRuleController::class);
});
