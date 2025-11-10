<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
// use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\OrganizationController\OrganizationController;
use App\Http\Controllers\Api\PodcastController\PodcastController;
use App\Http\Controllers\Api\ReleaseController\ReleaseController;
use App\Http\Controllers\Api\BlogController\BlogController;

// Shield Controllers
use App\Http\Controllers\Api\Shield\ShieldAnalyticsController;
use App\Http\Controllers\Api\Shield\ShieldOrganizationsController;
use App\Http\Controllers\Api\Shield\ShieldQuestionsController;
use App\Http\Controllers\Api\Shield\ShieldSaveController;
use App\Http\Controllers\Api\Shield\ShieldSubmissionController;
use App\Http\Controllers\Api\Shield\ShieldAttachmentController;
use App\Http\Controllers\Api\Shield\ShieldDownloadController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('email/verify', [EmailVerificationController::class, 'verifyEmail']);
Route::post('email/resend', [EmailVerificationController::class, 'resendVerification']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

/*
|--------------------------------------------------------------------------
| Organization Routes
|--------------------------------------------------------------------------
*/
Route::apiResource('organizations', OrganizationController::class)->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Podcast Routes
|--------------------------------------------------------------------------
*/
Route::prefix('podcasts')->group(function () {
    Route::get('/', [PodcastController::class, 'index']);
    Route::get('/{id}', [PodcastController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Release Routes
|--------------------------------------------------------------------------
*/
Route::prefix('releases')->group(function () {
    Route::get('/', [ReleaseController::class, 'index']);
    Route::get('/{id}', [ReleaseController::class, 'show']);
    Route::get('/{id}/download', [ReleaseController::class, 'download']);
});

/*
|--------------------------------------------------------------------------
| Blog Routes
|--------------------------------------------------------------------------
*/
Route::prefix('blogs')->group(function () {
    Route::get('/', [BlogController::class, 'index']);
    Route::get('/{id}', [BlogController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Shield Routes - Public (No Authentication Required)
|--------------------------------------------------------------------------
*/
Route::prefix('shield')->group(function () {
    // Analytics - Get overall statistics
    Route::get('/analytics', [ShieldAnalyticsController::class, 'index']);
    
    // Organizations - Get paginated list with filters
    Route::get('/organizations', [ShieldOrganizationsController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| Shield Routes - Protected (Authentication Required)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('shield')->group(function () {
    
    // Questions - Get all questions with user's saved answers
    Route::get('/questions', [ShieldQuestionsController::class, 'index']);
    
    // Save - Save draft answers (partial submission allowed)
    Route::post('/save', [ShieldSaveController::class, 'save']);
    
    // Submit - Submit final answers (all questions required)
    Route::post('/submit', [ShieldSubmissionController::class, 'submit']);
    
    // Attachment - Upload file
    Route::post('/attachment/upload', [ShieldAttachmentController::class, 'upload']);
    
    // Download - Generate and download PDF results
    Route::get('/download-results', [ShieldDownloadController::class, 'downloadResults']);
});

