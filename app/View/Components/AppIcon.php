<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AppIcon extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $name,
        public string $size = 'md',
        public string $style = 'outline',
        public ?string $variant = null,
        public bool $loading = false,
        public ?string $class = null,
    ) {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.app-icon');
    }
}
