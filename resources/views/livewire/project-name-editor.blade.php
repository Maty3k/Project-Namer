<div>
    @if($editing)
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <flux:field class="flex-1">
                <flux:input
                    wire:model="name"
                    wire:keydown.enter="save"
                    wire:keydown.escape="cancel"
                    class="text-2xl sm:text-3xl font-bold bg-transparent border-0 border-b-2 border-accent focus:ring-0 focus:border-accent"
                    placeholder="Project name"
                    autofocus
                />
                <flux:error name="name" />
            </flux:field>

            <div class="flex gap-2 sm:flex-shrink-0">
                <button
                    wire:click="save"
                    type="button"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-accent hover:bg-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>

                <button
                    wire:click="cancel"
                    type="button"
                    class="inline-flex items-center px-3 py-2 border border-zinc-300 text-sm leading-4 font-medium rounded-md text-zinc-700 bg-white hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </div>
                </button>
            </div>
        </div>
    @else
        <div class="group flex items-center gap-3">
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white flex-1">
                {{ $project->name }}
            </h1>

            <button
                wire:click="startEdit"
                type="button"
                class="opacity-0 group-hover:opacity-100 transition-opacity inline-flex items-center px-2 py-1 border border-transparent text-sm font-medium rounded-md text-zinc-600 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
                Edit
            </button>
        </div>
    @endif
</div>