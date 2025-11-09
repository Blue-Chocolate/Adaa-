<?php 


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrganizationController\OrganizationController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);


Route::apiResource('organizations', OrganizationController::class)->middleware('auth:sanctum');


use App\Http\Controllers\Api\ShieldAxisResponseController\ShieldAxisResponseController;

    Route::get('shield-axes', [ShieldAxisResponseController::class, 'getAxes']);
    Route::get('shield-axes/{axisId}', [ShieldAxisResponseController::class, 'getAxis']);  
// Protect these routes with auth middleware (e.g., sanctum)

Route::middleware('auth:sanctum')->group(function () {
    
    // Get all axes with questions
    Route::get('/axes', [ShieldAxisResponseController::class, 'getAxes']);
    
    // Get specific axis with questions
    Route::get('/axes/{axisId}', [ShieldAxisResponseController::class, 'getAxis']);
    
    // Organization routes
    Route::prefix('organizations/{orgId}')->group(function () {
        
        // Get organization's overall shield status
        Route::get('/shield-status', [ShieldAxisResponseController::class, 'getOverallStatus']);
        
        // Axis routes
        Route::prefix('axes/{axisId}')->group(function () {
            
            // Get organization's response for specific axis
            Route::get('/', [ShieldAxisResponseController::class, 'show']);
            
            // ✨ NEW: Save single answer instantly
            Route::post('/answer', [ShieldAxisResponseController::class, 'saveAnswer']);
            
            // ✨ NEW: Upload single attachment instantly
            Route::post('/attachment', [ShieldAxisResponseController::class, 'uploadAttachment']);
            
            // ✨ NEW: Delete attachment
            Route::delete('/attachment/{attachmentNumber}', [ShieldAxisResponseController::class, 'deleteAttachment']);
            
            // Legacy: Bulk save (keep for backward compatibility)
            Route::post('/', [ShieldAxisResponseController::class, 'storeOrUpdate']);
        });
    });
});
use App\Http\Controllers\Api\PodcastController\PodcastController;

Route::get('/podcasts', [PodcastController::class, 'index']);
Route::get('/podcasts/{id}', [PodcastController::class, 'show']);


use App\Http\Controllers\Api\ReleaseController\ReleaseController;

Route::prefix('releases')->group(function () {
    Route::get('/', [ReleaseController::class, 'index']);
    Route::get('/{id}', [ReleaseController::class, 'show']);
    Route::get('/{id}/download', [ReleaseController::class, 'download']);
});

use App\Http\Controllers\Api\BlogController\BlogController;


Route::prefix('blogs')->group(function () {
    Route::get('/', [BlogController::class, 'index']);
    Route::get('{id}', [BlogController::class, 'show']);
});