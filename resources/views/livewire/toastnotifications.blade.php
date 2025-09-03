<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public array $toasts = [];
    
    #[On('show-toast')]
    public function showToast($data)
    {
        $toast = [
            'id' => (string) uniqid(),
            'message' => (string) ($data['message'] ?? 'Operation completed'),
            'type' => (string) ($data['type'] ?? 'info'), // success, error, warning, info
            'duration' => (int) ($data['duration'] ?? 5000),
            'timestamp' => (string) now()->toDateTimeString()
        ];
        
        $this->toasts[] = $toast;
        
        // Auto-remove toast after duration
        $this->dispatch('auto-remove-toast', ['id' => $toast['id'], 'duration' => $toast['duration']]);
    }
    
    public function removeToast(string $toastId): void
    {
        $this->toasts = array_values(array_filter($this->toasts, fn($toast) => $toast['id'] !== $toastId));
    }
    
    public function clearAllToasts(): void
    {
        $this->toasts = [];
    }
    
    protected function serializeProperty($property)
    {
        // Ensure toasts array contains only JSON-serializable data
        if ($property === $this->toasts) {
            return array_map(fn($toast) => [
                'id' => (string) $toast['id'],
                'message' => (string) $toast['message'],
                'type' => (string) $toast['type'],
                'duration' => (int) $toast['duration'],
                'timestamp' => (string) $toast['timestamp'],
            ], $property);
        }
        
        return parent::serializeProperty($property);
    }
}; ?>

<div class="fixed z-50">
    <!-- Main toasts container -->
    <div class="fixed top-4 right-4 space-y-4" style="max-width: 400px;">
        @foreach($toasts as $toast)
            <div 
                x-data="{ show: true }" 
                x-show="show"
                x-transition:enter="transform ease-out duration-300 transition"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                x-init="setTimeout(() => show = false, {{ $toast['duration'] }})"
                @auto-remove-toast.window="if ($event.detail.id === '{{ $toast['id'] }}') setTimeout(() => { Livewire.find('{{ $_instance->id }}').call('removeToast', '{{ $toast['id'] }}') }, $event.detail.duration)"
                class="relative w-full max-w-sm mx-auto bg-white rounded-lg shadow-lg overflow-hidden
                    @if($toast['type'] === 'success') border-l-4 border-green-500 @endif
                    @if($toast['type'] === 'error') border-l-4 border-red-500 @endif
                    @if($toast['type'] === 'warning') border-l-4 border-yellow-500 @endif
                    @if($toast['type'] === 'info') border-l-4 border-blue-500 @endif">
                
                <div class="p-4">
                    <div class="flex items-start">
                        <!-- Toast Icon -->
                        <div class="flex-shrink-0 mr-3">
                            @if($toast['type'] === 'success')
                                <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @elseif($toast['type'] === 'error')
                                <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            @elseif($toast['type'] === 'warning')
                                <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                        
                        <!-- Toast Content -->
                        <div class="flex-grow">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $toast['message'] }}
                            </p>
                            
                            @if($toast['timestamp'])
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ \Carbon\Carbon::parse($toast['timestamp'])->format('g:i A') }}
                                </p>
                            @endif
                        </div>
                        
                        <!-- Close Button -->
                        <div class="flex-shrink-0 ml-3">
                            <button 
                                wire:click="removeToast('{{ $toast['id'] }}')"
                                class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <span class="sr-only">Close</span>
                                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Progress bar for auto-dismiss -->
                <div class="h-1 bg-gray-200">
                    <div 
                        class="h-full bg-gradient-to-r
                            @if($toast['type'] === 'success') from-green-500 to-green-600 @endif
                            @if($toast['type'] === 'error') from-red-500 to-red-600 @endif
                            @if($toast['type'] === 'warning') from-yellow-500 to-yellow-600 @endif
                            @if($toast['type'] === 'info') from-blue-500 to-blue-600 @endif"
                        x-data="{ width: 100 }"
                        x-init="
                            const interval = setInterval(() => {
                                width = width - (100 / ({{ $toast['duration'] }} / 100));
                                if (width <= 0) clearInterval(interval);
                            }, 100)
                        "
                        :style="`width: ${width}%`">
                    </div>
                </div>
            </div>
        @endforeach
        
        <!-- Clear All Button (when multiple toasts) -->
        @if(count($toasts) > 1)
            <div class="text-right">
                <button 
                    wire:click="clearAllToasts"
                    class="text-xs text-gray-500 hover:text-gray-700 underline">
                    Clear all notifications
                </button>
            </div>
        @endif
    </div>

    <!-- Success Toast Examples (for testing) -->
    @if(app()->environment('local'))
        <div class="fixed bottom-4 left-4 space-x-2">
            <button onclick="Livewire.dispatch('show-toast', { message: 'Success! Logo generated successfully.', type: 'success' })"
                    class="px-3 py-1 bg-green-500 text-white text-xs rounded">Test Success</button>
            <button onclick="Livewire.dispatch('show-toast', { message: 'Error! Generation failed.', type: 'error' })"
                    class="px-3 py-1 bg-red-500 text-white text-xs rounded">Test Error</button>
            <button onclick="Livewire.dispatch('show-toast', { message: 'Warning! High system load detected.', type: 'warning' })"
                    class="px-3 py-1 bg-yellow-500 text-white text-xs rounded">Test Warning</button>
            <button onclick="Livewire.dispatch('show-toast', { message: 'Info: Checking logo status...', type: 'info' })"
                    class="px-3 py-1 bg-blue-500 text-white text-xs rounded">Test Info</button>
        </div>
    @endif
</div>
