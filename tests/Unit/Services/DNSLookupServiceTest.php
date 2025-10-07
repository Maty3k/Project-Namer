<?php

declare(strict_types=1);

use App\Services\DNSLookupService;
use Illuminate\Support\Facades\Log;
use Spatie\Dns\Dns;

beforeEach(function (): void {
    $this->service = new DNSLookupService();

    // Mock Log facade for unit tests
    Log::spy();
});

describe('DNSLookupService', function (): void {
    describe('hasDNSRecords', function (): void {
        test('returns true when domain has A records', function (): void {
            $mockDns = Mockery::mock('overload:'.Dns::class);
            $mockDns->shouldReceive('getRecords')
                ->with('example.com', DNS_A)
                ->andReturn([['ip' => '192.0.2.1']]);

            $result = $this->service->hasDNSRecords('example.com');

            expect($result)->toBeTrue();
        });

        test('returns true when domain has AAAA records', function (): void {
            $mockDns = Mockery::mock('overload:'.Dns::class);
            $mockDns->shouldReceive('getRecords')
                ->with('example.com', DNS_A)
                ->andReturn([]);
            $mockDns->shouldReceive('getRecords')
                ->with('example.com', DNS_AAAA)
                ->andReturn([['ipv6' => '2001:db8::1']]);

            $result = $this->service->hasDNSRecords('example.com');

            expect($result)->toBeTrue();
        });

        test('returns true when domain has CNAME records', function (): void {
            $mockDns = Mockery::mock('overload:'.Dns::class);
            $mockDns->shouldReceive('getRecords')
                ->with('example.com', DNS_A)
                ->andReturn([]);
            $mockDns->shouldReceive('getRecords')
                ->with('example.com', DNS_AAAA)
                ->andReturn([]);
            $mockDns->shouldReceive('getRecords')
                ->with('example.com', DNS_CNAME)
                ->andReturn([['target' => 'otherdomain.com']]);

            $result = $this->service->hasDNSRecords('example.com');

            expect($result)->toBeTrue();
        });

        test('returns true when domain has MX records', function (): void {
            $mockDns = Mockery::mock('overload:'.Dns::class);
            $mockDns->shouldReceive('getRecords')
                ->with('example.com', DNS_A)
                ->andReturn([]);
            $mockDns->shouldReceive('getRecords')
                ->with('example.com', DNS_AAAA)
                ->andReturn([]);
            $mockDns->shouldReceive('getRecords')
                ->with('example.com', DNS_CNAME)
                ->andReturn([]);
            $mockDns->shouldReceive('getRecords')
                ->with('example.com', DNS_MX)
                ->andReturn([['target' => 'mail.example.com', 'pri' => 10]]);

            $result = $this->service->hasDNSRecords('example.com');

            expect($result)->toBeTrue();
        });

        test('returns false when domain has no DNS records', function (): void {
            $mockDns = Mockery::mock('overload:'.Dns::class);
            $mockDns->shouldReceive('getRecords')
                ->andReturn([]);

            $result = $this->service->hasDNSRecords('newdomain.com');

            expect($result)->toBeFalse();
        });

        test('handles invalid domain gracefully', function (): void {
            $mockDns = Mockery::mock('overload:'.Dns::class);
            $mockDns->shouldReceive('getRecords')
                ->andThrow(new \Exception('Invalid domain'));

            $result = $this->service->hasDNSRecords('invalid..domain');

            expect($result)->toBeNull();
        });

        test('handles DNS resolution timeout', function (): void {
            $mockDns = Mockery::mock('overload:'.Dns::class);
            $mockDns->shouldReceive('getRecords')
                ->andThrow(new \Exception('DNS query timeout'));

            $result = $this->service->hasDNSRecords('timeout.com');

            expect($result)->toBeNull();
        });

        test('validates domain format before lookup', function (): void {
            // Empty domain
            $result = $this->service->hasDNSRecords('');
            expect($result)->toBeNull();

            // Invalid characters
            $result = $this->service->hasDNSRecords('invalid domain.com');
            expect($result)->toBeNull();

            // Too short
            $result = $this->service->hasDNSRecords('x');
            expect($result)->toBeNull();
        });

        test('returns DNS record details when requested', function (): void {
            $mockDns = Mockery::mock('overload:'.Dns::class);
            $mockDns->shouldReceive('getRecords')
                ->with('example.com', DNS_A)
                ->andReturn([['ip' => '192.0.2.1'], ['ip' => '192.0.2.2']]);
            $mockDns->shouldReceive('getRecords')
                ->with('example.com', DNS_AAAA)
                ->andReturn([]);
            $mockDns->shouldReceive('getRecords')
                ->with('example.com', DNS_CNAME)
                ->andReturn([]);
            $mockDns->shouldReceive('getRecords')
                ->with('example.com', DNS_MX)
                ->andReturn([['target' => 'mail.example.com', 'pri' => 10]]);

            $records = $this->service->getDNSRecords('example.com');

            expect($records)->toBeArray()
                ->and($records)->toHaveKey('A')
                ->and($records['A'])->toHaveCount(2)
                ->and($records)->toHaveKey('MX')
                ->and($records['MX'])->toHaveCount(1);
        });
    });

    describe('domain validation', function (): void {
        test('validates domain format correctly', function (): void {
            // Valid domains
            expect($this->service->isValidDomain('example.com'))->toBeTrue();
            expect($this->service->isValidDomain('sub.example.com'))->toBeTrue();
            expect($this->service->isValidDomain('my-domain.co.uk'))->toBeTrue();

            // Invalid domains
            expect($this->service->isValidDomain(''))->toBeFalse();
            expect($this->service->isValidDomain('invalid domain'))->toBeFalse();
            expect($this->service->isValidDomain('.com'))->toBeFalse();
            expect($this->service->isValidDomain('domain..com'))->toBeFalse();
            expect($this->service->isValidDomain('-domain.com'))->toBeFalse();
        });
    });
});
