<div class="w-full max-w-full mx-auto p-1
            sm:max-w-2xl sm:p-4
            md:p-6">
    <div class="rounded-lg shadow-lg transition-all duration-300 bg-white dark:bg-zinc-900 p-3
                sm:p-6
                md:p-8">
        <div class="text-center mb-4
                    sm:mb-8">
            <h1 class="font-bold mb-1 text-zinc-900 dark:text-zinc-100 text-lg
                       sm:text-2xl
                       md:text-3xl">
                Create New Project
            </h1>
            <p class="text-zinc-600 dark:text-zinc-400 text-xs
                      sm:text-base">
                Describe your business idea to get started with name generation
            </p>
        </div>

        <form wire:submit="createProject" class="space-y-3
                                                 sm:space-y-6">
            <div>
                <flux:field>
                    <flux:label for="description">Describe your project</flux:label>
                    <flux:textarea
                        id="description"
                        wire:model.live="description"
                        placeholder="Tell us about your business idea, target market, and what makes it unique..."
                        rows="6"
                        maxlength="2000"
                        class="w-full text-sm sm:text-base"
                    />
                    <flux:description>
                        <span class="text-[10px] sm:text-xs">{{ strlen($description) }} / 2000 characters</span>
                    </flux:description>
                    <flux:error name="description" />
                </flux:field>
            </div>

            <div class="flex flex-col gap-2
                        sm:flex-row sm:justify-between sm:items-center">
                <div class="text-[10px] text-zinc-500 dark:text-zinc-400 text-center
                            sm:text-sm sm:text-left">
                    Your project will be saved automatically
                </div>
                <flux:button
                    type="submit"
                    variant="primary"
                    :disabled="strlen(trim($description)) < 10"
                    class="w-full text-sm py-2
                           sm:w-auto sm:text-base sm:py-auto"
                >
                    Save & Generate Names
                </flux:button>
            </div>
        </form>
    </div>
</div>