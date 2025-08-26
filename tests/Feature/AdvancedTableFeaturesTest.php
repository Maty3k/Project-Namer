<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Advanced Table Features', function (): void {
    describe('Table Sorting Functionality', function (): void {
        it('sorts results by business name alphabetically ascending', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('generatedNames', ['Zebra Corp', 'Alpha Solutions', 'Beta Industries'])
                ->set('domainResults', [
                    [
                        'name' => 'Zebra Corp',
                        'domains' => [
                            'zebracorp.com' => ['status' => 'available', 'available' => true],
                        ],
                    ],
                    [
                        'name' => 'Alpha Solutions',
                        'domains' => [
                            'alphasolutions.com' => ['status' => 'available', 'available' => true],
                        ],
                    ],
                    [
                        'name' => 'Beta Industries',
                        'domains' => [
                            'betaindustries.com' => ['status' => 'unavailable', 'available' => false],
                        ],
                    ],
                ]);

            $component->call('sortTable', 'name', 'asc');

            expect($component->get('processedDomainResults')[0]['name'])->toBe('Alpha Solutions');
            expect($component->get('processedDomainResults')[1]['name'])->toBe('Beta Industries');
            expect($component->get('processedDomainResults')[2]['name'])->toBe('Zebra Corp');
        });

        it('sorts results by business name alphabetically descending', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('generatedNames', ['Alpha Solutions', 'Beta Industries', 'Zebra Corp'])
                ->set('domainResults', [
                    [
                        'name' => 'Alpha Solutions',
                        'domains' => [
                            'alphasolutions.com' => ['status' => 'available', 'available' => true],
                        ],
                    ],
                    [
                        'name' => 'Beta Industries',
                        'domains' => [
                            'betaindustries.com' => ['status' => 'unavailable', 'available' => false],
                        ],
                    ],
                    [
                        'name' => 'Zebra Corp',
                        'domains' => [
                            'zebracorp.com' => ['status' => 'available', 'available' => true],
                        ],
                    ],
                ]);

            $component->call('sortTable', 'name', 'desc');

            expect($component->get('processedDomainResults')[0]['name'])->toBe('Zebra Corp');
            expect($component->get('processedDomainResults')[1]['name'])->toBe('Beta Industries');
            expect($component->get('processedDomainResults')[2]['name'])->toBe('Alpha Solutions');
        });

        it('sorts results by name length shortest to longest', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('generatedNames', ['Very Long Business Name Corp', 'Short', 'Medium Name'])
                ->set('domainResults', [
                    [
                        'name' => 'Very Long Business Name Corp',
                        'domains' => [
                            'verylongbusinessnamecorp.com' => ['status' => 'available', 'available' => true],
                        ],
                    ],
                    [
                        'name' => 'Short',
                        'domains' => [
                            'short.com' => ['status' => 'unavailable', 'available' => false],
                        ],
                    ],
                    [
                        'name' => 'Medium Name',
                        'domains' => [
                            'mediumname.com' => ['status' => 'available', 'available' => true],
                        ],
                    ],
                ]);

            $component->call('sortTable', 'length', 'asc');

            expect($component->get('processedDomainResults')[0]['name'])->toBe('Short');
            expect($component->get('processedDomainResults')[1]['name'])->toBe('Medium Name');
            expect($component->get('processedDomainResults')[2]['name'])->toBe('Very Long Business Name Corp');
        });

        it('sorts results by domain availability status', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('generatedNames', ['Available Corp', 'Unavailable Inc', 'Mixed Domains'])
                ->set('domainResults', [
                    [
                        'name' => 'Available Corp',
                        'domains' => [
                            'availablecorp.com' => ['status' => 'available', 'available' => true],
                            'availablecorp.net' => ['status' => 'available', 'available' => true],
                            'availablecorp.org' => ['status' => 'available', 'available' => true],
                        ],
                    ],
                    [
                        'name' => 'Unavailable Inc',
                        'domains' => [
                            'unavailableinc.com' => ['status' => 'unavailable', 'available' => false],
                            'unavailableinc.net' => ['status' => 'unavailable', 'available' => false],
                            'unavailableinc.org' => ['status' => 'unavailable', 'available' => false],
                        ],
                    ],
                    [
                        'name' => 'Mixed Domains',
                        'domains' => [
                            'mixeddomains.com' => ['status' => 'available', 'available' => true],
                            'mixeddomains.net' => ['status' => 'unavailable', 'available' => false],
                            'mixeddomains.org' => ['status' => 'available', 'available' => true],
                        ],
                    ],
                ]);

            $component->call('sortTable', 'availability', 'desc');

            // Available domains should be first, then mixed, then unavailable
            expect($component->get('processedDomainResults')[0]['name'])->toBe('Available Corp');
        });

        it('maintains current sort when new results are added', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('generatedNames', ['Zebra Corp', 'Alpha Solutions'])
                ->set('domainResults', [
                    [
                        'name' => 'Zebra Corp',
                        'domains' => ['zebracorp.com' => ['status' => 'available', 'available' => true]],
                    ],
                    [
                        'name' => 'Alpha Solutions',
                        'domains' => ['alphasolutions.com' => ['status' => 'available', 'available' => true]],
                    ],
                ]);

            $component->call('sortTable', 'name', 'asc');

            // Add new result
            $component->set('domainResults', array_merge($component->get('domainResults'), [[
                'name' => 'Beta Industries',
                'domains' => ['betaindustries.com' => ['status' => 'available', 'available' => true]],
            ]]));

            $component->call('applySorting');

            expect($component->get('processedDomainResults')[0]['name'])->toBe('Alpha Solutions');
            expect($component->get('processedDomainResults')[1]['name'])->toBe('Beta Industries');
            expect($component->get('processedDomainResults')[2]['name'])->toBe('Zebra Corp');
        });
    });

    describe('Table Filtering Functionality', function (): void {
        it('filters results to show only available .com domains', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('domainResults', [
                    [
                        'name' => 'Available Corp',
                        'domains' => [
                            'availablecorp.com' => ['status' => 'available', 'available' => true],
                            'availablecorp.net' => ['status' => 'unavailable', 'available' => false],
                        ],
                    ],
                    [
                        'name' => 'Unavailable Inc',
                        'domains' => [
                            'unavailableinc.com' => ['status' => 'unavailable', 'available' => false],
                            'unavailableinc.net' => ['status' => 'available', 'available' => true],
                        ],
                    ],
                    [
                        'name' => 'Mixed Domains',
                        'domains' => [
                            'mixeddomains.com' => ['status' => 'available', 'available' => true],
                            'mixeddomains.net' => ['status' => 'available', 'available' => true],
                        ],
                    ],
                ]);

            $component->call('filterTable', 'domain_status', 'com_available');

            $filteredResults = $component->get('filteredDomainResults');
            expect($filteredResults)->toHaveCount(2);
            expect($filteredResults[0]['name'])->toBe('Available Corp');
            expect($filteredResults[1]['name'])->toBe('Mixed Domains');
        });

        it('filters results by name length ranges', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('domainResults', [
                    [
                        'name' => 'Short',
                        'domains' => ['short.com' => ['status' => 'available', 'available' => true]],
                    ],
                    [
                        'name' => 'Medium Length Name',
                        'domains' => ['mediumlengthname.com' => ['status' => 'available', 'available' => true]],
                    ],
                    [
                        'name' => 'Very Long Business Name Corporation',
                        'domains' => ['verylongbusinessnamecorporation.com' => ['status' => 'available', 'available' => true]],
                    ],
                ]);

            $component->call('filterTable', 'name_length', 'short'); // 1-10 chars

            $filteredResults = $component->get('filteredDomainResults');
            expect($filteredResults)->toHaveCount(1);
            expect($filteredResults[0]['name'])->toBe('Short');

            $component->call('filterTable', 'name_length', 'medium'); // 11-20 chars

            $filteredResults = $component->get('filteredDomainResults');
            expect($filteredResults)->toHaveCount(1);
            expect($filteredResults[0]['name'])->toBe('Medium Length Name');
        });

        it('filters results by multiple domain extensions availability', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('domainResults', [
                    [
                        'name' => 'Triple Available',
                        'domains' => [
                            'tripleavailable.com' => ['status' => 'available', 'available' => true],
                            'tripleavailable.net' => ['status' => 'available', 'available' => true],
                            'tripleavailable.org' => ['status' => 'available', 'available' => true],
                        ],
                    ],
                    [
                        'name' => 'Com Only',
                        'domains' => [
                            'comonly.com' => ['status' => 'available', 'available' => true],
                            'comonly.net' => ['status' => 'unavailable', 'available' => false],
                            'comonly.org' => ['status' => 'unavailable', 'available' => false],
                        ],
                    ],
                    [
                        'name' => 'None Available',
                        'domains' => [
                            'noneavailable.com' => ['status' => 'unavailable', 'available' => false],
                            'noneavailable.net' => ['status' => 'unavailable', 'available' => false],
                            'noneavailable.org' => ['status' => 'unavailable', 'available' => false],
                        ],
                    ],
                ]);

            $component->call('filterTable', 'domain_status', 'all_available');

            $filteredResults = $component->get('filteredDomainResults');
            expect($filteredResults)->toHaveCount(1);
            expect($filteredResults[0]['name'])->toBe('Triple Available');
        });

        it('combines sorting and filtering correctly', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('domainResults', [
                    [
                        'name' => 'Zebra Available',
                        'domains' => ['zebraavailable.com' => ['status' => 'available', 'available' => true]],
                    ],
                    [
                        'name' => 'Alpha Available',
                        'domains' => ['alphaavailable.com' => ['status' => 'available', 'available' => true]],
                    ],
                    [
                        'name' => 'Beta Unavailable',
                        'domains' => ['betaunavailable.com' => ['status' => 'unavailable', 'available' => false]],
                    ],
                ]);

            $component->call('filterTable', 'domain_status', 'com_available');
            $component->call('sortTable', 'name', 'asc');

            $results = $component->get('processedDomainResults');
            expect($results)->toHaveCount(2);
            expect($results[0]['name'])->toBe('Alpha Available');
            expect($results[1]['name'])->toBe('Zebra Available');
        });

        it('clears all filters when requested', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('domainResults', [
                    [
                        'name' => 'Test Name 1',
                        'domains' => ['testname1.com' => ['status' => 'available', 'available' => true]],
                    ],
                    [
                        'name' => 'Test Name 2',
                        'domains' => ['testname2.com' => ['status' => 'unavailable', 'available' => false]],
                    ],
                ]);

            $component->call('filterTable', 'domain_status', 'com_available');
            expect($component->get('filteredDomainResults'))->toHaveCount(1);

            $component->call('clearFilters');
            expect($component->get('processedDomainResults'))->toHaveCount(2);
            expect($component->get('activeFilters'))->toBeEmpty();
        });
    });

    describe('Table UI Integration', function (): void {
        it('displays sorting indicators in table headers', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('domainResults', [
                    ['name' => 'Test Name', 'domains' => ['test.com' => ['status' => 'available', 'available' => true]]],
                ])
                ->call('sortTable', 'name', 'asc');

            // Check that sorting state is maintained
            expect($component->get('currentSort')['column'])->toBe('name');
            expect($component->get('currentSort')['direction'])->toBe('asc');
        });

        it('shows active filter indicators in the UI', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('domainResults', [
                    ['name' => 'Test Name', 'domains' => ['test.com' => ['status' => 'available', 'available' => true]]],
                ])
                ->call('filterTable', 'domain_status', 'com_available');

            // Check that filter state is maintained
            expect($component->get('activeFilters'))->toHaveKey('domain_status');
            expect($component->get('activeFilters')['domain_status'])->toBe('com_available');
        });

        it('maintains table state during Livewire updates', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('domainResults', [
                    ['name' => 'Test Name', 'domains' => ['test.com' => ['status' => 'available', 'available' => true]]],
                ]);

            $component->call('sortTable', 'name', 'desc');
            $component->call('filterTable', 'domain_status', 'com_available');

            expect($component->get('currentSort')['column'])->toBe('name');
            expect($component->get('currentSort')['direction'])->toBe('desc');
            expect($component->get('activeFilters'))->toHaveKey('domain_status');
        });
    });

    describe('Performance and Edge Cases', function (): void {
        it('handles empty results gracefully', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('domainResults', []);

            $component->call('sortTable', 'name', 'asc');
            $component->call('filterTable', 'domain_status', 'com_available');

            expect($component->get('processedDomainResults'))->toBeEmpty();
        });

        it('handles large datasets efficiently', function (): void {
            $largeDataset = [];
            for ($i = 0; $i < 100; $i++) {
                $largeDataset[] = [
                    'name' => "Business Name {$i}",
                    'domains' => [
                        "businessname{$i}.com" => ['status' => $i % 2 === 0 ? 'available' : 'unavailable', 'available' => $i % 2 === 0],
                    ],
                ];
            }

            $startTime = microtime(true);

            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('domainResults', $largeDataset);

            $component->call('sortTable', 'name', 'asc');
            $component->call('filterTable', 'domain_status', 'com_available');

            $endTime = microtime(true);
            $processingTime = $endTime - $startTime;

            expect($processingTime)->toBeLessThan(1.0); // Should complete within 1 second
            expect($component->get('processedDomainResults'))->toHaveCount(50); // Half should be available
        });

        it('preserves special characters in sorting', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'test business')
                ->set('mode', 'creative')
                ->set('domainResults', [
                    ['name' => 'Café Solutions', 'domains' => ['cafesolutions.com' => ['status' => 'available', 'available' => true]]],
                    ['name' => 'Alpha Corp', 'domains' => ['alphacorp.com' => ['status' => 'available', 'available' => true]]],
                    ['name' => 'Ümlaüt Inc', 'domains' => ['umlautinc.com' => ['status' => 'available', 'available' => true]]],
                ]);

            $component->call('sortTable', 'name', 'asc');

            $results = $component->get('processedDomainResults');
            expect($results[0]['name'])->toBe('Alpha Corp');
            // Special characters should be handled properly in sorting
        });
    });
});
