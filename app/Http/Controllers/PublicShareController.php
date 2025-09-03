<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SharePasswordRequest;
use App\Models\MoodBoard;
use App\Models\Share;
use App\Services\ShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * PublicShareController handles public access to shared content.
 *
 * Provides password authentication, access tracking, and content display
 * for publicly shared logo generations.
 */
final class PublicShareController extends Controller
{
    public function __construct(
        private readonly ShareService $shareService
    ) {}

    /**
     * Display a public share or password authentication form.
     */
    public function show(Request $request, string $uuid): Response|JsonResponse
    {
        $validation = $this->shareService->validateShareAccess($uuid);

        if (! $validation['success']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validation['error'],
                ], 404);
            }

            abort(404, $validation['error']);
        }

        /** @var Share $share */
        $share = $validation['share'];

        // Handle password protection
        if ($share->share_type === 'password_protected') {
            $authenticated = session("share_authenticated_{$share->uuid}", false);

            if (! $authenticated) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Password required',
                        'requires_password' => true,
                    ], 423); // Locked
                }

                return $this->showPasswordForm($share);
            }
        }

        // Record access
        $this->shareService->recordShareAccess($share, [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
        ]);

        if ($request->expectsJson()) {
            return $this->jsonResponse($share);
        }

        return $this->webResponse($share);
    }

    /**
     * Authenticate password-protected share.
     */
    public function authenticate(SharePasswordRequest $request, string $uuid): RedirectResponse
    {
        $password = $request->getSanitizedPassword();

        $validation = $this->shareService->validateShareAccess($uuid, $password);

        if (! $validation['success']) {
            return redirect()->route('public-share.show', $uuid)->withErrors([
                'password' => $validation['error'],
            ]);
        }

        /** @var Share $share */
        $share = $validation['share'];

        // Set session flag for authentication
        session(["share_authenticated_{$share->uuid}" => true]);

        return redirect()->route('public-share.show', $share->uuid);
    }

    /**
     * Show password authentication form.
     */
    private function showPasswordForm(Share $share): Response
    {
        $metadata = $this->shareService->generateSocialMediaMetadata($share);

        return response()->view('shares.password', [
            'share' => $share,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Return JSON response for API requests.
     */
    private function jsonResponse(Share $share): JsonResponse
    {
        $share->load(['shareable']);

        return response()->json([
            'uuid' => $share->uuid,
            'title' => $share->title,
            'description' => $share->description,
            'share_type' => $share->share_type,
            'view_count' => $share->view_count,
            'created_at' => $share->created_at->toISOString(),
            'settings' => $share->settings,
            'shareable' => $this->getShareableData($share),
        ]);
    }

    /**
     * Return web response for browser requests.
     */
    private function webResponse(Share $share): Response
    {
        $share->load(['shareable', 'user']);

        // Check if shareable content still exists
        if (! $share->shareable) {
            return response()->view('shares.not-found', [
                'message' => 'Share content is no longer available',
            ]);
        }

        $metadata = $this->shareService->generateSocialMediaMetadata($share);

        return response()->view('shares.show', [
            'share' => $share,
            'metadata' => $metadata,
            'shareable' => $this->getShareableData($share),
        ]);
    }

    /**
     * Get formatted shareable data.
     *
     * @return array<string, mixed>
     */
    private function getShareableData(Share $share): array
    {
        if (! $share->shareable) {
            return [];
        }

        $data = [
            'type' => \Illuminate\Support\Str::snake(class_basename($share->shareable_type)),
            'id' => $share->shareable_id,
        ];

        // Add specific data based on shareable type
        if ($share->shareable instanceof \App\Models\LogoGeneration) {
            $logoGeneration = $share->shareable;

            $data = array_merge($data, [
                'business_name' => $logoGeneration->business_name,
                'business_description' => $logoGeneration->business_description,
                'status' => $logoGeneration->status,
                'created_at' => $logoGeneration->created_at->toISOString(),
            ]);

            // Load generated logos if available
            if ($logoGeneration->generatedLogos()->exists()) {
                $logoGeneration->load('generatedLogos.colorVariants');

                $logos = [];
                foreach ($logoGeneration->generatedLogos as $logo) {
                    $colorVariants = [];
                    foreach ($logo->colorVariants as $variant) {
                        $colorVariants[] = [
                            'id' => $variant->id,
                            'color_scheme' => $variant->color_scheme,
                            'preview_url' => $this->getAssetUrl($variant->file_path),
                            'download_url' => $this->getAssetUrl($variant->file_path),
                            'is_original' => $variant->color_scheme === 'original',
                        ];
                    }

                    $logos[] = [
                        'id' => $logo->id,
                        'style' => $logo->style,
                        'prompt' => $logo->prompt_used,
                        'preview_url' => $this->getAssetUrl($logo->original_file_path),
                        'download_url' => $this->getAssetUrl($logo->original_file_path),
                        'color_variants' => $colorVariants,
                    ];
                }

                $data['logos'] = $logos;
            }
        }

        return $data;
    }

    /**
     * Display a publicly shared mood board.
     */
    public function showMoodBoard(string $token): Response
    {
        $moodBoard = MoodBoard::where('share_token', $token)
            ->where('is_public', true)
            ->with(['projectImages', 'project', 'user'])
            ->first();

        if (! $moodBoard) {
            abort(404, 'Mood board not found or not publicly shared');
        }

        return response()->view('shares.mood-board', [
            'moodBoard' => $moodBoard,
            'metadata' => [
                'title' => "Mood Board: {$moodBoard->name}",
                'description' => $moodBoard->description ?? 'A creative mood board collection',
            ],
        ]);
    }

    /**
     * Get asset URL for a file path, handling nullable paths.
     */
    private function getAssetUrl(?string $filePath): ?string
    {
        return $filePath !== null ? asset("storage/{$filePath}") : null;
    }
}
