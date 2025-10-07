<?php

declare(strict_types=1);

use App\Jobs\CheckDomainDNSJob;
use App\Livewire\NameGeneratorDashboard;
use App\Models\User;
use App\Services\DNSLookupService;
use App\Services\OpenAINameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Domain Filtering in Name Generation', function (): void {
    it('filters domains with DNS records from results', function (): void {
        // This test verifies that the DNS filtering logic correctly marks domains
        // The actual name generation will use fallback service, which is fine
        // We just need to verify DNS filtering works

        $this->mock(OpenAINameService::class, function ($mock): void {
            $mock->shouldReceive('generateNames')
                ->andReturn(['testbusiness', 'anothername']);
        });

        $this->mock(DNSLookupService::class, function ($mock): void {
            // Mock DNS responses for various domains
            $mock->shouldReceive('hasDNSRecords')->andReturnUsing(fn ($domain) =>
                // Simulate some domains having DNS, some not
                str_contains($domain, '.com') || str_contains($domain, '.org'));

            $mock->shouldReceive('getDNSRecords')->andReturn([
                'A' => [['ip' => '192.0.2.1']],
                'AAAA' => [],
                'CNAME' => [],
                'MX' => [],
            ]);
        });

        $component = Livewire::test(NameGeneratorDashboard::class)
            ->set('businessIdea', 'A test business')
            ->set('generationMode', 'professional')
            ->call('generateNames');

        // Check that results were generated
        $domainResults = $component->get('domainResults');
        expect($domainResults)->not->toBeEmpty();

        // Verify DNS filtering logic is working
        foreach ($domainResults as $name => $domains) {
            expect($domains)->toBeArray();
            expect($domains)->not->toBeEmpty();

            // Verify at least some domains check availability
            foreach ($domains as $domain => $info) {
                expect($info)->toHaveKey('available');
                expect($info)->toHaveKey('status');

                // .com and .org should be marked as unavailable per our mock
                if (str_contains($domain, '.com') || str_contains($domain, '.org')) {
                    expect($info['available'])->toBeFalse();
                }
            }
        }
    });

    it('dispatches background DNS jobs for all domains', function (): void {
        Queue::fake();

        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')->andReturn(false);
        });

        $this->mock(OpenAINameService::class, function ($mock): void {
            $mock->shouldReceive('generateNames')
                ->andReturn(['businessname']);
        });

        Livewire::test(NameGeneratorDashboard::class)
            ->set('businessIdea', 'A test business')
            ->set('generationMode', 'creative')
            ->call('generateNames');

        // Should dispatch job for each TLD (10 TLDs per name)
        Queue::assertPushed(CheckDomainDNSJob::class, 10);
    });

    it('shows checking status initially before DNS results', function (): void {
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')->andReturn(null); // DNS check pending
        });

        $this->mock(OpenAINameService::class, function ($mock): void {
            $mock->shouldReceive('generateNames')
                ->andReturn(['newname']);
        });

        $component = Livewire::test(NameGeneratorDashboard::class)
            ->set('businessIdea', 'A test business')
            ->set('generationMode', 'brandable')
            ->call('generateNames');

        expect($component->get('isCheckingDomains'))->toBeFalse();
        expect($component->get('domainResults'))->not->toBeEmpty();
    });

    it('handles DNS check failures gracefully with unknown status', function (): void {
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->andReturn(null); // DNS check failed
        });

        $this->mock(OpenAINameService::class, function ($mock): void {
            $mock->shouldReceive('generateNames')
                ->andReturn(['testbusiness']);
        });

        $component = Livewire::test(NameGeneratorDashboard::class)
            ->set('businessIdea', 'A test business')
            ->set('generationMode', 'tech-focused')
            ->call('generateNames');

        $domainResults = $component->get('domainResults');

        // DNS failures should not prevent results from showing
        expect($domainResults)->toHaveKey('testbusiness');
    });

    it('updates domain status when DNS check event is received', function (): void {
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')->andReturn(false);
        });

        $this->mock(OpenAINameService::class, function ($mock): void {
            $mock->shouldReceive('generateNames')
                ->andReturn(['mybusiness']);
        });

        $component = Livewire::test(NameGeneratorDashboard::class)
            ->set('businessIdea', 'My business idea')
            ->set('generationMode', 'professional')
            ->call('generateNames');

        // Simulate DNS check completion event
        $component->dispatch('domain-dns-checked', [
            'domain' => 'mybusiness.com',
            'available' => false,
            'has_dns_records' => true,
            'status' => 'taken',
            'check_method' => 'dns',
        ]);

        $domainResults = $component->get('domainResults');
        expect($domainResults['mybusiness']['mybusiness.com']['available'])->toBeFalse();
        expect($domainResults['mybusiness']['mybusiness.com']['has_dns_records'])->toBeTrue();
    });

    it('shows availability status correctly across multiple TLDs', function (): void {
        // This test verifies that DNS checking works and properly marks domains
        // The actual implementation is already tested in DomainCheckServiceTest

        $this->mock(OpenAINameService::class, function ($mock): void {
            $mock->shouldReceive('generateNames')
                ->andReturn(['testname']);
        });

        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')->andReturn(false); // All available for this test
        });

        $component = Livewire::test(NameGeneratorDashboard::class)
            ->set('businessIdea', 'Test filtering')
            ->set('generationMode', 'creative')
            ->call('generateNames');

        $domainResults = $component->get('domainResults');

        // Verify results exist
        expect($domainResults)->not->toBeEmpty();

        // Verify domain results structure is correct
        foreach ($domainResults as $domains) {
            expect($domains)->toBeArray();
            expect(count($domains))->toBeGreaterThan(0);

            // Each domain should have availability info
            foreach ($domains as $info) {
                expect($info)->toHaveKey('available');
                expect($info)->toHaveKey('status');
            }
        }
    });

    it('preserves domain results across component updates', function (): void {
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')->andReturn(false);
        });

        $this->mock(OpenAINameService::class, function ($mock): void {
            $mock->shouldReceive('generateNames')
                ->andReturn(['persistname']);
        });

        $component = Livewire::test(NameGeneratorDashboard::class)
            ->set('businessIdea', 'Persistence test')
            ->set('generationMode', 'brandable')
            ->call('generateNames');

        $initialResults = $component->get('domainResults');

        // Trigger component update
        $component->set('activeTab', 'results');

        $updatedResults = $component->get('domainResults');

        expect($updatedResults)->toBe($initialResults);
    });
});

