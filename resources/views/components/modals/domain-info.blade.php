{{-- Domain Information Modal Content --}}
@php
$dataArray = is_array($data) ? $data : ['domain' => $data];
$domain = $dataArray['domain'] ?? 'Unknown';
@endphp
<div class="space-y-6">
    {{-- Domain Header --}}
    <div class="text-center">
        <h4 class="text-xl font-bold text-gray-900 dark:text-gray-100">
            {{ $domain }}
        </h4>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Domain Information & Pricing
        </p>
    </div>

    {{-- Availability Status --}}
    <div class="bg-{{ ($dataArray['status'] ?? 'unknown') === 'available' ? 'green' : 'red' }}-50 dark:bg-{{ ($dataArray['status'] ?? 'unknown') === 'available' ? 'green' : 'red' }}-900 rounded-lg p-4">
        <div class="flex items-center">
            <svg class="w-6 h-6 text-{{ ($dataArray['status'] ?? 'unknown') === 'available' ? 'green' : 'red' }}-600 mr-3" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                @if(($dataArray['status'] ?? 'unknown') === 'available')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                @endif
            </svg>
            <div>
                <h5 class="text-lg font-semibold text-{{ ($dataArray['status'] ?? 'unknown') === 'available' ? 'green' : 'red' }}-800 dark:text-{{ ($dataArray['status'] ?? 'unknown') === 'available' ? 'green' : 'red' }}-200">
                    {{ ($dataArray['status'] ?? 'unknown') === 'available' ? 'Available for Registration' : 'Domain Taken' }}
                </h5>
                <p class="text-sm text-{{ ($dataArray['status'] ?? 'unknown') === 'available' ? 'green' : 'red' }}-700 dark:text-{{ ($dataArray['status'] ?? 'unknown') === 'available' ? 'green' : 'red' }}-300">
                    {{ ($dataArray['status'] ?? 'unknown') === 'available' ? 'This domain can be registered' : 'This domain is already registered' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Pricing Information --}}
    @if(($dataArray['status'] ?? 'unknown') === 'available')
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Pricing Information</h5>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Registration Price</span>
                    <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $dataArray['price'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Renewal Price</span>
                    <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $dataArray['renewal_price'] ?? 'N/A' }}</p>
                </div>
            </div>
            @if(isset($dataArray['registrar']))
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <span class="text-xs text-gray-600 dark:text-gray-400">Recommended Registrar</span>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $dataArray['registrar'] }}</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Related Domains --}}
    @if(!empty($dataArray['related_domains']))
        <div>
            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Related Domains</h5>
            <div class="space-y-2">
                @foreach($dataArray['related_domains'] as $domain => $status)
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded">
                        <span class="text-sm font-medium">{{ $domain }}</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $status === 'available' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
                            {{ $status === 'available' ? 'Available' : 'Taken' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Features & Benefits --}}
    <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4">
        <h5 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">What's Included</h5>
        <ul class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
            <li class="flex items-center">
                <svg class="w-3 h-3 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                Free WHOIS privacy protection
            </li>
            <li class="flex items-center">
                <svg class="w-3 h-3 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                DNS management tools
            </li>
            <li class="flex items-center">
                <svg class="w-3 h-3 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                24/7 customer support
            </li>
        </ul>
    </div>

    {{-- Action Buttons --}}
    @if(($dataArray['status'] ?? 'unknown') === 'available')
        <div class="flex space-x-3">
            <flux:button variant="primary" class="flex-1">
                Register Domain
            </flux:button>
            <flux:button variant="outline" class="flex-1">
                Check Other TLDs
            </flux:button>
        </div>
    @else
        <div class="flex space-x-3">
            <flux:button variant="outline" class="flex-1">
                Check Alternatives
            </flux:button>
            <flux:button variant="outline" class="flex-1">
                WHOIS Lookup
            </flux:button>
        </div>
    @endif
</div>