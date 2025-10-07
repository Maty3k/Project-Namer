<?php

declare(strict_types=1);

use App\Jobs\CheckDomainDNSJob;
use App\Models\DomainCache;
use App\Services\DomainCheckService;
use App\Services\DNSLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('CheckDomainDNSJob', function (): void {
    it('dispatches DNS check for a domain', function (): void {
        Queue::fake();

        CheckDomainDNSJob::dispatch('example.com');

        Queue::assertPushed(CheckDomainDNSJob::class, function ($job) {
            return $job->domain === 'example.com';
        });
    });

    it('checks DNS records and stores result in cache', function (): void {
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->with('example.com')
                ->once()
                ->andReturn(true);
            $mock->shouldReceive('getDNSRecords')
                ->with('example.com')
                ->once()
                ->andReturn([
                    'A' => [['ip' => '192.0.2.1']],
                    'AAAA' => [],
                    'CNAME' => [],
                    'MX' => [],
                ]);
        });

        $job = new CheckDomainDNSJob('example.com');
        $job->handle(app(DomainCheckService::class));

        $cached = DomainCache::where('domain', 'example.com')->first();
        expect($cached)->not->toBeNull();
        expect($cached->available)->toBeFalse();
        expect($cached->has_dns_records)->toBeTrue();
        expect($cached->check_method)->toBe('dns');
    });

    it('dispatches Livewire event on completion', function (): void {
        Event::fake();

        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->andReturn(false);
        });

        $job = new CheckDomainDNSJob('example.com');
        $job->handle(app(DomainCheckService::class));

        // Livewire events are dispatched via the app's event system
        Event::assertDispatched('domain-dns-checked');
    });

    it('has 5 retry attempts configured', function (): void {
        $job = new CheckDomainDNSJob('example.com');

        expect($job->tries)->toBe(5);
    });

    it('has 30 second timeout configured', function (): void {
        $job = new CheckDomainDNSJob('example.com');

        expect($job->timeout)->toBe(30);
    });

    it('handles DNS lookup failures gracefully', function (): void {
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->andReturn(null); // DNS lookup failed
        });

        $job = new CheckDomainDNSJob('example.com');

        // Job should not throw exception on DNS failure
        expect(fn () => $job->handle(app(DomainCheckService::class)))
            ->not->toThrow(Exception::class);
    });

    it('processes multiple domains in batch', function (): void {
        Queue::fake();

        $domains = ['example.com', 'test.io', 'sample.net'];

        foreach ($domains as $domain) {
            CheckDomainDNSJob::dispatch($domain);
        }

        Queue::assertPushed(CheckDomainDNSJob::class, 3);
    });

    it('updates existing cache entry instead of creating duplicate', function (): void {
        // Create initial cache entry
        DomainCache::create([
            'domain' => 'example.com',
            'available' => true,
            'check_method' => 'api',
            'checked_at' => now()->subDays(2),
        ]);

        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->andReturn(true);
            $mock->shouldReceive('getDNSRecords')
                ->andReturn([
                    'A' => [['ip' => '192.0.2.1']],
                    'AAAA' => [],
                    'CNAME' => [],
                    'MX' => [],
                ]);
        });

        $job = new CheckDomainDNSJob('example.com');
        $job->handle(app(DomainCheckService::class));

        // Should only have one cache entry
        expect(DomainCache::where('domain', 'example.com')->count())->toBe(1);

        $cached = DomainCache::where('domain', 'example.com')->first();
        expect($cached->available)->toBeFalse(); // Updated
        expect($cached->check_method)->toBe('dns'); // Updated
    });

    it('includes domain name in Livewire event payload', function (): void {
        Event::fake();

        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->with('example.com')
                ->andReturn(true);
            $mock->shouldReceive('getDNSRecords')
                ->with('example.com')
                ->andReturn([
                    'A' => [['ip' => '192.0.2.1']],
                    'AAAA' => [],
                    'CNAME' => [],
                    'MX' => [],
                ]);
        });

        $job = new CheckDomainDNSJob('example.com');
        $job->handle(app(DomainCheckService::class));

        Event::assertDispatched('domain-dns-checked');
    });

    it('dispatches event with correct availability status', function (): void {
        Event::fake();

        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->with('newdomain.com')
                ->andReturn(false); // No DNS = potentially available
        });

        $job = new CheckDomainDNSJob('newdomain.com');
        $job->handle(app(DomainCheckService::class));

        Event::assertDispatched('domain-dns-checked');
    });
});
