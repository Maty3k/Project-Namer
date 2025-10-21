<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User keyboard shortcuts preferences.
 *
 * @property int $id
 * @property int $user_id
 * @property array<array-key, mixed>|null $disabled_shortcuts
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserKeyboardShortcut newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserKeyboardShortcut newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserKeyboardShortcut query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserKeyboardShortcut whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserKeyboardShortcut whereDisabledShortcuts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserKeyboardShortcut whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserKeyboardShortcut whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserKeyboardShortcut whereUserId($value)
 *
 * @mixin \Eloquent
 */
final class UserKeyboardShortcut extends Model
{
    protected $fillable = [
        'user_id',
        'disabled_shortcuts',
    ];

    protected function casts(): array
    {
        return [
            'disabled_shortcuts' => 'array',
        ];
    }

    /**
     * Get the user that owns the keyboard shortcuts.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default keyboard shortcuts configuration.
     *
     * @return array<string, mixed>
     */
    public static function getDefaultShortcuts(): array
    {
        return [
            'newProject' => [
                'key' => 'n',
                'modifiers' => ['ctrl'],
                'description' => 'New project',
                'enabled' => true,
            ],
            'settings' => [
                'key' => 's',
                'modifiers' => ['ctrl'],
                'description' => 'Open settings',
                'enabled' => true,
            ],
            'appearance' => [
                'key' => 't',
                'modifiers' => ['ctrl'],
                'description' => 'Appearance settings',
                'enabled' => true,
            ],
            'logoGallery' => [
                'key' => 'l',
                'modifiers' => ['ctrl'],
                'description' => 'Logo gallery',
                'enabled' => true,
            ],
            'keyboardShortcuts' => [
                'key' => 'k',
                'modifiers' => ['ctrl'],
                'description' => 'Keyboard shortcuts settings',
                'enabled' => true,
            ],
            'help' => [
                'key' => 'h',
                'modifiers' => ['ctrl'],
                'description' => 'Show keyboard shortcuts',
                'enabled' => true,
            ],
            'escape' => [
                'key' => 'Escape',
                'modifiers' => [],
                'description' => 'Close modals',
                'enabled' => true,
            ],
        ];
    }

    /**
     * Get merged shortcuts (defaults + disabled).
     *
     * @return array<string, mixed>
     */
    public function getMergedShortcuts(): array
    {
        $shortcuts = self::getDefaultShortcuts();

        // Apply disabled shortcuts
        if ($this->disabled_shortcuts) {
            foreach ($this->disabled_shortcuts as $action) {
                if (isset($shortcuts[$action])) {
                    $shortcuts[$action]['enabled'] = false;
                }
            }
        }

        return $shortcuts;
    }

    /**
     * Find or create keyboard shortcuts for a user.
     */
    public static function findOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'disabled_shortcuts' => null,
            ]
        );
    }

    /**
     * Check if a specific shortcut is enabled for this user.
     */
    public function isShortcutEnabled(string $action): bool
    {
        if ($this->disabled_shortcuts && in_array($action, $this->disabled_shortcuts)) {
            return false;
        }

        return true;
    }

    /**
     * Toggle a specific shortcut on/off.
     */
    public function toggleShortcut(string $action): void
    {
        $disabledShortcuts = $this->disabled_shortcuts ?? [];

        if (in_array($action, $disabledShortcuts)) {
            // Enable it
            $disabledShortcuts = array_values(array_diff($disabledShortcuts, [$action]));
        } else {
            // Disable it
            $disabledShortcuts[] = $action;
        }

        $this->update(['disabled_shortcuts' => $disabledShortcuts]);
    }

    /**
     * Reset all shortcuts to defaults.
     */
    public function resetAllShortcuts(): void
    {
        $this->update([
            'disabled_shortcuts' => null,
        ]);
    }
}
