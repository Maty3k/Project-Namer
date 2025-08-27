<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Setup Blade view data with empty errors for FluxUI component testing
 */
function renderWithViewData(string $template): string
{
    $errors = new ViewErrorBag;
    $errors->put('default', new MessageBag);

    // Share errors globally for this test
    View::share('errors', $errors);

    return Blade::render($template);
}

describe('FluxUI Pro Component Migration', function (): void {
    describe('Button Component Migration', function (): void {
        it('renders flux pro button variants correctly', function (): void {
            $html = Blade::render('<flux:button variant="primary">Test Button</flux:button>');

            expect($html)->toContain('Test Button');
            expect($html)->toContain('flux-button');
        });

        it('supports loading states in pro buttons', function (): void {
            $html = Blade::render('<flux:button variant="primary" loading="true">Loading Button</flux:button>');

            expect($html)->toContain('Loading Button');
            expect($html)->toContain('flux-button');
        });

        it('maintains existing button functionality', function (): void {
            $html = Blade::render('<flux:button type="submit" variant="filled">Submit</flux:button>');

            expect($html)->toContain('Submit');
            expect($html)->toContain('type="submit"');
        });

        it('supports all button variants', function (string $variant): void {
            $html = Blade::render("<flux:button variant=\"{$variant}\">Test</flux:button>");

            expect($html)->toContain('Test');
            expect($html)->toContain('flux-button');
        })->with(['primary', 'filled', 'outline', 'ghost', 'danger']);
    });

    describe('Input Component Migration', function (): void {
        it('renders flux pro input with validation states', function (): void {
            $html = renderWithViewData('<flux:input name="test" placeholder="Test Input" />');

            expect($html)->toContain('name="test"');
            expect($html)->toContain('placeholder="Test Input"');
            expect($html)->toContain('flux-input');
        });

        it('supports error states', function (): void {
            $html = renderWithViewData('<flux:input name="test" error="true" />');

            expect($html)->toContain('name="test"');
            expect($html)->toContain('flux-input');
        });

        it('maintains Alpine.js interactions', function (): void {
            $html = renderWithViewData('<flux:input x-model="value" name="test" />');

            expect($html)->toContain('x-model="value"');
            expect($html)->toContain('name="test"');
        });

        it('supports various input types', function (string $type): void {
            $html = renderWithViewData("<flux:input type=\"{$type}\" name=\"test\" />");

            expect($html)->toContain("type=\"{$type}\"");
            expect($html)->toContain('name="test"');
        })->with(['text', 'email', 'password', 'search', 'url']);
    });

    describe('Field Component Migration', function (): void {
        it('renders field wrapper with enhanced validation', function (): void {
            $html = renderWithViewData('
                <flux:field>
                    <flux:label>Test Label</flux:label>
                    <flux:input name="test" />
                    <flux:error name="test">Test Error</flux:error>
                </flux:field>
            ');

            expect($html)->toContain('Test Label');
            expect($html)->toContain('name="test"');
            expect($html)->toContain('flux-error');
        });

        it('integrates properly with validation feedback', function (): void {
            $html = renderWithViewData('
                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input name="email" type="email" />
                </flux:field>
            ');

            expect($html)->toContain('Email');
            expect($html)->toContain('type="email"');
        });

        it('maintains field grouping functionality', function (): void {
            $html = renderWithViewData('
                <flux:field>
                    <flux:label>Required Field</flux:label>
                    <flux:input name="required" required />
                    <flux:description>This field is required</flux:description>
                </flux:field>
            ');

            expect($html)->toContain('Required Field');
            expect($html)->toContain('required');
            expect($html)->toContain('This field is required');
        });
    });

    describe('Checkbox Component Migration', function (): void {
        it('renders checkbox with enhanced functionality', function (): void {
            $html = renderWithViewData('<flux:checkbox name="terms" value="1" label="Accept Terms" />');

            expect($html)->toContain('name="terms"');
            expect($html)->toContain('value="1"');
            expect($html)->toContain('Accept Terms');
        });

        it('supports indeterminate state', function (): void {
            $html = renderWithViewData('<flux:checkbox name="select-all" indeterminate="true" label="Select All" />');

            expect($html)->toContain('name="select-all"');
            expect($html)->toContain('Select All');
        });

        it('maintains existing checkbox behavior', function (): void {
            $html = renderWithViewData('<flux:checkbox name="test" checked label="Checked Option" />');

            expect($html)->toContain('name="test"');
            expect($html)->toContain('Checked Option');
        });
    });

    describe('Layout Components Migration', function (): void {
        it('renders card components with enhanced styling', function (): void {
            $html = Blade::render('
                <flux:card>
                    <flux:heading size="lg">Card Title</flux:heading>
                    <p>Card content goes here</p>
                </flux:card>
            ');

            expect($html)->toContain('Card Title');
            expect($html)->toContain('Card content goes here');
        });

        it('supports modal components with accessibility', function (): void {
            $html = Blade::render('
                <flux:modal name="test-modal">
                    <div class="space-y-6">
                        <flux:heading>Modal Title</flux:heading>
                        <p>Modal content</p>
                    </div>
                </flux:modal>
            ');

            expect($html)->toContain('Modal Title');
            expect($html)->toContain('Modal content');
        });

        it('renders table with enhanced features', function (): void {
            $html = Blade::render('
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        <flux:table.row>
                            <flux:table.cell>Test Name</flux:table.cell>
                            <flux:table.cell>Active</flux:table.cell>
                        </flux:table.row>
                    </flux:table.rows>
                </flux:table>
            ');

            expect($html)->toContain('Name');
            expect($html)->toContain('Status');
            expect($html)->toContain('Test Name');
            expect($html)->toContain('Active');
        });
    });

    describe('Responsive Behavior', function (): void {
        it('maintains responsive classes on buttons', function (): void {
            $html = Blade::render('<flux:button class="w-full md:w-auto">Responsive Button</flux:button>');

            expect($html)->toContain('w-full');
            expect($html)->toContain('md:w-auto');
            expect($html)->toContain('Responsive Button');
        });

        it('supports responsive input sizing', function (): void {
            $html = renderWithViewData('<flux:input class="text-sm md:text-base" name="test" />');

            expect($html)->toContain('text-sm');
            expect($html)->toContain('md:text-base');
        });

        it('handles responsive card layouts', function (): void {
            $html = Blade::render('
                <flux:card class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                    <div>Card content</div>
                </flux:card>
            ');

            expect($html)->toContain('grid-cols-1');
            expect($html)->toContain('md:grid-cols-2');
            expect($html)->toContain('lg:grid-cols-3');
        });
    });

    describe('Alpine.js Integration', function (): void {
        it('preserves Alpine directives on buttons', function (): void {
            $html = Blade::render('<flux:button x-on:click="handleClick()">Click Me</flux:button>');

            expect($html)->toContain('x-on:click="handleClick()"');
            expect($html)->toContain('Click Me');
        });

        it('maintains Alpine state binding on inputs', function (): void {
            $html = renderWithViewData('<flux:input x-model="searchTerm" name="search" />');

            expect($html)->toContain('x-model="searchTerm"');
            expect($html)->toContain('name="search"');
        });

        it('supports Alpine show/hide on modals', function (): void {
            $html = Blade::render('
                <flux:modal x-show="showModal" name="alpine-modal">
                    <div>Alpine controlled modal</div>
                </flux:modal>
            ');

            expect($html)->toContain('x-show="showModal"');
            expect($html)->toContain('Alpine controlled modal');
        });
    });

    describe('Backward Compatibility', function (): void {
        it('maintains existing component attributes', function (): void {
            $html = Blade::render('
                <flux:button 
                    type="submit" 
                    class="custom-class" 
                    id="submit-btn" 
                    data-test="submit"
                >
                    Submit Form
                </flux:button>
            ');

            expect($html)->toContain('type="submit"');
            expect($html)->toContain('custom-class');
            expect($html)->toContain('id="submit-btn"');
            expect($html)->toContain('data-test="submit"');
            expect($html)->toContain('Submit Form');
        });

        it('preserves custom CSS classes', function (): void {
            $html = renderWithViewData('<flux:input class="border-red-500 focus:ring-blue-500" name="test" />');

            expect($html)->toContain('border-red-500');
            expect($html)->toContain('focus:ring-blue-500');
        });

        it('supports existing event handlers', function (): void {
            $html = Blade::render('
                <flux:button 
                    onclick="alert(\'clicked\')" 
                    onmouseover="handleHover()"
                >
                    Event Button
                </flux:button>
            ');

            expect($html)->toContain('onclick="alert(\'clicked\')"');
            expect($html)->toContain('onmouseover="handleHover()"');
            expect($html)->toContain('Event Button');
        });
    });

    describe('Livewire Integration', function (): void {
        it('integrates properly with Livewire components', function (): void {
            $component = Livewire::test('name-generator');

            $component->assertOk()
                ->assertSee('Generate Names')
                ->assertSee('Business Description');
        });

        it('maintains wire directives on components', function (): void {
            $html = Blade::render('
                <flux:button wire:click="submit" wire:loading.attr="disabled">
                    Submit
                </flux:button>
            ');

            expect($html)->toContain('wire:click="submit"');
            expect($html)->toContain('wire:loading.attr="disabled"');
            expect($html)->toContain('Submit');
        });

        it('supports wire:model on inputs', function (): void {
            $html = renderWithViewData('<flux:input wire:model.live="searchQuery" name="search" />');

            expect($html)->toContain('wire:model.live="searchQuery"');
            expect($html)->toContain('name="search"');
        });
    });
});
