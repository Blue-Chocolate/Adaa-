<?php 

use Illuminate\Support\Facades\Route;

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
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\PasswordResetController;

use App\Http\Controllers\DumpAuthenticationController;

Route::post('/register', [DumpAuthenticationController::class, 'register']);
Route::post('/login', [DumpAuthenticationController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [DumpAuthenticationController::class, 'logout']);
    Route::get('/me', [DumpAuthenticationController::class, 'me']);
});
/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
// Route::post('/register', [AuthController::class, 'register']);
// Route::post('/login', [AuthController::class, 'login']);

// // Email verification routes
// // Email verification routes - No authentication required
// Route::get('/email/verify', [EmailVerificationController::class, 'verify'])
//     ->name('verification.verify');
// Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
//     ->name('verification.resend');

//     Route::post('/password/forgot', [PasswordResetController::class, 'forgotPassword'])
//         ->name('password.forgot');
//     Route::post('/password/reset', [PasswordResetController::class, 'resetPassword'])
//         ->name('password.reset');
//     Route::get('/password/verify-token', [PasswordResetController::class, 'verifyToken'])
//         ->name('password.verify-token');

        
// Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
// Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me']);


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

    Route::post('/shield/upload-attachment', [ShieldSubmissionController::class, 'uploadAttachment']);

});

