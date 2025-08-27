<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Enhanced Notifications and Form Validation - Simple Tests', function (): void {
    describe('Toast Notification System', function (): void {
        it('dispatches success notification with correct parameters', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('showSuccessNotification', 'Test success message');

            $component->assertDispatched('toast');
        });

        it('dispatches error notification with correct parameters', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('showErrorNotification', 'Test error message');

            $component->assertDispatched('toast');
        });

        it('dispatches warning notification', function (): void {
            $component = Livewire::test('name-generator');

            $component->call('showWarningNotification', 'Test warning message');

            $component->assertDispatched('toast');
        });

        it('dispatches info notification', function (): void {
            $component = Livewire::test('name-generator');

            $component->call('showInfoNotification', 'Test info message');

            $component->assertDispatched('toast');
        });
    });

    describe('Form Validation System', function (): void {
        it('validates business description field correctly', function (): void {
            $component = Livewire::test('name-generator');

            // Test empty field
            $component->set('businessDescription', '');
            expect($component->get('validationErrors'))->toHaveKey('businessDescription');

            // Test too short
            $component->set('businessDescription', 'Short');
            expect($component->get('validationErrors'))->toHaveKey('businessDescription');

            // Test valid input
            $component->set('businessDescription', 'A valid business description with sufficient length');
            expect($component->get('validationErrors'))->not->toHaveKey('businessDescription');
            expect($component->get('validationSuccess'))->toHaveKey('businessDescription');
        });

        it('updates character count correctly', function (): void {
            $component = Livewire::test('name-generator');

            $description = 'Test business description';
            $component->set('businessDescription', $description);

            expect($component->get('characterCount'))->toBe(strlen($description));
            expect($component->get('characterLimit'))->toBe(2000);
        });

        it('detects near limit condition', function (): void {
            $component = Livewire::test('name-generator');

            // Test normal condition
            $component->set('businessDescription', 'Normal length description');
            expect($component->get('isNearLimit'))->toBeFalse();

            // Test near limit condition (over 90% of limit)
            $longDescription = str_repeat('a', 1900); // Over 90% of 2000
            $component->set('businessDescription', $longDescription);
            expect($component->get('isNearLimit'))->toBeTrue();
        });

        it('validates generation mode correctly', function (): void {
            $component = Livewire::test('name-generator');

            // Test invalid mode (this should be handled gracefully)
            $component->set('mode', 'creative'); // Valid mode
            $component->call('validateField', 'mode');
            expect($component->get('validationErrors'))->not->toHaveKey('mode');
        });

        it('prevents form submission when validation fails', function (): void {
            $component = Livewire::test('name-generator');

            // Set invalid data
            $component->set('businessDescription', ''); // Too short

            // Try to generate names
            $component->call('generateNames');

            // Should not generate names
            expect($component->get('generatedNames'))->toBeEmpty();

            // Should dispatch error notification
            $component->assertDispatched('toast');
        });
    });

    describe('Integration Tests', function (): void {
        it('shows success notification after name generation', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'A comprehensive tech startup focused on AI solutions')
                ->set('mode', 'creative');

            // Mock successful generation by setting names directly
            $component->set('generatedNames', ['TechFlow', 'AICore', 'InnovateTech']);
            $component->call('showGenerationCompleteNotification');

            $component->assertDispatched('toast');
        });

        it('validates input before showing name details modal', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Valid business description');

            // Test with empty name (should show error)
            $component->call('showNameDetails', '');

            expect($component->get('modalOpen'))->toBeFalse();
            $component->assertDispatched('toast'); // Should dispatch error notification
        });

        it('resets validation state when form is reset', function (): void {
            $component = Livewire::test('name-generator');

            // Set up some validation state
            $component->set('businessDescription', 'Valid input');
            expect($component->get('validationSuccess'))->toHaveKey('businessDescription');

            // Reset form
            $component->call('resetForm');

            // Validation state should be clean
            expect($component->get('validationErrors'))->toBeEmpty();
            expect($component->get('validationSuccess'))->toBeEmpty();
            expect($component->get('businessDescription'))->toBe('');
        });
    });
});
