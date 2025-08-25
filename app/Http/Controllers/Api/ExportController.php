<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExportResource;
use App\Models\Export;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ExportController handles API endpoints for export management.
 *
 * Provides creation, download, and management of file exports
 * with proper authorization and file serving.
 */
final class ExportController extends Controller
{
    public function __construct(
        private readonly ExportService $exportService
    ) {
        $this->middleware('auth')->except(['download', 'publicDownload']);
        $this->middleware('throttle.exports')->only(['store']);
    }

    /**
     * Get paginated list of user's exports with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'export_type' => ['sometimes', 'in:pdf,csv,json'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Export::where('user_id', $request->user()->id)
            ->with(['exportable'])
            ->orderBy('created_at', 'desc');

        if (isset($validated['export_type'])) {
            $query->where('export_type', $validated['export_type']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => ExportResource::collection($paginated->items()),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
                'has_more_pages' => $paginated->hasMorePages(),
            ],
        ]);
    }

    /**
     * Create a new export.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exportable_type' => ['required', 'string'],
            'exportable_id' => ['required', 'integer', 'exists:logo_generations,id'],
            'export_type' => ['required', 'in:pdf,csv,json'],
            'expires_in_days' => ['sometimes', 'integer', 'min:1', 'max:30'],
            'include_domains' => ['sometimes', 'boolean'],
            'include_metadata' => ['sometimes', 'boolean'],
            'include_logos' => ['sometimes', 'boolean'],
            'include_branding' => ['sometimes', 'boolean'],
            'template' => ['sometimes', 'string', 'in:default,professional'],
            'settings' => ['sometimes', 'array'],
        ]);

        try {
            $export = $this->exportService->createExport($request->user(), $validated);

            return response()->json([
                'data' => new ExportResource($export),
                'message' => 'Export created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Export creation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a specific export.
     *
     * @param  Export<\Database\Factories\ExportFactory>  $export
     */
    public function show(Request $request, Export $export): JsonResponse
    {
        Gate::authorize('view', $export);

        $export->load(['exportable', 'user']);

        return response()->json([
            'data' => new ExportResource($export),
        ]);
    }

    /**
     * Download export file (authenticated).
     */
    public function download(Request $request, string $uuid): StreamedResponse|JsonResponse
    {
        $export = Export::where('uuid', $uuid)->firstOrFail();

        Gate::authorize('download', $export);

        if ($export->isExpired()) {
            return response()->json([
                'message' => 'Export has expired',
            ], 410); // Gone
        }

        if (! $export->fileExists()) {
            return response()->json([
                'message' => 'Export file not found',
            ], 404);
        }

        return $this->exportService->serveDownload($export);
    }

    /**
     * Delete an export and its associated file.
     *
     * @param  Export<\Database\Factories\ExportFactory>  $export
     */
    public function destroy(Request $request, Export $export): JsonResponse
    {
        Gate::authorize('delete', $export);

        // Export model has boot method that handles file deletion
        $export->delete();

        return response()->json([
            'message' => 'Export deleted successfully',
        ]);
    }

    /**
     * Get export analytics for the authenticated user.
     */
    public function analytics(Request $request): JsonResponse
    {
        $analytics = $this->exportService->getExportAnalytics($request->user());

        return response()->json([
            'data' => $analytics,
        ]);
    }

    /**
     * Clean up expired exports (admin/system endpoint).
     */
    public function cleanup(Request $request): JsonResponse
    {
        // This should be restricted to admin users or system processes
        $deletedCount = $this->exportService->cleanupExpiredExports();

        return response()->json([
            'message' => "Cleaned up {$deletedCount} expired exports",
            'deleted_count' => $deletedCount,
        ]);
    }

    /**
     * Public download endpoint using UUID (no authentication required).
     */
    public function publicDownload(string $uuid): StreamedResponse|JsonResponse
    {
        $export = Export::where('uuid', $uuid)->firstOrFail();

        if ($export->isExpired()) {
            return response()->json([
                'message' => 'Export has expired',
            ], 410);
        }

        if (! $export->fileExists()) {
            return response()->json([
                'message' => 'Export file not found',
            ], 404);
        }

        return $this->exportService->serveDownload($export);
    }
}
