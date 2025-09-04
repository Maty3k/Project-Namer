<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public array $toasts = [];
    
    #[On('show-toast')]
    public function showToast(...$params)
    {
        // Handle both array format and individual parameter format
        if (count($params) === 1 && is_array($params[0])) {
            // Array format: ['message' => '...', 'type' => '...', 'duration' => ...]
            $data = $params[0];
            $message = $data['message'] ?? 'Operation completed';
            $type = $data['type'] ?? 'info';
            $duration = $data['duration'] ?? 5000;
        } else {
            // Individual parameter format: message, type, duration
            $message = $params[0] ?? 'Operation completed';
            $type = $params[1] ?? 'info';
            $duration = $params[2] ?? 5000;
        }
        
        $toast = [
            'id' => (string) uniqid(),
            'message' => (string) $message,
            'type' => (string) $type, // success, error, warning, info
            'duration' => (int) $duration,
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
                @auto-remove-toast.window="if ($event.detail.id === '{{ $toast['id'] }}') setTimeout(() => { $wire.call('removeToast', '{{ $toast['id'] }}') }, $event.detail.duration)"
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
                                <x-app-icon name="success" size="lg" variant="success" aria-label="Success" />
                            @elseif($toast['type'] === 'error')
                                <x-app-icon name="error" size="lg" variant="error" aria-label="Error" />
                            @elseif($toast['type'] === 'warning')
                                <x-app-icon name="warning" size="lg" variant="warning" aria-label="Warning" />
                            @else
                                <x-app-icon name="info" size="lg" variant="info" aria-label="Information" />
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
                                <x-app-icon name="close" size="sm" />
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
            <button onclick="Livewire.dispatch('show-toast', { message: 'Success! Logo generated successfully.', type: 'success', duration: 4000 })"
                    class="px-3 py-1 bg-green-500 text-white text-xs rounded">Test Success</button>
            <button onclick="Livewire.dispatch('show-toast', { message: 'Error! Generation failed.', type: 'error', duration: 8000 })"
                    class="px-3 py-1 bg-red-500 text-white text-xs rounded">Test Error</button>
            <button onclick="Livewire.dispatch('show-toast', { message: 'Warning! High system load detected.', type: 'warning', duration: 6000 })"
                    class="px-3 py-1 bg-yellow-500 text-white text-xs rounded">Test Warning</button>
            <button onclick="Livewire.dispatch('show-toast', { message: 'Info: Checking logo status...', type: 'info', duration: 5000 })"
                    class="px-3 py-1 bg-blue-500 text-white text-xs rounded">Test Info</button>
        </div>
    @endif
</div>
