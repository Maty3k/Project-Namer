<?php

declare(strict_types=1);

use App\Models\DomainCache;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;

describe('NameResultCard Dropdown Interaction', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);
    });

    it('triggers domain checking via API endpoint', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['extension' => '.com'],
                'testname.io' => ['extension' => '.io'],
            ],
        ]);

        // Call the API endpoint directly
        $response = $this->postJson("/api/suggestions/{$suggestion->id}/check-domains");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'already_checked' => false,
            ]);

        // Verify domains were checked and cached
        $cachedCom = DomainCache::where('domain', 'testname.com')->first();
        $cachedIo = DomainCache::where('domain', 'testname.io')->first();

        expect($cachedCom)->not->toBeNull();
        expect($cachedIo)->not->toBeNull();

        // Verify results were written back to suggestion
        $suggestion->refresh();
        expect($suggestion->domains['testname.com'])->toHaveKey('available');
        expect($suggestion->domains['testname.io'])->toHaveKey('available');
        expect($suggestion->domains['testname.com']['status'])->not->toBe('checking');
        expect($suggestion->domains['testname.io']['status'])->not->toBe('checking');
    });

    it('does not re-check domains if already checked', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => [
                    'extension' => '.com',
                    'available' => true,
                    'status' => 'available',
                ],
            ],
        ]);

        // First API call
        $response = $this->postJson("/api/suggestions/{$suggestion->id}/check-domains");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'already_checked' => true,
            ]);

        $firstCheck = DomainCache::where('domain', 'testname.com')->first();

        // Second API call (should return cached data)
        $secondResponse = $this->postJson("/api/suggestions/{$suggestion->id}/check-domains");

        $secondResponse->assertOk()
            ->assertJson([
                'success' => true,
                'already_checked' => true,
            ]);

        $secondCheck = DomainCache::where('domain', 'testname.com')->first();

        // Verify no new checks were made - timestamp should be the same
        if ($firstCheck && $secondCheck) {
            expect($secondCheck->checked_at->timestamp)->toBe($firstCheck->checked_at->timestamp);
        }
    });

    it('displays fresh domain data from database', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['extension' => '.com'],
            ],
        ]);

        // Call API to check domains
        $response = $this->postJson("/api/suggestions/{$suggestion->id}/check-domains");

        $response->assertOk();

        // Reload from database to verify domains were checked and updated
        $suggestion->refresh();
        $domains = $suggestion->domains;

        expect($domains)->toBeArray();
        expect($domains['testname.com'])->toHaveKey('available');
        expect($domains['testname.com'])->toHaveKey('status');
    });

    it('returns domain data in API response', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['extension' => '.com'],
            ],
        ]);

        $response = $this->postJson("/api/suggestions/{$suggestion->id}/check-domains");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'domains' => [
                    'testname.com' => [
                        'extension',
                        'available',
                        'status',
                    ],
                ],
                'already_checked',
            ]);
    });

    it('requires authentication to check domains', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'TestName',
            'domains' => [
                'testname.com' => ['extension' => '.com'],
            ],
        ]);

        // Logout
        auth()->logout();

        $response = $this->postJson("/api/suggestions/{$suggestion->id}/check-domains");

        $response->assertUnauthorized();
    });
});
