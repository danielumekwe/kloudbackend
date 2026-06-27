<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use Illuminate\Http\JsonResponse;

class LegalDocumentController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $doc = LegalDocument::where('slug', $slug)->first();

        if (! $doc) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        return response()->json($doc->toApiArray());
    }

    public function index(): JsonResponse
    {
        $docs = LegalDocument::orderBy('slug')->get();

        return response()->json($docs->map->toApiArray()->values());
    }
}
