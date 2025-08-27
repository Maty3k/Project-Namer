<?php

declare(strict_types=1);

use App\Models\NamingSession;
use App\Models\SessionResult;

describe('SessionResult Model', function (): void {
    it('belongs to a naming session', function (): void {
        $session = NamingSession::factory()->create();
        $result = SessionResult::factory()->create(['session_id' => $session->id]);

        expect($result->session)->toBeInstanceOf(NamingSession::class);
        expect($result->session->id)->toBe($session->id);
    });

    it('casts json columns properly', function (): void {
        $result = SessionResult::factory()->create([
            'generated_names' => ['TechNova', 'CodeCraft', 'DevHub'],
            'domain_results' => [
                ['name' => 'technova', 'available' => true, 'extensions' => ['.com', '.io']],
                ['name' => 'codecraft', 'available' => false, 'extensions' => ['.com']],
            ],
            'selected_for_logos' => ['TechNova', 'CodeCraft'],
        ]);

        expect($result->generated_names)->toBeArray();
        expect($result->domain_results)->toBeArray();
        expect($result->selected_for_logos)->toBeArray();
        expect($result->generated_names)->toHaveCount(3);
        expect($result->domain_results)->toHaveCount(2);
        expect($result->selected_for_logos)->toHaveCount(2);
    });

    it('handles null selected_for_logos', function (): void {
        $result = SessionResult::factory()->create([
            'selected_for_logos' => null,
        ]);

        expect($result->selected_for_logos)->toBeNull();
    });

    it('timestamps generation correctly', function (): void {
        $result = SessionResult::factory()->create();

        expect($result->generation_timestamp)->not->toBeNull();
        expect($result->generation_timestamp)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('deletes when parent session is deleted', function (): void {
        $session = NamingSession::factory()->create();
        $result = SessionResult::factory()->create(['session_id' => $session->id]);

        expect(SessionResult::find($result->id))->not->toBeNull();

        $session->delete();

        expect(SessionResult::find($result->id))->toBeNull();
    });
});
