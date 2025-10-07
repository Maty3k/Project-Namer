<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserKeyboardShortcut;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class KeyboardShortcutsController extends Controller
{
    /**
     * Get the authenticated user's keyboard shortcuts preferences.
     */
    public function index(): JsonResponse
    {
        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser(Auth::id());

        return response()->json([
            'enabled' => $userShortcuts->enabled,
            'shortcuts' => $userShortcuts->getMergedShortcuts(),
        ]);
    }
}
