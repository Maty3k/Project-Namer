<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\DomainCheckService;
use Illuminate\Support\Facades\Log;
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
