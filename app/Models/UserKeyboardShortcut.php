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
 * @property array|null $custom_shortcuts
 * @property array|null $disabled_shortcuts
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 */
final class UserKeyboardShortcut extends Model
{
    protected $fillable = [
        'user_id',
        'custom_shortcuts',
        'disabled_shortcuts',
    ];

    protected function casts(): array
    {
        return [
            'custom_shortcuts' => 'array',
            'disabled_shortcuts' => 'array',
        ];
    }

    /**
     * Get the user that owns the keyboard shortcuts.
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
            'themeCustomizer' => [
                'key' => 't',
                'modifiers' => ['ctrl'],
                'description' => 'Theme customizer',
                'enabled' => true,
            ],
            'logoGallery' => [
                'key' => 'l',
                'modifiers' => ['ctrl'],
                'description' => 'Logo gallery',
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
     * Get merged shortcuts (defaults + custom + disabled).
     *
     * @return array<string, mixed>
     */
    public function getMergedShortcuts(): array
    {
        $shortcuts = self::getDefaultShortcuts();

        // Apply custom shortcuts
        if ($this->custom_shortcuts) {
            foreach ($this->custom_shortcuts as $action => $binding) {
                if (isset($shortcuts[$action])) {
                    $shortcuts[$action] = array_merge($shortcuts[$action], $binding);
                }
            }
        }

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
                'custom_shortcuts' => null,
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
     * Update a custom shortcut binding.
     *
     * @param  array<string, mixed>  $binding
     */
    public function updateShortcut(string $action, array $binding): void
    {
        $customShortcuts = $this->custom_shortcuts ?? [];
        $customShortcuts[$action] = $binding;

        $this->update(['custom_shortcuts' => $customShortcuts]);
    }

    /**
     * Reset a shortcut to default.
     */
    public function resetShortcut(string $action): void
    {
        $customShortcuts = $this->custom_shortcuts ?? [];
        unset($customShortcuts[$action]);

        $this->update(['custom_shortcuts' => $customShortcuts]);
    }

    /**
     * Reset all shortcuts to defaults.
     */
    public function resetAllShortcuts(): void
    {
        $this->update([
            'custom_shortcuts' => null,
            'disabled_shortcuts' => null,
        ]);
    }
}
