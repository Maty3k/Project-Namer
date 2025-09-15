@php
    $userTheme = \App\Helpers\ThemeHelper::getCurrentUserTheme();
@endphp

<div class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
    <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
</div>
<div class="ms-1 grid flex-1 text-start text-sm">
    <span class="mb-0.5 truncate leading-tight {{ $userTheme ? 'theme-text-contrast' : 'font-semibold' }}"
          @if($userTheme)
              style="color: {{ $userTheme->text_color }} !important;"
          @endif>Laravel Starter Kit</span>
</div>
