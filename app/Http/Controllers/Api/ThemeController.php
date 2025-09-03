<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserThemePreference;
use App\Services\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ThemeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected ThemeService $themeService) {}

    /**
     * Get current user theme preferences.
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $user = $request->user();

        $preference = UserThemePreference::where('user_id', $user->id)->first();

        if (! $preference) {
            $defaultThemes = $this->themeService->getPredefinedThemes();
            $defaultTheme = $defaultThemes[0];
            $defaultTheme['id'] = null;

            return response()->json(['theme' => $defaultTheme]);
        }

        $themeData = $preference->toArray();
        $themeData['theme_name'] ??= 'custom';

        return response()->json(['theme' => $themeData]);
    }

    /**
     * Update user theme preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'primary_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'text_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_name' => ['required', 'string', 'max:50'],
            'is_dark_mode' => ['boolean'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = $request->user();
        $data = $validator->validated();

        $preference = UserThemePreference::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return response()->json(['theme' => $preference->toArray()]);
    }

    /**
     * Get predefined theme collection.
     */
    public function getPresets(): JsonResponse
    {
        $themes = $this->themeService->getPredefinedThemes();

        return response()->json(['themes' => $themes]);
    }

    /**
     * Generate custom CSS for theme.
     */
    public function generateCss(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'primary_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'text_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();
        $css = $this->themeService->generateCssProperties($data);

        $accessibilityScore = $this->themeService->calculateAccessibilityScore(
            $data['primary_color'],
            $data['background_color'],
            $data['text_color']
        );

        $properties = [
            '--color-primary',
            '--color-accent',
            '--color-background',
            '--color-text',
        ];

        return response()->json([
            'css' => $css,
            'custom_properties' => $properties,
            'accessibility_score' => $accessibilityScore,
        ]);
    }

    /**
     * Validate accessibility of color combinations.
     */
    public function validateAccessibility(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'primary_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'text_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();
        $textColor = $data['text_color'] ?? '#000000';

        $contrastRatio = $this->themeService->calculateContrastRatio(
            $data['primary_color'],
            $data['background_color']
        );

        $wcagLevel = $this->themeService->getWcagLevel($contrastRatio);
        $accessibilityScore = $this->themeService->calculateAccessibilityScore(
            $data['primary_color'],
            $data['background_color'],
            $textColor
        );

        $feedback = $this->themeService->generateAccessibilityFeedback(
            $data['primary_color'],
            $data['background_color'],
            $textColor
        );

        return response()->json([
            'accessibility_score' => $accessibilityScore,
            'contrast_ratio' => $contrastRatio,
            'wcag_level' => $wcagLevel,
            'warnings' => $feedback['warnings'],
            'suggestions' => $feedback['suggestions'],
        ]);
    }

    /**
     * Import theme from uploaded file.
     */
    public function importTheme(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'theme_file' => ['required', 'file', 'mimetypes:application/json', 'max:1024'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $file = $request->file('theme_file');
        $content = file_get_contents($file->path());
        $themeData = json_decode($content, true);

        if (! $themeData || ! is_array($themeData)) {
            return response()->json(['error' => 'Invalid theme file format'], 422);
        }

        $validator = Validator::make($themeData, [
            'theme_name' => ['required', 'string', 'max:50'],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'text_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_dark_mode' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid theme data in file'], 422);
        }

        $user = $request->user();
        $preference = UserThemePreference::updateOrCreate(
            ['user_id' => $user->id],
            $validator->validated()
        );

        return response()->json(['theme' => $preference->toArray()]);
    }

    /**
     * Export current theme as downloadable file.
     */
    public function exportTheme(Request $request): JsonResponse
    {
        $user = $request->user();
        $preference = UserThemePreference::where('user_id', $user->id)->first();

        if (! $preference) {
            $defaultThemes = $this->themeService->getPredefinedThemes();
            $themeData = $defaultThemes[0];
        } else {
            $themeData = $preference->toArray();
            unset($themeData['id'], $themeData['user_id'], $themeData['created_at'], $themeData['updated_at']);
        }

        $filename = 'theme-'.($themeData['theme_name'] ?? 'export').'.json';

        return response()->json($themeData)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }
}
