{{-- Undo Toast Component --}}
@props([
    'message' => 'Action completed',
    'duration' => 5000,
])

<div x-data="{
    show: false,
    message: @js($message),
    operationId: null,
    timeoutId: null,

    init() {
        // Listen for show undo toast events
        window.addEventListener('show-undo-toast', (event) => {
            this.showToast(event.detail);
        });

        // Listen for hide undo toast events
        window.addEventListener('hide-undo-toast', (event) => {
            if (event.detail.operationId === this.operationId) {
                this.hideToast();
            }
        });
    },

    showToast(detail) {
        this.message = detail.message;
        this.operationId = detail.operationId;
        this.show = true;

        // Auto-hide after duration
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
        }

        this.timeoutId = setTimeout(() => {
            this.hideToast();
        }, detail.duration || {{ $duration }});
    },

    hideToast() {
        this.show = false;
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
            this.timeoutId = null;
        }
    },

    handleUndo() {
        // Dispatch undo event
        const optimisticUI = Alpine.store('optimisticUI') || window.Alpine?.$data(document.querySelector('[x-data*=optimisticUI]'));
        if (optimisticUI && this.operationId) {
            optimisticUI.undoDelete(this.operationId);
        }

        this.hideToast();
    }
}"
     x-show="show"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-2"
     class="fixed bottom-4 right-4 z-50 max-w-md"
     role="alert"
     aria-live="assertive">

    <div class="flex items-center justify-between px-4 py-3 bg-gray-900 dark:bg-gray-800 text-white rounded-lg shadow-lg border border-gray-700">
        {{-- Message --}}
        <div class="flex items-center space-x-3">
            <svg class="h-5 w-5 text-blue-400"
                 xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium" x-text="message"></span>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center space-x-2 ml-4">
            <button @click="handleUndo()"
                    class="px-3 py-1 text-sm font-medium text-blue-400 hover:text-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded transition-colors">
                Undo
            </button>
            <button @click="hideToast()"
                    class="p-1 text-gray-400 hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 rounded transition-colors">
                <svg class="h-4 w-4"
                     xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</div>
