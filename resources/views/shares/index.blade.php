<x-layouts.app title="My Shares">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('My Shares') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">Share Management</h3>
                    @if(Auth::user())
                        @php
                            $shares = Auth::user()->shares()->latest()->get();
                        @endphp
                        @if($shares->count() > 0)
                            @foreach($shares as $share)
                                <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded">
                                    <h4 class="font-semibold">{{ $share->title ?: 'Untitled Share' }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $share->share_type }} - Created {{ $share->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            @endforeach
                        @else
                            <p class="text-gray-600 dark:text-gray-400">No shares yet.</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>