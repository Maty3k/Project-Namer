{{-- Name Details Modal Content --}}
@php
$dataArray = is_array($data) ? $data : ['name' => $data];
$name = $dataArray['name'] ?? 'Unknown';
@endphp
<div class="space-y-6">
    {{-- Business Name Header --}}
    <div class="text-center">
        <h4 class="text-xl font-bold text-gray-900 dark:text-gray-100">
            {{ $name }}
        </h4>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {{ $dataArray['length'] ?? strlen($name) }} characters
        </p>
    </div>

    {{-- Brandability Score --}}
    @if(isset($dataArray['brandability_score']))
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Brandability Score</span>
                <span class="text-lg font-bold {{ $dataArray['brandability_score'] >= 80 ? 'text-green-600' : ($dataArray['brandability_score'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $dataArray['brandability_score'] }}/100
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-gradient-to-r from-red-500 via-yellow-500 to-green-500 h-2 rounded-full" 
                     style="width: {{ $dataArray['brandability_score'] }}%"></div>
            </div>
        </div>
    @endif

    {{-- Domain Availability --}}
    @if(!empty($dataArray['domains']))
        <div>
            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Domain Availability</h5>
            <div class="space-y-2">
                @foreach($dataArray['domains'] as $domain => $info)
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded">
                        <span class="text-sm font-medium">{{ $domain }}</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ ($info['available'] ?? false) ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
                            {{ ($info['available'] ?? false) ? 'Available' : 'Taken' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Alternative Names --}}
    @if(!empty($dataArray['alternatives']))
        <div>
            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Alternative Suggestions</h5>
            <div class="flex flex-wrap gap-2">
                @foreach($dataArray['alternatives'] as $alternative)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                        {{ $alternative }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Trademark Status --}}
    @if(isset($dataArray['trademark_status']))
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 {{ $dataArray['trademark_status'] === 'clear' ? 'text-green-600' : 'text-yellow-600' }} mr-2" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="{{ $dataArray['trademark_status'] === 'clear' ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' : 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z' }}">
                    </path>
                </svg>
                <div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Trademark Status</span>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        {{ $dataArray['trademark_status'] === 'clear' ? 'No obvious trademark conflicts found' : 'Potential trademark conflicts detected' }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Action Buttons --}}
    <div class="flex space-x-3">
        <flux:button 
            wire:click="generateLogos('{{ $name }}')"
            variant="primary" 
            class="flex-1">
            Generate Logos
        </flux:button>
        <flux:button 
            wire:click="showDomainInfo('{{ array_key_first($dataArray['domains'] ?? []) ?: $name . '.com' }}')"
            variant="outline" 
            class="flex-1">
            Domain Info
        </flux:button>
    </div>
</div>