<?php

declare(strict_types=1);

use App\Models\NamingSession;
use App\Models\SessionResult;
use App\Models\User;
use Illuminate\Support\Str;

describe('NamingSession Model', function (): void {
    it('generates uuid on creation', function (): void {
        $session = NamingSession::factory()->create();

        expect($session->id)->not->toBeNull();
        expect(Str::isUuid($session->id))->toBeTrue();
    });

    it('belongs to a user', function (): void {
        $user = User::factory()->create();
        $session = NamingSession::factory()->create(['user_id' => $user->id]);

        expect($session->user)->toBeInstanceOf(User::class);
        expect($session->user->id)->toBe($user->id);
    });

    it('has many session results', function (): void {
        $session = NamingSession::factory()->create();
        SessionResult::factory()->count(3)->create(['session_id' => $session->id]);

        expect($session->results)->toHaveCount(3);
        expect($session->results->first())->toBeInstanceOf(SessionResult::class);
    });

    it('auto generates title from business description', function (): void {
        $session = NamingSession::factory()->create([
            'business_description' => 'An AI-powered tool for generating business names with domain checking',
            'title' => null,
        ]);

        // Should auto-generate title from first 50 chars of description
        expect($session->title)->not->toBeNull();
        expect($session->title)->toContain('AI-powered tool');
        expect(strlen($session->title))->toBeLessThanOrEqual(53); // 50 + "..."
    });

    it('casts json columns properly', function (): void {
        $session = NamingSession::factory()->create();

        expect($session->deep_thinking)->toBeBool();
        expect($session->is_starred)->toBeBool();
        expect($session->is_active)->toBeBool();
    });

    it('has starred scope', function (): void {
        $user = User::factory()->create();
        NamingSession::factory()->count(3)->create(['user_id' => $user->id, 'is_starred' => false]);
        NamingSession::factory()->count(2)->create(['user_id' => $user->id, 'is_starred' => true]);

        $starred = NamingSession::starred()->where('user_id', $user->id)->get();

        expect($starred)->toHaveCount(2);
        expect($starred->every(fn ($s) => $s->is_starred === true))->toBeTrue();
    });

    it('has recent scope', function (): void {
        $user = User::factory()->create();
        $old = NamingSession::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(10),
        ]);
        $recent = NamingSession::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDay(),
        ]);
        $newest = NamingSession::factory()->create([
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $results = NamingSession::recent()->where('user_id', $user->id)->get();

        expect($results->first()->id)->toBe($newest->id);
        expect($results->last()->id)->toBe($old->id);
    });

    it('updates last accessed at on load', function (): void {
        $session = NamingSession::factory()->create(['last_accessed_at' => null]);

        $session->markAccessed();

        expect($session->fresh()->last_accessed_at)->not->toBeNull();
        expect($session->fresh()->last_accessed_at->timestamp)->toBeGreaterThanOrEqual(now()->timestamp - 1);
        expect($session->fresh()->last_accessed_at->timestamp)->toBeLessThanOrEqual(now()->timestamp + 1);
    });

    it('cascades delete to session results', function (): void {
        $session = NamingSession::factory()->create();
        SessionResult::factory()->count(3)->create(['session_id' => $session->id]);

        expect(SessionResult::where('session_id', $session->id)->get())->toHaveCount(3);

        $session->delete();

        expect(SessionResult::where('session_id', $session->id)->get())->toHaveCount(0);
    });

    it('generates preview text', function (): void {
        $session = NamingSession::factory()->create([
            'business_description' => 'This is a very long business description that should be truncated for preview display in the sidebar to keep things clean and readable',
        ]);

        $preview = $session->getPreviewText();

        expect(strlen($preview))->toBeLessThanOrEqual(80);
        expect($preview)->toEndWith('...');
    });
});
