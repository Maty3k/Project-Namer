{{-- Confirmation Modal Content --}}
<div class="space-y-6">
    {{-- Icon and Header --}}
    <div class="text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full {{ ($data['type'] ?? 'warning') === 'danger' ? 'bg-red-100 dark:bg-red-900' : 'bg-yellow-100 dark:bg-yellow-900' }}">
            @if(($data['type'] ?? 'warning') === 'danger')
                {{-- Danger Icon --}}
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            @else
                {{-- Warning Icon --}}
                <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            @endif
        </div>
        <h4 class="mt-3 text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ $data['title'] ?? 'Confirm Action' }}
        </h4>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ $data['message'] ?? 'Are you sure you want to proceed with this action?' }}
        </p>
    </div>

    {{-- Additional Details --}}
    @if(!empty($data['details']))
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ $data['detailsTitle'] ?? 'Details' }}
            </h5>
            @if(is_array($data['details']))
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    @foreach($data['details'] as $detail)
                        <li class="flex items-center">
                            <svg class="w-3 h-3 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $detail }}
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $data['details'] }}
                </p>
            @endif
        </div>
    @endif

    {{-- Warning Note --}}
    @if(!empty($data['warning']))
        <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <div class="flex">
                <svg class="w-5 h-5 text-yellow-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Warning
                    </p>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                        {{ $data['warning'] }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Action Buttons --}}
    <div class="flex space-x-3">
        @if(($data['type'] ?? 'warning') === 'danger')
            {{-- Danger Actions --}}
            <flux:button 
                wire:click="confirmAction('{{ $data['confirmAction'] ?? 'confirm' }}')"
                variant="danger" 
                class="flex-1">
                {{ $data['confirmText'] ?? 'Delete' }}
            </flux:button>
            <flux:button 
                wire:click="closeModal"
                variant="outline" 
                class="flex-1">
                {{ $data['cancelText'] ?? 'Cancel' }}
            </flux:button>
        @else
            {{-- Standard Actions --}}
            <flux:button 
                wire:click="confirmAction('{{ $data['confirmAction'] ?? 'confirm' }}')"
                variant="primary" 
                class="flex-1">
                {{ $data['confirmText'] ?? 'Confirm' }}
            </flux:button>
            <flux:button 
                wire:click="closeModal"
                variant="outline" 
                class="flex-1">
                {{ $data['cancelText'] ?? 'Cancel' }}
            </flux:button>
        @endif
    </div>

    {{-- Keyboard Shortcuts Hint --}}
    <div class="text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Press <kbd class="px-1 py-0.5 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">Enter</kbd> 
            to confirm or <kbd class="px-1 py-0.5 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">Esc</kbd> 
            to cancel
        </p>
    </div>
</div>