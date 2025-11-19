<?php

namespace App\Actions\Release;

use App\Models\Release;
use Illuminate\Http\Request;

class StoreReleaseAction
{
    public function execute(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'release_category_id' => 'required|exists:releases_categories,id',

            'file' => 'nullable|file|mimes:pdf|max:20480',
            'excel' => 'nullable|file|mimes:xlsx,xls|max:20480',
            'powerbi' => 'nullable|file|mimes:pbix|max:51200',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        // Store uploaded files
        $paths = [
            'file' => $request->file('file')?->store('releases', 'public'),
            'excel' => $request->file('excel')?->store('releases', 'public'),
            'powerbi' => $request->file('powerbi')?->store('releases', 'public'),
            'image' => $request->file('image')?->store('releases/images', 'public'),
        ];

        return Release::create([
            'title' => $request->title,
            'description' => $request->description,
            'author' => $request->author ?? 'Admin',
            'release_category_id' => $request->release_category_id,
            'file_path' => $paths['file'],
            'excel_path' => $paths['excel'],
            'powerbi_path' => $paths['powerbi'],
            'image' => $paths['image'],
        ]);
    }
}