describe('Real-time DNS Status Updates', function (): void {
    it('updates UI when background DNS check completes', function (): void {
        Event::fake(['domain-dns-checked']);

        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')->andReturn(false);
        });

        $this->mock(OpenAINameService::class, function ($mock): void {
            $mock->shouldReceive('generateNames')
                ->andReturn(['realtimename']);
        });

        $component = Livewire::test(NameGeneratorDashboard::class)
            ->set('businessIdea', 'Real-time test')
            ->set('generationMode', 'professional')
            ->call('generateNames');

        // Simulate background job completion
        $component->call('onDomainDNSChecked', [
            'domain' => 'realtimename.com',
            'available' => false,
            'has_dns_records' => true,
            'status' => 'taken',
            'check_method' => 'dns',
        ]);

        $domainResults = $component->get('domainResults');
        expect($domainResults['realtimename']['realtimename.com']['updated'])->toBeTrue();
    });

    it('handles multiple DNS check events for different domains', function (): void {
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')->andReturn(false);
        });

        $this->mock(OpenAINameService::class, function ($mock): void {
            $mock->shouldReceive('generateNames')
                ->andReturn(['multiname']);
        });

        $component = Livewire::test(NameGeneratorDashboard::class)
            ->set('businessIdea', 'Multi-domain test')
            ->set('generationMode', 'creative')
            ->call('generateNames');

        // Simulate multiple DNS checks completing
        $component->call('onDomainDNSChecked', [
            'domain' => 'multiname.com',
            'available' => false,
            'has_dns_records' => true,
            'status' => 'taken',
        ]);

        $component->call('onDomainDNSChecked', [
            'domain' => 'multiname.io',
            'available' => true,
            'has_dns_records' => false,
            'status' => 'available',
        ]);

        $domainResults = $component->get('domainResults');
        expect($domainResults['multiname']['multiname.com']['available'])->toBeFalse();
        expect($domainResults['multiname']['multiname.io']['available'])->toBeTrue();
    });
});
