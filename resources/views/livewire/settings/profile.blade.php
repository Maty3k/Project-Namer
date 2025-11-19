<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your profile information')">
        {{-- Profile Photo Section --}}
        <div class="my-6 w-full space-y-4">
            <div class="flex flex-col gap-4
                        sm:flex-row sm:items-start">
                {{-- Current Profile Photo --}}
                <div class="flex flex-col items-center gap-3
                            sm:items-start">
                    <flux:text class="font-semibold text-sm">{{ __('Profile Photo') }}</flux:text>
                    <div class="relative group">
                        @if(auth()->user()->profilePhotoUrl())
                            <img src="{{ auth()->user()->profilePhotoUrl() }}"
                                 alt="{{ auth()->user()->name }}"
                                 class="h-24 w-24 rounded-full object-cover border-[3px] border-gray-300 dark:border-gray-600 transition-all duration-200 group-hover:scale-105
                                        sm:h-28 sm:w-28">
                        @else
                            <div class="h-24 w-24 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center border-[3px] border-gray-300 dark:border-gray-600 transition-all duration-200 group-hover:scale-105
                                        sm:h-28 sm:w-28">
                                <span class="text-2xl font-medium text-zinc-900 dark:text-white
                                             sm:text-3xl">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Upload Controls --}}
                <div class="flex-1 space-y-4">
                    <div x-data="{ uploading: false, progress: 0 }"
                         x-on:livewire-upload-start="uploading = true"
                         x-on:livewire-upload-finish="uploading = false"
                         x-on:livewire-upload-error="uploading = false"
                         x-on:livewire-upload-progress="progress = $event.detail.progress">

                        <flux:field>
                            <flux:label for="profilePhoto">{{ __('Choose New Photo') }}</flux:label>
                            <flux:input
                                type="file"
                                wire:model="profilePhoto"
                                accept="image/*"
                                id="profilePhoto"
                                class="w-full"
                            />
                            <flux:description>
                                <span class="text-xs">{{ __('JPG, PNG, GIF, WebP, or AVIF. Max size 2MB.') }}</span>
                            </flux:description>
                            <flux:error name="profilePhoto" />
                        </flux:field>

                        {{-- Upload Progress --}}
                        <div x-show="uploading" class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                            <div class="bg-primary-600 h-2 transition-all duration-300 rounded-full"
                                 :style="`width: ${progress}%`"></div>
                        </div>

                        {{-- Preview --}}
                        @if ($profilePhoto)
                            <div class="flex flex-col gap-2
                                        sm:flex-row sm:items-center sm:gap-4">
                                <flux:text class="text-sm font-medium">{{ __('Preview:') }}</flux:text>
                                <img src="{{ $profilePhoto->temporaryUrl() }}"
                                     alt="Preview"
                                     class="h-16 w-16 rounded-full object-cover border-[3px] border-primary-500">
                                <div class="flex gap-2">
                                    <flux:button
                                        wire:click="updateProfilePhoto"
                                        variant="primary"
                                        size="sm"
                                        class="border-[3px] border-primary-700 dark:border-primary-300 hover:scale-105 active:scale-95 transition-all">
                                        {{ __('Upload Photo') }}
                                    </flux:button>
                                    <flux:button
                                        wire:click="$set('profilePhoto', null)"
                                        variant="ghost"
                                        size="sm"
                                        class="hover:scale-105 active:scale-95 transition-all">
                                        {{ __('Cancel') }}
                                    </flux:button>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Delete Photo Button --}}
                    @if(auth()->user()->profilePhotoUrl())
                        <div x-data="{ showDeleteModal: false }">
                            <flux:button
                                @click="showDeleteModal = true"
                                variant="danger"
                                size="sm"
                                class="border-[3px] border-red-700 dark:border-red-300 hover:scale-105 active:scale-95 transition-all">
                                {{ __('Remove Photo') }}
                            </flux:button>

                            {{-- Delete Confirmation Modal --}}
                            <flux:modal x-model="showDeleteModal" class="space-y-6">
                                <div>
                                    <flux:heading size="lg">{{ __('Remove Profile Photo') }}</flux:heading>
                                    <flux:subheading class="mt-2">
                                        {{ __('Are you sure you want to remove your profile photo? Your initials will be displayed instead.') }}
                                    </flux:subheading>
                                </div>

                                <div class="flex gap-2 justify-end">
                                    <flux:button variant="ghost" @click="showDeleteModal = false">
                                        {{ __('Cancel') }}
                                    </flux:button>
                                    <flux:button
                                        variant="danger"
                                        wire:click="deleteProfilePhoto"
                                        @click="showDeleteModal = false">
                                        {{ __('Remove Photo') }}
                                    </flux:button>
                                </div>
                            </flux:modal>
                        </div>
                    @endif

                    {{-- Success Messages --}}
                    <div x-data="{ show: false }"
                         @profile-photo-updated.window="show = true; setTimeout(() => show = false, 3000)"
                         x-show="show"
                         x-transition
                         class="text-sm font-medium text-green-600 dark:text-green-400">
                        {{ __('Profile photo updated successfully!') }}
                    </div>

                    <div x-data="{ show: false }"
                         @profile-photo-deleted.window="show = true; setTimeout(() => show = false, 3000)"
                         x-show="show"
                         x-transition
                         class="text-sm font-medium text-green-600 dark:text-green-400">
                        {{ __('Profile photo removed successfully!') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Separator --}}
        <div class="my-8 border-t border-gray-300 dark:border-gray-600"></div>

        {{-- Profile Information Form --}}
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
