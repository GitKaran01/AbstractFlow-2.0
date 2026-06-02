<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AbstractorController;
use App\Http\Controllers\Api\DropdownSyncController;
use App\Http\Controllers\Api\ActivityApiController;

// ==========================================
// PUBLIC & GENERAL METADATA API ROUTES
// ==========================================

// 🔥 Dropdown Sync Matrix Endpoints for Flutter Mobile Team
Route::get('/meta/clients', [DropdownSyncController::class, 'getClients']);
Route::get('/meta/products', [DropdownSyncController::class, 'getProducts']);

// 🔥 FIXED MAP: Points directly to verified functional logic inside AdminController
Route::get('/admin/users', [AdminController::class, 'syncAdminUsersBoard']);
Route::get('/meta/abstractors', [AdminController::class, 'getAbstractors']); // Manual intake drops here cleanly

// Master monitoring table layout
Route::get('/admin/tickets', [AdminController::class, 'syncAdminMonitoringPanel']);

// Public User Authentication
Route::post('/login', [AuthController::class, 'login']);


// ==========================================
// WEB BROWSER COMPATIBLE ADMIN ROUTES 
// ==========================================
Route::prefix('admin')->group(function () {
    Route::post('/users/create', [AdminController::class, 'createUser']);
    Route::post('/tickets/create', [AdminController::class, 'createTicket']);
    Route::post('/tickets/{id}/reassign', [AdminController::class, 'reassignTicket']);
    Route::post('/tickets/{id}/review', [AdminController::class, 'reviewTicket']);
});


// ==========================================
// PROTECTED MOBILE API ROUTES (Requires Sanctum Token)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout']);

    // 🔥 FIXED & MERGED: Abstractor Mobile Workspaces & Activities Engine
    Route::prefix('abstractor')->group(function () {
        Route::get('/my-tasks', [AbstractorController::class, 'getMyTasks']);
        Route::post('/tasks/{id}/update-status', [AbstractorController::class, 'updateStatus']);
        Route::post('/tasks/{id}/escalate', [AbstractorController::class, 'escalateTask']);
        Route::post('/tasks/{id}/submit', [AbstractorController::class, 'submitFinalReport']); 
        
        // 👥 New Activity Framework Endpoints (Using unique dynamic parameter to avoid conflict)
        Route::get('/tasks/{ticket_id}/activities', [ActivityApiController::class, 'getTicketActivities']);
        Route::post('/tasks/{ticket_id}/activity-comment', [ActivityApiController::class, 'postActivityComment']); // 👈 CHANGED url endpoint here to avoid clash
    });

    // 👑 Admin Management Audit Panel Group
    Route::prefix('admin')->group(function () {
        // Global system tracking logs for system monitoring view
        Route::get('/global-logs', [ActivityApiController::class, 'getGlobalAdminLogs']);
        Route::get('/tickets/{id}/stream-pdf', [AdminController::class, 'streamTicketPdf']);
    });
});