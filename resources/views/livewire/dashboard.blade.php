<div class="max-w-2xl mx-auto p-6">
    <div class="rounded-lg shadow-lg p-8 transition-all duration-300 themed-create-box"
         @php
             $userTheme = \App\Helpers\ThemeHelper::getCurrentUserTheme();
         @endphp
         @if($userTheme)
             style="background-color: {{ $userTheme->background_color }};
                    box-shadow: 0 10px 25px {{ $userTheme->primary_color }}20;
                    color: {{ $userTheme->text_color }};"
         @else
             class="bg-white dark:bg-gray-900"
         @endif>
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold mb-2"
                @if($userTheme)
                    style="color: {{ $userTheme->text_color }};"
                @else
                    class="text-gray-900 dark:text-white"
                @endif>
                Create New Project
            </h1>
            <p @if($userTheme)
                   style="color: {{ $userTheme->text_color }}; opacity: 0.8;"
               @else
                   class="text-gray-600 dark:text-gray-400"
               @endif>
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
                <div class="text-sm"
                     @if($userTheme)
                         style="color: {{ $userTheme->text_color }}; opacity: 0.7;"
                     @else
                         class="text-gray-500 dark:text-gray-400"
                     @endif>
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