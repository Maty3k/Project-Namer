<div class="max-w-2xl mx-auto p-6">
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                Create New Project
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Describe your business idea to get started with name generation
            </p>
        </div>

        <form wire:submit="createProject" class="space-y-6">
            <div>
                <flux:field>
                    <flux:label for="description">Describe your project</flux:label>
                    <flux:textarea
                        id="description"
                        wire:model.live="description"
                        placeholder="Tell us about your business idea, target market, and what makes it unique..."
                        rows="8"
                        maxlength="2000"
                        class="w-full"
                    />
                    <flux:description>
                        <span>{{ strlen($description) }} / 2000 characters</span>
                    </flux:description>
                    <flux:error name="description" />
                </flux:field>
            </div>

            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Your project will be saved automatically
                </div>
                <flux:button 
                    type="submit"
                    variant="primary"
                    :disabled="strlen(trim($description)) < 10"
                >
                    Save & Generate Names
                </flux:button>
            </div>
        </form>
    </div>
</div>