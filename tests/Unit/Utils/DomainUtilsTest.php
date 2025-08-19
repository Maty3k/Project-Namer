<?php

declare(strict_types=1);

use App\Utils\DomainUtils;

describe('DomainUtils', function (): void {
    describe('formatDomain', function (): void {
        it('converts domain to lowercase', function (): void {
            expect(DomainUtils::formatDomain('EXAMPLE.COM'))->toBe('example.com');
        });

        it('trims whitespace', function (): void {
            expect(DomainUtils::formatDomain('  example.com  '))->toBe('example.com');
        });

        it('removes protocol prefixes', function (): void {
            expect(DomainUtils::formatDomain('https://example.com'))->toBe('example.com');
            expect(DomainUtils::formatDomain('http://example.com'))->toBe('example.com');
            expect(DomainUtils::formatDomain('HTTP://EXAMPLE.COM'))->toBe('example.com');
        });

        it('removes www prefix', function (): void {
            expect(DomainUtils::formatDomain('www.example.com'))->toBe('example.com');
            expect(DomainUtils::formatDomain('WWW.EXAMPLE.COM'))->toBe('example.com');
        });

        it('removes trailing slash', function (): void {
            expect(DomainUtils::formatDomain('example.com/'))->toBe('example.com');
            expect(DomainUtils::formatDomain('example.com///'))->toBe('example.com');
        });

        it('handles complex URLs', function (): void {
            expect(DomainUtils::formatDomain('https://www.example.com/path?query=1#fragment'))
                ->toBe('example.com');
        });
    });

    describe('isValidDomain', function (): void {
        it('validates correct domain formats', function (): void {
            expect(DomainUtils::isValidDomain('example.com'))->toBeTrue();
            expect(DomainUtils::isValidDomain('sub.example.com'))->toBeTrue();
            expect(DomainUtils::isValidDomain('test-site.example.co.uk'))->toBeTrue();
            expect(DomainUtils::isValidDomain('123.example.com'))->toBeTrue();
        });

        it('rejects invalid domain formats', function (): void {
            expect(DomainUtils::isValidDomain(''))->toBeFalse();
            expect(DomainUtils::isValidDomain('example'))->toBeFalse();
            expect(DomainUtils::isValidDomain('example.'))->toBeFalse();
            expect(DomainUtils::isValidDomain('.example.com'))->toBeFalse();
            expect(DomainUtils::isValidDomain('example..com'))->toBeFalse();
            expect(DomainUtils::isValidDomain('exam ple.com'))->toBeFalse();
            expect(DomainUtils::isValidDomain('example.c'))->toBeFalse();
        });

        it('rejects domains with invalid characters', function (): void {
            expect(DomainUtils::isValidDomain('example@.com'))->toBeFalse();
            expect(DomainUtils::isValidDomain('example!.com'))->toBeFalse();
            expect(DomainUtils::isValidDomain('example_.com'))->toBeFalse();
        });
    });

    describe('sanitizeBusinessName', function (): void {
        it('converts to lowercase', function (): void {
            expect(DomainUtils::sanitizeBusinessName('MyBusiness'))->toBe('mybusiness');
        });

        it('removes spaces', function (): void {
            expect(DomainUtils::sanitizeBusinessName('My Business'))->toBe('mybusiness');
            expect(DomainUtils::sanitizeBusinessName('My  Business  Name'))->toBe('mybusinessname');
        });

        it('removes special characters', function (): void {
            expect(DomainUtils::sanitizeBusinessName('My-Business!'))->toBe('mybusiness');
            expect(DomainUtils::sanitizeBusinessName('Business & Co.'))->toBe('businessco');
            expect(DomainUtils::sanitizeBusinessName('Email@Company.com'))->toBe('emailcompanycom');
        });

        it('preserves alphanumeric characters', function (): void {
            expect(DomainUtils::sanitizeBusinessName('Business123'))->toBe('business123');
            expect(DomainUtils::sanitizeBusinessName('ABC123XYZ'))->toBe('abc123xyz');
        });

        it('handles empty results', function (): void {
            expect(DomainUtils::sanitizeBusinessName('!@#$%'))->toBe('');
            expect(DomainUtils::sanitizeBusinessName('   '))->toBe('');
        });

        it('trims whitespace before processing', function (): void {
            expect(DomainUtils::sanitizeBusinessName('  My Business  '))->toBe('mybusiness');
        });
    });

    describe('extractTLD', function (): void {
        it('extracts correct TLD from domains', function (): void {
            expect(DomainUtils::extractTLD('example.com'))->toBe('com');
            expect(DomainUtils::extractTLD('example.co.uk'))->toBe('uk');
            expect(DomainUtils::extractTLD('site.example.org'))->toBe('org');
            expect(DomainUtils::extractTLD('test.io'))->toBe('io');
        });

        it('handles domains without TLD', function (): void {
            expect(DomainUtils::extractTLD('example'))->toBeNull();
            expect(DomainUtils::extractTLD(''))->toBeNull();
        });
    });

    describe('buildDomainsFromName', function (): void {
        it('builds domains with default TLDs', function (): void {
            $domains = DomainUtils::buildDomainsFromName('testbusiness');

            expect($domains)->toHaveCount(4)
                ->and($domains)->toContain('testbusiness.com')
                ->and($domains)->toContain('testbusiness.io')
                ->and($domains)->toContain('testbusiness.co')
                ->and($domains)->toContain('testbusiness.net');
        });

        it('builds domains with custom TLDs', function (): void {
            $domains = DomainUtils::buildDomainsFromName('testbusiness', ['org', 'dev']);

            expect($domains)->toHaveCount(2)
                ->and($domains)->toContain('testbusiness.org')
                ->and($domains)->toContain('testbusiness.dev');
        });

        it('sanitizes business name before building domains', function (): void {
            $domains = DomainUtils::buildDomainsFromName('Test Business!');

            expect($domains)->toContain('testbusiness.com');
        });
    });

    describe('isDomainCacheFresh', function (): void {
        it('returns true for fresh cache entries', function (): void {
            $freshTimestamp = now();
            expect(DomainUtils::isDomainCacheFresh($freshTimestamp))->toBeTrue();

            $recentTimestamp = now()->subHours(12);
            expect(DomainUtils::isDomainCacheFresh($recentTimestamp))->toBeTrue();
        });

        it('returns false for expired cache entries', function (): void {
            $expiredTimestamp = now()->subHours(25);
            expect(DomainUtils::isDomainCacheFresh($expiredTimestamp))->toBeFalse();

            $veryOldTimestamp = now()->subDays(2);
            expect(DomainUtils::isDomainCacheFresh($veryOldTimestamp))->toBeFalse();
        });

        it('handles boundary conditions', function (): void {
            $exactBoundary = now()->subHours(24);
            expect(DomainUtils::isDomainCacheFresh($exactBoundary))->toBeFalse();
        });
    });

    describe('getAvailabilityStatus', function (): void {
        it('returns correct status for available domains', function (): void {
            expect(DomainUtils::getAvailabilityStatus(true, null))->toBe('available');
        });

        it('returns correct status for unavailable domains', function (): void {
            expect(DomainUtils::getAvailabilityStatus(false, null))->toBe('taken');
        });

        it('returns error status when availability is null', function (): void {
            expect(DomainUtils::getAvailabilityStatus(null, 'API error'))->toBe('error');
        });

        it('returns checking status when specified', function (): void {
            expect(DomainUtils::getAvailabilityStatus(null, null, 'checking'))->toBe('checking');
        });
    });
});
