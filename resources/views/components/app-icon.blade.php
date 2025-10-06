@props([
    'name' => 'question-mark-circle',
    'size' => 'md',
    'style' => 'outline',
    'variant' => null,
    'loading' => false,
    'class' => null,
])

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
        'info' => 'text-zinc-600 dark:text-zinc-400',
        'muted' => 'text-zinc-400 dark:text-zinc-500',
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

    // Heroicons structure: 16px and 20px only have solid, 24px has both outline and solid
    $iconStyle = $style;
    if (in_array($iconSizePath, ['16', '20']) && $iconStyle === 'outline') {
        // For 16px and 20px, only solid exists, so fallback to 24px outline
        $iconSizePath = '24';
    }

    $iconPath = "node_modules/heroicons/{$iconSizePath}/{$iconStyle}/{$resolvedIcon}.svg";

    // Check if file exists, try fallback strategies
    if (!file_exists(base_path($iconPath))) {
        // Fallback 1: Try 24px outline if we were looking for a smaller outline
        if ($iconSizePath !== '24' && $iconStyle === 'outline') {
            $fallbackPath = "node_modules/heroicons/24/outline/{$resolvedIcon}.svg";
            if (file_exists(base_path($fallbackPath))) {
                $iconPath = $fallbackPath;
            }
        }

        // Fallback 2: Try 24px solid if outline doesn't exist
        if (!file_exists(base_path($iconPath))) {
            $fallbackPath = "node_modules/heroicons/24/solid/{$resolvedIcon}.svg";
            if (file_exists(base_path($fallbackPath))) {
                $iconPath = $fallbackPath;
                $iconStyle = 'solid'; // Update style for correct rendering
            }
        }

        // Final fallback: question-mark-circle
        if (!file_exists(base_path($iconPath))) {
            $iconPath = "node_modules/heroicons/24/outline/question-mark-circle.svg";
            $iconStyle = 'outline';
        }
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
    @if($iconStyle === 'outline')
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