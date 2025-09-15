<?php

declare(strict_types=1);

// Enhanced Blade syntax checker
$file = '/Users/anamariaradulescu/Herd/Project-Namer/resources/views/livewire/name-generator-dashboard.blade.php';
$content = file_get_contents($file);

if (! $content) {
    echo "Could not read file\n";
    exit(1);
}

$lines = explode("\n", $content);
$stack = [];
$errors = [];

foreach ($lines as $lineNum => $line) {
    $lineNum++; // 1-based line numbers

    // Match @if statements
    if (preg_match('/@if\s*\(/', $line)) {
        $stack[] = ['type' => 'if', 'line' => $lineNum];
    }

    // Match @elseif statements
    if (preg_match('/@elseif\s*\(/', $line)) {
        if (empty($stack) || end($stack)['type'] !== 'if') {
            $errors[] = "Line $lineNum: @elseif without matching @if";
        } else {
            // Update the type to elseif to track state
            $stack[count($stack) - 1]['type'] = 'elseif';
        }
    }

    // Match @else statements
    if (preg_match('/@else\s*$/', $line)) {
        if (empty($stack) || ! in_array(end($stack)['type'], ['if', 'elseif'])) {
            $errors[] = "Line $lineNum: @else without matching @if or @elseif";
        } else {
            $stack[count($stack) - 1]['type'] = 'else';
        }
    }

    // Match @endif statements
    if (preg_match('/@endif/', $line)) {
        if (empty($stack)) {
            $errors[] = "Line $lineNum: @endif without matching @if";
        } else {
            array_pop($stack);
        }
    }
}

// Check for unclosed @if statements
foreach ($stack as $unclosed) {
    $errors[] = "Line {$unclosed['line']}: Unclosed @if statement";
}

if (empty($errors)) {
    echo "Blade syntax appears to be correct!\n";
} else {
    echo 'Found '.count($errors)." syntax errors:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
}
