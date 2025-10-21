<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\LogoGeneration;
use Livewire\Component;

class LogoGenerations extends Component
{
    /**
     * Get all logo generations for the authenticated user.
     */
    public function getLogoGenerationsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return auth()->user()
            ->logoGenerations()
            ->with('generatedLogos')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Render the logo generations list.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.logo-generations', [
            'logoGenerations' => $this->getLogoGenerationsProperty(),
        ])->layout('components.layouts.app');
    }
}
