<?php

namespace App\Http\Controllers\Api\ModelController;

use App\Http\Controllers\Controller;
use App\Repositories\ModelRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ModelController extends Controller
{
    protected ModelRepository $repo;

    public function __construct(ModelRepository $repo)
    {
        $this->repo = $repo;
    }

    // List all models with pagination & limit
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        $page = (int) $request->query('page', 1);

        $models = $this->repo->all($limit, $page);

        return response()->json($models);
    }

    // Show single model
    public function show(int $id)
    {
        $model = $this->repo->find($id);

        if (!$model) {
            return response()->json(['message' => 'Model not found'], 404);
        }

        return response()->json($model);
    }

    // Download attachment
    public function downloadAttachment(int $id, Request $request)
    {
        // Find the model
        $model = $this->repo->find($id);

        if (!$model) {
            return response()->json(['message' => 'Model not found'], 404);
        }

        // Get attachment path from model (adjust based on your model structure)
        $attachmentPath = $model->attachment_path ?? null;

        if (!$attachmentPath) {
            return response()->json(['message' => 'No attachment found'], 404);
        }

        // Check if file exists
        if (!Storage::exists($attachmentPath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // Get original filename (adjust based on your model structure)
        $filename = $model->attachment_name ?? basename($attachmentPath);

        // Return file download response
        return Storage::download($attachmentPath, $filename);
    }

    // Alternative: Download by attachment ID (if attachments are in separate table)
    public function downloadAttachmentById(int $modelId, int $attachmentId)
    {
        $model = $this->repo->find($modelId);

        if (!$model) {
            return response()->json(['message' => 'Model not found'], 404);
        }

        // Find specific attachment (adjust based on your relationship structure)
        $attachment = $model->attachments()->find($attachmentId);

        if (!$attachment) {
            return response()->json(['message' => 'Attachment not found'], 404);
        }

        // Check if file exists
        if (!Storage::exists($attachment->file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // Return file download response
        return Storage::download($attachment->file_path, $attachment->filename);
    }
}