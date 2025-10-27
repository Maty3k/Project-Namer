<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\UserAIPreferences;
use Illuminate\View\View;
use Livewire\Component;

/**
 * AI Preferences settings component.
 *
 * Allows users to view and manage their saved AI generation preferences.
 */
class AIPreferences extends Component
{
    /** @var array<int, string> */
    public array $selectedAIModels = [];

    public string $generationMode = '';

    public bool $deepThinking = false;

    public bool $enableModelComparison = false;

    public bool $hasPreferences = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadPreferences();
    }

    /**
     * Load user preferences from database.
     */
    protected function loadPreferences(): void
    {
        $preferences = UserAIPreferences::where('user_id', auth()->id())->first();

        if ($preferences) {
            $this->hasPreferences = true;
            $this->selectedAIModels = $preferences->preferred_models ?? [];
            $this->generationMode = $preferences->default_generation_mode ?? '';
            $this->deepThinking = $preferences->default_deep_thinking ?? false;
            $this->enableModelComparison = $preferences->enable_model_comparison ?? false;
        } else {
            $this->hasPreferences = false;
            $this->selectedAIModels = [];
            $this->generationMode = '';
            $this->deepThinking = false;
            $this->enableModelComparison = false;
        }
    }

    /**
     * Clear user AI preferences.
     */
    public function clearPreferences(): void
    {
        UserAIPreferences::where('user_id', auth()->id())->delete();

        $this->loadPreferences();

        $this->dispatch('show-toast', [
            'message' => 'AI preferences cleared successfully',
            'type' => 'success',
        ]);
    }

    public function render(): View
    {
        return view('livewire.settings.a-i-preferences')
            ->layout('components.settings.layout');
    }
}
