<div>
    {{-- Test simplified version --}}
    @if($showResults)
        <flux:tab.panel name="results" class="flex-1">
            <div>Test content</div>
        </flux:tab.panel>
    @endif

    @if($showLogoGeneration && $this->currentLogoGeneration)
        <flux:tab.panel name="logos" class="flex-1">
            <div>Logo content</div>
        </flux:tab.panel>
    @endif
</div>