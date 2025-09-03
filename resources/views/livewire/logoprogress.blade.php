<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;

new class extends Component {
    public $logoGenerationId;
    public $status = null;
    
    public function mount($logoGenerationId)
    {
        $this->logoGenerationId = $logoGenerationId;
        $this->loadStatus();
    }
    
    public function loadStatus()
    {
        if (!$this->logoGenerationId) return;
        
        try {
            $response = Http::get("/api/logos/{$this->logoGenerationId}/status");
            
            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                $this->status = $data;
            }
        } catch (Exception) {
            $this->status = [
                'status' => 'error',
                'message' => 'Unable to check generation status. Please refresh the page.'
            ];
        }
    }
    
    public function retry()
    {
        if (!$this->logoGenerationId) return;
        
        try {
            $response = Http::post("/api/logos/{$this->logoGenerationId}/retry");
            
            if ($response->successful()) {
                $this->loadStatus();
                $this->dispatch('show-toast', [
                    'message' => 'Logo generation restarted successfully!',
                    'type' => 'success'
                ]);
            }
        } catch (Exception) {
            $this->dispatch('show-toast', [
                'message' => 'Unable to retry generation. Please try again.',
                'type' => 'error'
            ]);
        }
    }
    
    protected function serializeProperty($property)
    {
        if ($property === $this->status && is_array($property)) {
            return array_map(fn($value) => is_string($value) || is_numeric($value) || is_bool($value) ? $value : (string) $value, $property);
        }
        
        return parent::serializeProperty($property);
    }
}; ?>

<div class="w-full max-w-2xl mx-auto p-6 bg-white rounded-lg shadow-lg">
    @if($status)
        <div class="space-y-4">
            <!-- Status Header -->
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">
                    Logo Generation Progress
                </h3>
                
                @if($status['status'] === 'processing')
                    <div class="flex items-center text-blue-600">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium">Processing...</span>
                    </div>
                @endif
            </div>

            <!-- Progress Bar (for processing status) -->
            @if($status['status'] === 'processing' && isset($status['progress']))
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-500 ease-out"
                         style="width: {{ $status['progress'] }}%"></div>
                </div>
                
                <div class="flex justify-between text-sm text-gray-600">
                    <span>{{ $status['progress'] }}% complete</span>
                    <span>{{ $status['logos_completed'] ?? 0 }}/{{ $status['total_logos_requested'] ?? 0 }} logos</span>
                </div>
            @endif

            <!-- Status Message -->
            <div class="p-4 rounded-lg 
                @if($status['status'] === 'completed') bg-green-50 border border-green-200 @endif
                @if($status['status'] === 'processing') bg-blue-50 border border-blue-200 @endif
                @if($status['status'] === 'failed') bg-red-50 border border-red-200 @endif
                @if($status['status'] === 'partial') bg-yellow-50 border border-yellow-200 @endif
                @if($status['status'] === 'pending') bg-gray-50 border border-gray-200 @endif">
                
                <div class="flex items-start">
                    <!-- Status Icon -->
                    <div class="flex-shrink-0 mr-3 mt-0.5">
                        @if($status['status'] === 'completed')
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @elseif($status['status'] === 'failed')
                            <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        @elseif($status['status'] === 'partial')
                            <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </div>
                    
                    <!-- Status Content -->
                    <div class="flex-grow">
                        <p class="text-sm font-medium 
                            @if($status['status'] === 'completed') text-green-800 @endif
                            @if($status['status'] === 'processing') text-blue-800 @endif
                            @if($status['status'] === 'failed') text-red-800 @endif
                            @if($status['status'] === 'partial') text-yellow-800 @endif
                            @if($status['status'] === 'pending') text-gray-800 @endif">
                            {{ $status['message'] ?? 'Processing your request...' }}
                        </p>
                        
                        <!-- Estimated Time (for processing) -->
                        @if($status['status'] === 'processing' && isset($status['estimated_time_remaining']))
                            <p class="text-xs text-gray-600 mt-1">
                                Estimated time remaining: 
                                @if($status['estimated_time_remaining'] < 60)
                                    {{ $status['estimated_time_remaining'] }} seconds
                                @else
                                    {{ ceil($status['estimated_time_remaining'] / 60) }} minutes
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center pt-2">
                @if($status['can_retry'] ?? false)
                    <flux:button 
                        wire:click="retry" 
                        variant="outline"
                        size="sm">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                        </svg>
                        Retry Generation
                    </flux:button>
                @endif
                
                @if($status['status'] === 'completed')
                    <flux:button 
                        href="/logos/{{ $logoGenerationId }}"
                        variant="primary"
                        size="sm">
                        View Your Logos
                        <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </flux:button>
                @endif
                
                <div class="text-xs text-gray-500">
                    <button wire:click="loadStatus" class="hover:text-gray-700">
                        Refresh Status
                    </button>
                </div>
            </div>
        </div>
    @else
        <!-- Loading state -->
        <div class="flex items-center justify-center py-8">
            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="ml-3 text-gray-600">Loading generation status...</span>
        </div>
    @endif
</div>
