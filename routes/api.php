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












Route::middleware('auth:sanctum')->group(function () {
    // Get all axes
    Route::get('/axes', [ShieldAxisResponseController::class, 'getAxes']);
    
    // Get specific axis
    Route::get('/axes/{axisId}', [ShieldAxisResponseController::class, 'getAxis']);
    
    // Organization-specific routes
    Route::prefix('organizations/{orgId}')->group(function () {
        // Get axis response
        Route::get('/axes/{axisId}', [ShieldAxisResponseController::class, 'show']);
        
        // Save single answer
        Route::post('/axes/{axisId}/answer', [ShieldAxisResponseController::class, 'saveAnswer']);
        
        // Bulk save (legacy)
        Route::post('/axes/{axisId}', [ShieldAxisResponseController::class, 'storeOrUpdate']);
        
        // Attachment management
        Route::post('/axes/{axisId}/attachment', [ShieldAxisResponseController::class, 'uploadAttachment']);
        Route::delete('/axes/{axisId}/attachment/{number}', [ShieldAxisResponseController::class, 'deleteAttachment']);
        
        // Overall status
        Route::get('/shield/status', [ShieldAxisResponseController::class, 'getOverallStatus']);
    });
});


use App\Http\Controllers\Api\Shield\ShieldAnalyticsController;
use App\Http\Controllers\Api\Shield\ShieldOrganizationsController;
use App\Http\Controllers\Api\Shield\ShieldQuestionsController;
use App\Http\Controllers\Api\Shield\ShieldSubmissionController;
use App\Http\Controllers\Api\Shield\ShieldAttachmentController;
use App\Http\Controllers\Api\Shield\ShieldDownloadController;
// Public routes (no authentication required)
Route::prefix('shield')->group(function () {
    // Analytics
    Route::get('/analytics', [ShieldAnalyticsController::class, 'index']);
    
    // Organizations list
    Route::get('/organizations', [ShieldOrganizationsController::class, 'index']);
});

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->prefix('shield')->group(function () {
    // Get questions with user's answers
    Route::get('/questions', [ShieldQuestionsController::class, 'index']);
    
    // Submit answers
    Route::post('/submit', [ShieldSubmissionController::class, 'submit']);
    
    // Upload attachment
    Route::post('/attachment/upload', [ShieldAttachmentController::class, 'upload']);
    
    // Download results
    Route::get('/download-results', [ShieldDownloadController::class, 'downloadResults']);
});
