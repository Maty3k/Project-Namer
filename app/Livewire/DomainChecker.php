<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Jobs\GenerateLogosJob;
use App\Models\LogoGeneration;
use App\Services\DomainCheckService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Domain Checker')]
class DomainChecker extends Component
{
    public string $nameInput = '';

    public string $cleanName = '';

    /**
     * @var array<string, array{extension: string, available: bool|null, status: string}>
     */
    public array $domainResults = [];

    public bool $isChecking = false;

    public ?string $errorMessage = null;

    /**
     * Check if any domain is available.
     */
    #[Computed]
    public function hasAvailableDomain(): bool
    {
        foreach ($this->domainResults as $result) {
            if ($result['available'] === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate logos for the checked name and redirect to gallery.
     */
    public function generateLogoForName(): void
    {
        if ($this->cleanName === '' || ! auth()->check()) {
            return;
        }

        try {
            // Create logo generation record
            $logoGeneration = LogoGeneration::create([
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'business_name' => $this->cleanName,
                'business_description' => "Logo for {$this->cleanName}",
                'generation_mode' => 'creative',
                'status' => 'processing',
                'total_logos_requested' => 4,
                'logos_completed' => 0,
            ]);

            // Dispatch logo generation job
            dispatch(new GenerateLogosJob($logoGeneration));

            // Redirect to the logo gallery for this generation
            $this->redirect(route('logo.gallery', ['logoGeneration' => $logoGeneration->id]));

        } catch (\Exception $e) {
            Log::error('Logo generation from domain checker failed', [
                'name' => $this->cleanName,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            $this->errorMessage = 'Failed to start logo generation. Please try again.';
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'nameInput' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-Z0-9\s\-\.]+$/',
            ],
        ];
    }

    /**
     * Check domain availability for the entered name.
     */
    public function checkDomain(): void
    {
        $this->errorMessage = null;
        $this->domainResults = [];

        $this->validate();

        $this->isChecking = true;

        // Clean the input
        $this->cleanName = trim($this->nameInput);
        $this->cleanName = (string) preg_replace('/\.(com|net|org|io|co|app|dev|ai|tech|studio)$/i', '', $this->cleanName);
        $this->cleanName = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $this->cleanName));

        try {
            $domainCheckService = app(DomainCheckService::class);

            // Check 4 main TLDs
            $tldsToCheck = ['com', 'io', 'co', 'net'];
            foreach ($tldsToCheck as $tld) {
                $domain = "{$this->cleanName}.{$tld}";
                $result = $domainCheckService->checkDomain($domain);

                $this->domainResults[$domain] = [
                    'extension' => ".{$tld}",
                    'available' => $result['available'],
                    'status' => $result['status'] ?? 'unknown',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Domain check failed in DomainChecker', [
                'name' => $this->cleanName,
                'error' => $e->getMessage(),
            ]);

            $this->errorMessage = 'Failed to check domain availability. Please try again.';
        } finally {
            $this->isChecking = false;
        }
    }

    /**
     * Reset the form and results.
     */
    public function resetChecker(): void
    {
        $this->reset(['nameInput', 'cleanName', 'domainResults', 'errorMessage']);
    }

    public function render(): mixed
    {
        return view('livewire.domain-checker');
    }
}
