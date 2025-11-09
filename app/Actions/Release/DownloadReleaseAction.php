<?php

namespace App\Actions\Release;

use App\Models\Release;
use Illuminate\Support\Facades\Auth;

class DownloadReleaseAction
{
    public function execute($id, $type = 'pdf')
    {
        if (!Auth::guard('sanctum')->check() && !Auth::check()) {
            return response()->json([
                'error' => 'Unauthorized. Please login first.',
                'redirect' => '/login',
            ], 401);
        }

        $release = Release::find($id);
        if (!$release) {
            return response()->json(['error' => 'Release not found.'], 404);
        }

        $path = match ($type) {
            'pdf' => $release->file_path,
            'excel' => $release->excel_path,
            'powerbi' => $release->powerbi_path,
            default => null,
        };

        if (!$path) {
            return response()->json(['error' => 'File type not found.'], 404);
        }

        $fullPath = storage_path('app/public/' . $path);
        if (!file_exists($fullPath)) {
            return response()->json(['error' => 'File not found.'], 404);
        }

        $fileName = $release->title . '.' . match ($type) {
            'pdf' => 'pdf',
            'excel' => 'xlsx',
            'powerbi' => 'pbix',
        };

        return response()->download($fullPath, $fileName);
    }
}
