<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('LogoGeneration Model', function (): void {
    it('can create a logo generation entry', function (): void {
        $generation = LogoGeneration::create([
            'session_id' => 'sess_123',
            'business_name' => 'TechFlow',
            'business_description' => 'A modern tech company',
            'status' => 'pending',
        ]);

        expect($generation)->toBeInstanceOf(LogoGeneration::class)
            ->and($generation->session_id)->toBe('sess_123')
            ->and($generation->business_name)->toBe('TechFlow')
            ->and($generation->status)->toBe('pending')
            ->and($generation->total_logos_requested)->toBe(12)
            ->and($generation->logos_completed)->toBe(0);
    });

    it('has correct default values', function (): void {
        $generation = LogoGeneration::create([
            'session_id' => 'sess_123',
            'business_name' => 'TechFlow',
        ]);

        expect($generation->status)->toBe('pending')
            ->and($generation->total_logos_requested)->toBe(12)
            ->and($generation->logos_completed)->toBe(0)
            ->and($generation->api_provider)->toBe('openai')
            ->and($generation->cost_cents)->toBe(0);
    });

    it('can update status and progress', function (): void {
        $generation = LogoGeneration::create([
            'session_id' => 'sess_123',
            'business_name' => 'TechFlow',
        ]);

        $generation->update([
            'status' => 'processing',
            'logos_completed' => 6,
        ]);

        expect($generation->fresh()->status)->toBe('processing')
            ->and($generation->fresh()->logos_completed)->toBe(6);
    });

    it('validates status enum values', function (): void {
        expect(fn () => LogoGeneration::create([
            'session_id' => 'sess_123',
            'business_name' => 'TechFlow',
            'status' => 'invalid_status',
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('has fillable attributes', function (): void {
        $fillable = (new LogoGeneration)->getFillable();

        expect($fillable)->toContain('session_id')
            ->and($fillable)->toContain('business_name')
            ->and($fillable)->toContain('business_description')
            ->and($fillable)->toContain('status')
            ->and($fillable)->toContain('total_logos_requested')
            ->and($fillable)->toContain('logos_completed')
            ->and($fillable)->toContain('api_provider')
            ->and($fillable)->toContain('cost_cents')
            ->and($fillable)->toContain('error_message');
    });

    it('can scope by session id', function (): void {
        LogoGeneration::create([
            'session_id' => 'sess_123',
            'business_name' => 'TechFlow',
        ]);

        LogoGeneration::create([
            'session_id' => 'sess_456',
            'business_name' => 'DataCorp',
        ]);

        $results = LogoGeneration::forSession('sess_123')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->business_name)->toBe('TechFlow');
    });

    it('can scope by status', function (): void {
        LogoGeneration::create([
            'session_id' => 'sess_123',
            'business_name' => 'TechFlow',
            'status' => 'pending',
        ]);

        LogoGeneration::create([
            'session_id' => 'sess_456',
            'business_name' => 'DataCorp',
            'status' => 'completed',
        ]);

        $pending = LogoGeneration::pending()->get();
        $completed = LogoGeneration::completed()->get();

        expect($pending)->toHaveCount(1)
            ->and($pending->first()->business_name)->toBe('TechFlow')
            ->and($completed)->toHaveCount(1)
            ->and($completed->first()->business_name)->toBe('DataCorp');
    });

    it('can check if generation is complete', function (): void {
        $incomplete = LogoGeneration::create([
            'session_id' => 'sess_123',
            'business_name' => 'TechFlow',
            'status' => 'processing',
            'total_logos_requested' => 12,
            'logos_completed' => 6,
        ]);

        $complete = LogoGeneration::create([
            'session_id' => 'sess_456',
            'business_name' => 'DataCorp',
            'status' => 'completed',
            'total_logos_requested' => 12,
            'logos_completed' => 12,
        ]);

        expect($incomplete->isComplete())->toBeFalse()
            ->and($complete->isComplete())->toBeTrue();
    });

    it('can calculate completion percentage', function (): void {
        $generation = LogoGeneration::create([
            'session_id' => 'sess_123',
            'business_name' => 'TechFlow',
            'total_logos_requested' => 12,
            'logos_completed' => 6,
        ]);

        expect($generation->getCompletionPercentage())->toBe(50);
    });

    it('can mark as failed with error message', function (): void {
        $generation = LogoGeneration::create([
            'session_id' => 'sess_123',
            'business_name' => 'TechFlow',
        ]);

        $generation->markAsFailed('API rate limit exceeded');

        expect($generation->fresh()->status)->toBe('failed')
            ->and($generation->fresh()->error_message)->toBe('API rate limit exceeded');
    });

    it('can increment logos completed count', function (): void {
        $generation = LogoGeneration::create([
            'session_id' => 'sess_123',
            'business_name' => 'TechFlow',
            'logos_completed' => 5,
        ]);

        $generation->incrementLogosCompleted();

        expect($generation->fresh()->logos_completed)->toBe(6);
    });

    it('can add to cost tracking', function (): void {
        $generation = LogoGeneration::create([
            'session_id' => 'sess_123',
            'business_name' => 'TechFlow',
            'cost_cents' => 100,
        ]);

        $generation->addCost(50);

        expect($generation->fresh()->cost_cents)->toBe(150);
    });
});
