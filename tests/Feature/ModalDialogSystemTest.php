<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Modal Dialog System', function (): void {
    describe('Modal Component Creation and Behavior', function (): void {
        it('creates a basic modal component', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'nameDetails', 'Test Business Name');

            expect($component->get('modalOpen'))->toBeTrue();
            expect($component->get('modalType'))->toBe('nameDetails');
            expect($component->get('modalData'))->toContain('Test Business Name');
        });

        it('closes modal when requested', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'nameDetails', 'Test Business Name');
            expect($component->get('modalOpen'))->toBeTrue();

            $component->call('closeModal');
            expect($component->get('modalOpen'))->toBeFalse();
            expect($component->get('modalType'))->toBeNull();
            expect($component->get('modalData'))->toBeNull();
        });

        it('handles multiple modal types correctly', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            // Test name details modal
            $component->call('openModal', 'nameDetails', ['name' => 'Test Corp', 'domains' => []]);
            expect($component->get('modalType'))->toBe('nameDetails');

            // Test domain info modal
            $component->call('openModal', 'domainInfo', ['domain' => 'example.com', 'status' => 'available']);
            expect($component->get('modalType'))->toBe('domainInfo');

            // Test confirmation modal
            $component->call('openModal', 'confirmation', ['message' => 'Are you sure?', 'action' => 'delete']);
            expect($component->get('modalType'))->toBe('confirmation');
        });

        it('passes modal data correctly', function (): void {
            $testData = [
                'name' => 'Innovative Solutions Corp',
                'domains' => [
                    'innovativesolutions.com' => ['status' => 'available', 'available' => true],
                    'innovativesolutions.net' => ['status' => 'unavailable', 'available' => false],
                ],
                'alternatives' => ['InnovativeSolutions', 'InnovateSolutions'],
            ];

            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'nameDetails', $testData);

            $modalData = $component->get('modalData');
            expect($modalData['name'])->toBe('Innovative Solutions Corp');
            expect($modalData['domains'])->toHaveKey('innovativesolutions.com');
            expect($modalData['alternatives'])->toContain('InnovativeSolutions');
        });

        it('prevents multiple modals from being open simultaneously', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'nameDetails', 'First Modal');
            expect($component->get('modalType'))->toBe('nameDetails');

            $component->call('openModal', 'domainInfo', 'Second Modal');
            expect($component->get('modalType'))->toBe('domainInfo');
            expect($component->get('modalData'))->toBe('Second Modal');
        });
    });

    describe('Modal Content Types', function (): void {
        it('displays name details modal with comprehensive information', function (): void {
            $nameData = [
                'name' => 'TechFlow Solutions',
                'length' => 17,
                'domains' => [
                    'techflowsolutions.com' => ['status' => 'available', 'available' => true, 'price' => '$12.99'],
                    'techflowsolutions.net' => ['status' => 'available', 'available' => true, 'price' => '$10.99'],
                    'techflowsolutions.org' => ['status' => 'unavailable', 'available' => false],
                ],
                'alternatives' => ['TechFlow', 'FlowTech', 'TechSolutions'],
                'trademark_status' => 'clear',
                'brandability_score' => 85,
            ];

            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'nameDetails', $nameData);

            expect($component->get('modalType'))->toBe('nameDetails');
            $modalData = $component->get('modalData');
            expect($modalData['brandability_score'])->toBe(85);
            expect($modalData['trademark_status'])->toBe('clear');
        });

        it('displays domain information modal with pricing details', function (): void {
            $domainData = [
                'domain' => 'example.com',
                'status' => 'available',
                'price' => '$12.99',
                'registrar' => 'Namecheap',
                'renewal_price' => '$14.99',
                'related_domains' => [
                    'example.net' => 'available',
                    'example.org' => 'unavailable',
                    'example.io' => 'available',
                ],
            ];

            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'domainInfo', $domainData);

            expect($component->get('modalType'))->toBe('domainInfo');
            $modalData = $component->get('modalData');
            expect($modalData['domain'])->toBe('example.com');
            expect($modalData['price'])->toBe('$12.99');
            expect($modalData['related_domains'])->toHaveKey('example.net');
        });

        it('displays confirmation modal for destructive actions', function (): void {
            $confirmationData = [
                'title' => 'Clear Search History',
                'message' => 'Are you sure you want to clear your search history? This action cannot be undone.',
                'confirmText' => 'Clear History',
                'cancelText' => 'Cancel',
                'action' => 'clearHistory',
                'variant' => 'danger',
            ];

            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'confirmation', $confirmationData);

            expect($component->get('modalType'))->toBe('confirmation');
            $modalData = $component->get('modalData');
            expect($modalData['title'])->toBe('Clear Search History');
            expect($modalData['variant'])->toBe('danger');
        });

        it('displays logo generation progress modal', function (): void {
            $logoData = [
                'businessName' => 'TechCorp Solutions',
                'progress' => 45,
                'status' => 'generating',
                'completedLogos' => 6,
                'totalLogos' => 12,
                'estimatedTimeRemaining' => '2 minutes',
            ];

            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'logoProgress', $logoData);

            expect($component->get('modalType'))->toBe('logoProgress');
            $modalData = $component->get('modalData');
            expect($modalData['progress'])->toBe(45);
            expect($modalData['completedLogos'])->toBe(6);
        });
    });

    describe('Modal Interaction and Dismissal', function (): void {
        it('handles backdrop click dismissal', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'nameDetails', 'Test Business Name');
            expect($component->get('modalOpen'))->toBeTrue();

            $component->call('handleBackdropClick');
            expect($component->get('modalOpen'))->toBeFalse();
        });

        it('prevents backdrop dismissal for critical modals', function (): void {
            $criticalData = [
                'title' => 'Critical Action',
                'message' => 'This action requires confirmation.',
                'dismissible' => false,
            ];

            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'confirmation', $criticalData);
            expect($component->get('modalOpen'))->toBeTrue();

            $component->call('handleBackdropClick');
            expect($component->get('modalOpen'))->toBeTrue(); // Should remain open
        });

        it('handles ESC key dismissal', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'nameDetails', 'Test Business Name');
            expect($component->get('modalOpen'))->toBeTrue();

            $component->call('handleEscapeKey');
            expect($component->get('modalOpen'))->toBeFalse();
        });

        it('executes confirmation modal actions', function (): void {
            $confirmationData = [
                'title' => 'Clear History',
                'action' => 'clearHistory',
                'parameters' => [],
            ];

            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('searchHistory', [
                    ['id' => '1', 'query' => 'test'],
                    ['id' => '2', 'query' => 'business'],
                ]);

            $component->call('openModal', 'confirmation', $confirmationData);
            $component->call('executeModalAction');

            expect($component->get('modalOpen'))->toBeFalse();
            expect($component->get('searchHistory'))->toBeEmpty();
        });

        it('handles modal action with parameters', function (): void {
            $confirmationData = [
                'title' => 'Generate Logos',
                'action' => 'generateLogos',
                'parameters' => ['TechCorp Solutions'],
            ];

            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'confirmation', $confirmationData);
            $component->call('executeModalAction');

            expect($component->get('modalOpen'))->toBeFalse();
            // Would normally check logo generation started, but that's complex in tests
        });
    });

    describe('Modal Accessibility and UX', function (): void {
        it('maintains focus management', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'nameDetails', 'Test Business Name');

            expect($component->get('modalOpen'))->toBeTrue();
            expect($component->get('focusedElement'))->toBe('modal-close-button');
        });

        it('provides proper ARIA attributes', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'nameDetails', ['name' => 'Test Business Name']);

            $ariaAttributes = $component->get('modalAriaAttributes');
            expect($ariaAttributes['aria-labelledby'])->toBe('modal-title');
            expect($ariaAttributes['aria-describedby'])->toBe('modal-content');
            expect($ariaAttributes['role'])->toBe('dialog');
        });

        it('handles keyboard navigation correctly', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'confirmation', [
                'title' => 'Test Confirmation',
                'confirmText' => 'Confirm',
                'cancelText' => 'Cancel',
            ]);

            // Test Tab navigation
            $component->call('handleTabKey', false); // forward
            expect($component->get('focusedElement'))->toBe('modal-cancel-button');

            $component->call('handleTabKey', true); // backward
            expect($component->get('focusedElement'))->toBe('modal-confirm-button');
        });

        it('announces modal changes to screen readers', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'nameDetails', ['name' => 'Test Business Name']);

            expect($component->get('screenReaderAnnouncement'))->toBe('Modal opened: Name details for Test Business Name');

            $component->call('closeModal');
            expect($component->get('screenReaderAnnouncement'))->toBe('Modal closed');
        });
    });

    describe('Modal Integration with Workflows', function (): void {
        it('integrates with name selection workflow', function (): void {
            $domainResults = [
                [
                    'name' => 'TechFlow Solutions',
                    'domains' => [
                        'techflowsolutions.com' => ['status' => 'available', 'available' => true],
                    ],
                ],
            ];

            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'tech consulting business')
                ->set('mode', 'creative')
                ->set('domainResults', $domainResults);

            $component->call('showNameDetails', 'TechFlow Solutions');

            expect($component->get('modalOpen'))->toBeTrue();
            expect($component->get('modalType'))->toBe('nameDetails');
            expect($component->get('modalData')['name'])->toBe('TechFlow Solutions');
        });

        it('integrates with domain checking workflow', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('showDomainInfo', 'example.com', [
                'status' => 'available',
                'price' => '$12.99',
                'registrar' => 'Namecheap',
            ]);

            expect($component->get('modalOpen'))->toBeTrue();
            expect($component->get('modalType'))->toBe('domainInfo');
            expect($component->get('modalData')['domain'])->toBe('example.com');
        });

        it('integrates with logo generation workflow', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('showLogoProgress', 'TechCorp Solutions');

            expect($component->get('modalOpen'))->toBeTrue();
            expect($component->get('modalType'))->toBe('logoProgress');
            expect($component->get('modalData')['businessName'])->toBe('TechCorp Solutions');
        });

        it('handles confirmation for destructive actions', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('searchHistory', [
                    ['id' => '1', 'query' => 'test business'],
                    ['id' => '2', 'query' => 'startup ideas'],
                ]);

            $component->call('confirmClearHistory');

            expect($component->get('modalOpen'))->toBeTrue();
            expect($component->get('modalType'))->toBe('confirmation');
            expect($component->get('modalData')['action'])->toBe('clearHistory');

            $component->call('executeModalAction');
            expect($component->get('searchHistory'))->toBeEmpty();
        });
    });

    describe('Modal Performance and State Management', function (): void {
        it('handles rapid modal opening and closing', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            for ($i = 0; $i < 5; $i++) {
                $component->call('openModal', 'nameDetails', "Test Name {$i}");
                expect($component->get('modalOpen'))->toBeTrue();

                $component->call('closeModal');
                expect($component->get('modalOpen'))->toBeFalse();
            }
        });

        it('cleans up modal state properly', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'nameDetails', [
                'name' => 'Test Name',
                'complex_data' => ['key' => 'value'],
            ]);

            $component->call('closeModal');

            expect($component->get('modalType'))->toBeNull();
            expect($component->get('modalData'))->toBeNull();
            expect($component->get('focusedElement'))->toBeNull();
            expect($component->get('screenReaderAnnouncement'))->toBe('Modal closed');
        });

        it('maintains modal state during component updates', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'nameDetails', 'Test Name');
            expect($component->get('modalOpen'))->toBeTrue();

            // Simulate component update
            $component->set('businessDescription', 'updated business description');

            expect($component->get('modalOpen'))->toBeTrue();
            expect($component->get('modalType'))->toBe('nameDetails');
        });

        it('handles modal data serialization correctly', function (): void {
            $complexData = [
                'name' => 'Complex Business Name',
                'nested' => [
                    'domains' => ['example.com', 'example.net'],
                    'metadata' => ['created' => '2024-01-01', 'score' => 95.5],
                ],
            ];

            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative');

            $component->call('openModal', 'nameDetails', $complexData);

            $modalData = $component->get('modalData');
            expect($modalData['nested']['metadata']['score'])->toBe(95.5);
        });
    });
});
