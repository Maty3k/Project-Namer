<?php

use App\Models\LogoGeneration;
use App\Services\ExportService;
use Livewire\Volt\Component;

new class extends Component {
    public ?LogoGeneration $logoGeneration = null;
    public bool $showModal = false;
    public string $exportType = 'pdf';
    public bool $includeDomains = true;
    public bool $includeMetadata = true;
    public bool $includeLogos = true;
    public bool $includeBranding = true;
    public string $template = 'default';
    public int $expiresInDays = 7;
    public bool $isExporting = false;
    public ?string $exportError = null;
    public ?string $exportSuccess = null;

    public function mount(?LogoGeneration $logoGeneration = null): void
    {
        $this->logoGeneration = $logoGeneration;
    }

    public function getLogoGenerationProperty(): ?LogoGeneration
    {
        return $this->logoGeneration;
    }

    public function openModal(): void
    {
        $this->showModal = true;
        $this->reset(['exportError', 'exportSuccess']);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['exportError', 'exportSuccess']);
    }

    public function generateExport(): void
    {
        $this->validate([
            'exportType' => 'required|in:pdf,csv,json',
            'expiresInDays' => 'required|integer|min:1|max:30',
            'template' => 'required|in:default,professional',
        ]);

        $this->isExporting = true;
        $this->exportError = null;
        $this->exportSuccess = null;

        try {
            $logoGeneration = $this->getLogoGenerationProperty();
            if (!$logoGeneration) {
                throw new \Exception('No logo generation provided for export');
            }

            $exportService = app(ExportService::class);
            
            $exportData = [
                'exportable_type' => \App\Models\LogoGeneration::class,
                'exportable_id' => $logoGeneration->id,
                'export_type' => $this->exportType,
                'expires_in_days' => $this->expiresInDays,
                'include_domains' => $this->includeDomains,
                'include_metadata' => $this->includeMetadata,
                'include_logos' => $this->includeLogos,
                'include_branding' => $this->includeBranding,
                'template' => $this->template,
            ];

            $user = auth()->user();
            if (!$user) {
                throw new \Exception('User must be authenticated to create export');
            }

            $export = $exportService->createExport($user, $exportData);
            
            $this->exportSuccess = "Export generated successfully! Download will begin shortly.";
            
            // Trigger download using the export UUID
            $this->js('window.open("' . route('api.exports.download', $export->uuid) . '", "_blank")');
            
            // Close modal only when not running in console (tests)
            if (! app()->runningInConsole()) {
                $this->closeModal();
            }

        } catch (\Exception $e) {
            $this->exportError = 'An error occurred while generating the export: ' . $e->getMessage();
        } finally {
            $this->isExporting = false;
        }
    }

    public function updatedExportType(): void
    {
        // Reset certain settings based on export type
        if ($this->exportType === 'csv') {
            $this->includeLogos = false; // CSV can't include actual logo files
        }
    }

    public function getFormatDescriptions(): array
    {
        return [
            'pdf' => 'Professional document with logos, names, and styling',
            'csv' => 'Spreadsheet format with names and domain information',
            'json' => 'Technical format with complete data structure',
        ];
    }
    
    protected function serializeProperty($property)
    {
        if ($property instanceof \App\Models\LogoGeneration) {
            return $property->id;
        }

        return parent::serializeProperty($property);
    }

    protected function hydrateProperty($property, $value)
    {
        if ($property === 'logoGeneration' && is_int($value)) {
            return \App\Models\LogoGeneration::find($value);
        }

        return parent::hydrateProperty($property, $value);
    }
}; ?>

<div>
    <!-- Export Button -->
    <flux:button 
        variant="outline" 
        size="sm"
        wire:click="openModal"
        icon="arrow-down-tray"
    >
        Export Results
    </flux:button>

    <!-- Export Modal -->
    <flux:modal wire:model="showModal" class="max-w-2xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <flux:heading size="lg">Export Logo Generation</flux:heading>
                <flux:button variant="ghost" size="sm" wire:click="closeModal" icon="x-mark" />
            </div>

            <div class="space-y-6">
                <!-- Export Format Selection -->
                <div>
                    <flux:field>
                        <flux:label>Export Format</flux:label>
                        <div class="grid grid-cols-1 gap-3 mt-2">
                            @foreach(['pdf' => 'PDF Document', 'csv' => 'CSV Spreadsheet', 'json' => 'JSON Data'] as $format => $label)
                                <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 {{ $exportType === $format ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                                    <input 
                                        type="radio" 
                                        wire:model.live="exportType" 
                                        value="{{ $format }}"
                                        class="mt-1 text-blue-600"
                                    >
                                    <div class="ml-3 flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $label }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $this->getFormatDescriptions()[$format] }}
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </flux:field>
                </div>

                <!-- Export Options -->
                <div class="space-y-4">
                    <flux:heading size="sm">Export Options</flux:heading>
                    
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:checkbox wire:model="includeDomains">Include Domain Status</flux:checkbox>
                        </flux:field>
                        
                        <flux:field>
                            <flux:checkbox wire:model="includeMetadata">Include Metadata</flux:checkbox>
                        </flux:field>
                        
                        @if($exportType !== 'csv')
                        <flux:field>
                            <flux:checkbox wire:model="includeLogos">Include Logo Images</flux:checkbox>
                        </flux:field>
                        @endif
                        
                        <flux:field>
                            <flux:checkbox wire:model="includeBranding">Include Branding</flux:checkbox>
                        </flux:field>
                    </div>
                </div>

                <!-- Advanced Settings -->
                <div class="space-y-4">
                    <flux:heading size="sm">Advanced Settings</flux:heading>
                    
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Template Style</flux:label>
                            <flux:select wire:model="template">
                                <option value="default">Default</option>
                                <option value="professional">Professional</option>
                            </flux:select>
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Expires In (Days)</flux:label>
                            <flux:select wire:model="expiresInDays">
                                <option value="1">1 Day</option>
                                <option value="7">7 Days</option>
                                <option value="14">14 Days</option>
                                <option value="30">30 Days</option>
                            </flux:select>
                        </flux:field>
                    </div>
                </div>

                <!-- Error and Success Messages -->
                @if($exportError)
                    <flux:callout variant="danger">
                        <flux:icon name="exclamation-triangle" />
                        {{ $exportError }}
                    </flux:callout>
                @endif

                @if($exportSuccess)
                    <flux:callout variant="success">
                        <flux:icon name="check-circle" />
                        {{ $exportSuccess }}
                    </flux:callout>
                @endif
            </div>

            <!-- Modal Actions -->
            <div class="flex flex-col gap-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 sm:flex-row sm:justify-end">
                <flux:button 
                    variant="ghost" 
                    wire:click="closeModal" 
                    :disabled="$isExporting"
                    class="w-full sm:w-auto"
                >
                    Cancel
                </flux:button>
                <flux:button 
                    variant="primary" 
                    wire:click="generateExport"
                    :disabled="$isExporting"
                    icon="{{ $isExporting ? 'arrow-path' : 'arrow-down-tray' }}"
                    class="w-full sm:w-auto"
                >
                    @if($isExporting)
                        <span wire:loading.delay class="animate-spin">
                            Generating...
                        </span>
                    @else
                        Generate Export
                    @endif
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
