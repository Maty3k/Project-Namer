<?php

declare(strict_types=1);

// Enhanced Blade syntax checker
$file = '/Users/anamariaradulescu/Herd/Project-Namer/resources/views/livewire/name-generator-dashboard.blade.php';
$content = file_get_contents($file);

// Count all @if and @endif statements
preg_match_all('/@if\b/', $content, $ifMatches);
preg_match_all('/@endif\b/', $content, $endifMatches);

echo 'Found '.count($ifMatches[0])." @if statements\n";
echo 'Found '.count($endifMatches[0])." @endif statements\n";

// Find unmatched statements
$lines = explode("\n", $content);
$stack = [];
$errors = [];

foreach ($lines as $lineNum => $line) {
    $lineNumber = $lineNum + 1;
    $trimmedLine = trim($line);

    // Skip lines that are comments or in strings
    if (strpos($trimmedLine, '{{--') !== false || strpos($trimmedLine, '--}}') !== false) {
        continue;
    }

    // Check for @if statements (including @if, but not @elseif)
    if (preg_match('/@if\b/', $line) && ! preg_match('/@elseif\b/', $line)) {
        $stack[] = ['type' => 'if', 'line' => $lineNumber, 'content' => $trimmedLine];
    }

    // Check for @elseif
    if (preg_match('/@elseif\b/', $line)) {
        if (empty($stack)) {
            $errors[] = "Unmatched @elseif at line $lineNumber: $trimmedLine";
        }
        // @elseif doesn't push to stack, it's part of existing @if
    }

    // Check for @else (but not @elseif)
    if (preg_match('/@else\b/', $line) && ! preg_match('/@elseif\b/', $line)) {
        if (empty($stack)) {
            $errors[] = "Unmatched @else at line $lineNumber: $trimmedLine";
        }
        // @else doesn't push to stack, it's part of existing @if
    }

    // Check for @endif
    if (preg_match('/@endif\b/', $line)) {
        if (empty($stack)) {
            $errors[] = "Unmatched @endif at line $lineNumber: $trimmedLine";
        } else {
            array_pop($stack);
        }
    }
}

// Check for unclosed @if statements
foreach ($stack as $unclosed) {
    $errors[] = "Unclosed @if at line {$unclosed['line']}: {$unclosed['content']}";
}

if (empty($errors)) {
    echo "No Blade syntax errors found!\n";
} else {
    echo "Blade syntax errors found:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
}
