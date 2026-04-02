<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MetaData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MetaDataController extends Controller
{
    /**
     * Get the meta data
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Assuming there's only one meta data record for the app. 
        // We use first() to get the single record.
        $metaData = MetaData::first();

        if (!$metaData) {
            return response()->json([
                'success' => false,
                'message' => 'Data meta_data belum tersedia.',
                'data'    => null
            ], 404);
        }

        if ($metaData->icon) {
            $metaData->icon_url = asset('storage/' . $metaData->icon);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil data meta_data.',
            'data'    => $metaData
        ], 200);
    }

    /**
     * Update the meta data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon'        => 'nullable|file|mimes:jpeg,png,jpg,svg,ico|max:2048',
        ]);

        $metaData = MetaData::first() ?? new MetaData();

        if ($request->has('title')) {
            $metaData->title = $request->title;
        }

        if ($request->has('description')) {
            $metaData->description = $request->description;
        }

        if ($request->hasFile('icon')) {
            if ($metaData->icon) {
                Storage::disk('public')->delete($metaData->icon);
            }
            $path = $request->file('icon')->store('metadata', 'public');
            $metaData->icon = $path;
        }

        $metaData->save();

        if ($metaData->icon) {
            $metaData->icon_url = asset('storage/' . $metaData->icon);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil memperbarui data meta_data.',
            'data'    => $metaData
        ], 200);
    }
}
