<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
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
    public function authenticate(Request $request, string $uuid): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $validation = $this->shareService->validateShareAccess($uuid, $request->password);

        if (! $validation['success']) {
            return back()->withErrors([
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
            'type' => class_basename($share->shareable_type),
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

                $data['generated_logos'] = $logoGeneration->generatedLogos->map(fn (GeneratedLogo $logo) => [
                    'id' => $logo->id,
                    'style' => $logo->style,
                    'prompt' => $logo->prompt_used,
                    'local_path' => $logo->original_file_path,
                    'color_variants' => $logo->colorVariants->map(fn (LogoColorVariant $variant) => [
                        'id' => $variant->id,
                        'color_scheme' => $variant->color_scheme,
                        'file_path' => $variant->file_path,
                        'is_original' => $variant->color_scheme === 'original',
                    ]),
                ]);
            }
        }

        return $data;
    }
}
