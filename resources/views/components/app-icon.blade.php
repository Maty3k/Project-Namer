@php
    // Map contextual names to actual icon names
    $iconMap = [
        // Primary actions
        'delete' => 'trash',
        'edit' => 'pencil',
        'save' => 'check',
        'cancel' => 'x-mark',
        'add' => 'plus',
        'remove' => 'minus',
        'close' => 'x-mark',
        
        // Status indicators  
        'success' => 'check',
        'error' => 'x-mark',
        'warning' => 'exclamation-triangle',
        'info' => 'information-circle',
        'loading' => 'arrow-path',
        
        // Navigation & file operations
        'home' => 'home',
        'back' => 'arrow-left',
        'forward' => 'arrow-right',
        'download' => 'arrow-down-tray',
        'upload' => 'arrow-up-tray',
        'refresh' => 'arrow-path',
        
        // Content management
        'create' => 'plus',
        'copy' => 'document-duplicate',
        'move' => 'arrows-pointing-out',
        'archive' => 'archive-box',
        'restore' => 'arrow-uturn-left',
        
        // Settings & preferences
        'settings' => 'cog-6-tooth',
        'preferences' => 'adjustments-horizontal',
        'configure' => 'wrench-screwdriver',
        
        // Communication
        'share' => 'share',
        'email' => 'envelope',
        'message' => 'chat-bubble-left',
        'notification' => 'bell',
        
        // View modes
        'view' => 'eye',
        'hide' => 'eye-slash',
        'expand' => 'arrows-pointing-out',
        'collapse' => 'arrows-pointing-in',
        'search' => 'magnifying-glass',
        'filter' => 'funnel',
        'sort' => 'bars-3-bottom-left',
    ];
    
    // Resolve icon name
    $resolvedIcon = $iconMap[$name] ?? $name;
    
    // Size classes mapping
    $sizeClasses = [
        'xs' => 'w-3 h-3',
        'sm' => 'w-4 h-4', 
        'md' => 'w-5 h-5',
        'lg' => 'w-6 h-6',
        'xl' => 'w-8 h-8',
        '2xl' => 'w-10 h-10',
    ];
    
    // Variant color classes
    $variantClasses = [
        'success' => 'text-green-600 dark:text-green-400',
        'error' => 'text-red-600 dark:text-red-400', 
        'warning' => 'text-yellow-600 dark:text-yellow-400',
        'info' => 'text-blue-600 dark:text-blue-400',
        'muted' => 'text-gray-400 dark:text-gray-500',
    ];
    
    // Build classes
    $classes = collect([
        $sizeClasses[$size] ?? $sizeClasses['md'],
        $variantClasses[$variant] ?? null,
        $loading ? 'animate-spin' : null,
        $class ?? null,
    ])->filter()->join(' ');
    
    // Get stroke width from attributes
    $strokeWidth = $attributes['stroke-width'] ?? '1.5';
    
    // Handle accessibility attributes
    $ariaLabel = $attributes['aria-label'] ?? null;
    $ariaHidden = $attributes['aria-hidden'] ?? ($ariaLabel ? null : 'true');
    $role = $ariaLabel ? 'img' : null;
    
    // Determine icon path based on size and style
    $iconSizePath = match($size) {
        'xs', 'sm' => '16',
        'md' => '20', 
        'lg', 'xl', '2xl' => '24',
        default => '20'
    };
    
    $iconPath = "node_modules/heroicons/{$iconSizePath}/{$style}/{$resolvedIcon}.svg";
    
    // Check if file exists, fallback to question-mark-circle if not
    if (!file_exists(base_path($iconPath))) {
        $iconPath = "node_modules/heroicons/24/outline/question-mark-circle.svg";
    }
    
    // Load and process the SVG
    $svgContent = '';
    if (file_exists(base_path($iconPath))) {
        $svgContent = file_get_contents(base_path($iconPath));
        
        // Remove the opening <svg> tag and closing </svg> tag to extract just the paths
        $svgContent = preg_replace('/<svg[^>]*>/', '', $svgContent);
        $svgContent = str_replace('</svg>', '', $svgContent);
        $svgContent = trim($svgContent);
    }
@endphp

<svg 
    {{ $attributes->merge(['class' => $classes]) }}
    @if($style === 'outline')
        fill="none" 
        stroke="currentColor" 
        stroke-width="{{ $strokeWidth }}"
    @else
        fill="currentColor"
    @endif
    viewBox="0 0 24 24"
    xmlns="http://www.w3.org/2000/svg"
    @if($role) role="{{ $role }}" @endif
    @if($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
    @if($ariaHidden) aria-hidden="{{ $ariaHidden }}" @endif
>
    {!! $svgContent !!}
</svg>