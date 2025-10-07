<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading=" __('Update the appearance settings for your account')">
        <div class="flex items-center space-x-2" x-data>
            <flux:button
                @click="$flux.appearance = 'light'"
                ::variant="$flux.appearance === 'light' ? 'primary' : 'ghost'"
                size="sm"
                icon="sun"
            >
                {{ __('Light') }}
            </flux:button>

            <flux:button
                @click="$flux.appearance = 'dark'"
                ::variant="$flux.appearance === 'dark' ? 'primary' : 'ghost'"
                size="sm"
                icon="moon"
            >
                {{ __('Dark') }}
            </flux:button>

            <flux:button
                @click="$flux.appearance = 'system'"
                ::variant="$flux.appearance === 'system' ? 'primary' : 'ghost'"
                size="sm"
                icon="computer-desktop"
            >
                {{ __('System') }}
            </flux:button>
        </div>
    </x-settings.layout>
</section>
