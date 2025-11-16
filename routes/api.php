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

        
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me']);
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
use App\Http\Controllers\Api\CertificateController\CertificateController;

// Shield Routes - Requires approved organization
Route::middleware(['auth:sanctum', 'organization.approved'])->prefix('shield')->group(function () {
    
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

// Certificate Routes - Requires approved organization
Route::middleware(['auth:sanctum', 'organization.approved'])->prefix('certificates')->group(function () {
    
    // Get summary of all paths for authenticated user's organization
    Route::get('summary', [CertificateController::class, 'summary'])
        ->name('certificates.summary');
    
    // Get questions by path (strategic, operational, hr)
    Route::get('questions/{path}', [CertificateController::class, 'getQuestionsByPath'])
        ->name('certificates.questions');
    
    // Submit answers for authenticated user's organization (specific path)
    Route::post('answers/{path}', [CertificateController::class, 'submitAnswers'])
        ->name('certificates.submit');
    
    // Show certificate details for specific path
    Route::get('answers/{path}', [CertificateController::class, 'show'])
        ->name('certificates.show');
    
    // Update answers for specific path
    Route::put('answers/{path}', [CertificateController::class, 'updateAnswers'])
        ->name('certificates.update');
    
    // Delete certificate answers for specific path
    Route::delete('answers/{path}', [CertificateController::class, 'destroy'])
        ->name('certificates.destroy');
    Route::get('download/{path}', [CertificateController::class, 'downloadCertificate'])
        ->name('certificates.download');    
});

use App\Http\Controllers\Api\ModelController\ModelController;

Route::get('/models', [ModelController::class, 'index']);
Route::get('/models/{id}', [ModelController::class, 'show']);
// For single attachment per model
Route::get('models/{id}/download', [ModelController::class, 'downloadAttachment']);

// For multiple attachments per model
Route::get('models/{modelId}/attachments/{attachmentId}/download', [ModelController::class, 'downloadAttachmentById']);

use App\Http\Controllers\Api\DesginController\DesginController;

Route::get('/dashboards', [DesginController::class, 'index']);
Route::get('/dashboards/{id}', [DesginController::class, 'show']);

use App\Http\Controllers\Api\NewsController\NewsController;

Route::prefix('news')->group(function () {
    Route::get('/', [NewsController::class, 'index']); // list news
    Route::get('/{id}', [NewsController::class, 'show']); // show single news
});


use App\Http\Controllers\Api\SubscriptionController\SubscriptionController;
Route::post('/subscribe/pro', [SubscriptionController::class, 'subscribeToPro'])
    ->middleware('auth:sanctum');

use App\Http\Controllers\Api\ToolController\ToolController;
Route::prefix('tools')->group(function () {
    Route::get('/', [ToolController::class, 'index']);
    Route::get('/{id}', [ToolController::class, 'show']);
    Route::get('/{id}/download', [ToolController::class, 'download']);
});