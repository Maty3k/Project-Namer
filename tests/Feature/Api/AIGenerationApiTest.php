<?php

declare(strict_types=1);

use App\Models\GenerationSession;
use App\Models\Project;
use App\Models\User;
use App\Models\UserAIPreferences;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Queue::fake();
});

describe('POST /api/ai/generate-names', function (): void {
    it('generates names with valid request data', function (): void {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson('/api/ai/generate-names', [
            'project_id' => $project->id,
            'business_description' => 'A tech startup for AI automation',
            'models' => ['gpt-4', 'claude-3.5-sonnet'],
            'generation_mode' => 'creative',
            'deep_thinking' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'session_id',
            'message',
        ]);
        expect($response->json('success'))->toBeTrue();
        expect($response->json('session_id'))->toStartWith('session_');
    });

    it('validates required fields', function (): void {
        $response = $this->postJson('/api/ai/generate-names', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'project_id',
            'business_description',
            'models',
        ]);
    });

    it('validates business description length', function (): void {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson('/api/ai/generate-names', [
            'project_id' => $project->id,
            'business_description' => str_repeat('a', 2001),
            'models' => ['gpt-4'],
            'generation_mode' => 'creative',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['business_description']);
    });

    it('validates model names', function (): void {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson('/api/ai/generate-names', [
            'project_id' => $project->id,
            'business_description' => 'Test description',
            'models' => ['invalid-model'],
            'generation_mode' => 'creative',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['models.0']);
    });

    it('validates generation mode', function (): void {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson('/api/ai/generate-names', [
            'project_id' => $project->id,
            'business_description' => 'Test description',
            'models' => ['gpt-4'],
            'generation_mode' => 'invalid-mode',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['generation_mode']);
    });

    it('prevents access to other users projects', function (): void {
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->postJson('/api/ai/generate-names', [
            'project_id' => $project->id,
            'business_description' => 'Test description',
            'models' => ['gpt-4'],
            'generation_mode' => 'creative',
        ]);

        $response->assertStatus(403);
    });

    it('creates generation session with correct data', function (): void {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson('/api/ai/generate-names', [
            'project_id' => $project->id,
            'business_description' => 'AI automation platform',
            'models' => ['gpt-4', 'claude-3.5-sonnet'],
            'generation_mode' => 'professional',
            'deep_thinking' => true,
        ]);

        $response->assertStatus(200);

        $sessionId = $response->json('session_id');
        $session = GenerationSession::where('session_id', $sessionId)->first();

        expect($session)->not->toBeNull();
        expect($session->business_description)->toBe('AI automation platform');
        expect($session->requested_models)->toBe(['gpt-4', 'claude-3.5-sonnet']);
        expect($session->generation_mode)->toBe('professional');
        expect($session->deep_thinking)->toBeTrue();
    });
});

describe('GET /api/ai/generation/{id}', function (): void {
    it('returns generation status and results', function (): void {
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'business_description' => 'Test platform',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
            'results' => [
                'gpt-4' => [
                    'names' => ['TechFlow', 'DataVibe'],
                    'status' => 'completed',
                ],
            ],
        ]);

        $response = $this->getJson("/api/ai/generation/{$session->session_id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'session_id',
            'status',
            'progress_percentage',
            'current_step',
            'results',
            'error_message',
        ]);
        expect($response->json('status'))->toBe('completed');
        expect($response->json('results.gpt-4.names'))->toContain('TechFlow');
    });

    it('returns 404 for non-existent generation', function (): void {
        $response = $this->getJson('/api/ai/generation/non-existent-id');

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Generation session not found',
        ]);
    });

    it('tracks progress for running generations', function (): void {
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'user_id' => $this->user->id,
            'status' => 'running',
            'business_description' => 'Test platform',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
            'progress_percentage' => 50,
            'current_step' => 'Generating names with GPT-4...',
        ]);

        $response = $this->getJson("/api/ai/generation/{$session->session_id}");

        $response->assertStatus(200);
        expect($response->json('status'))->toBe('running');
        expect($response->json('progress_percentage'))->toBe(50);
        expect($response->json('current_step'))->toBe('Generating names with GPT-4...');
    });
});

describe('POST /api/ai/cancel-generation/{id}', function (): void {
    it('cancels a running generation', function (): void {
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'user_id' => $this->user->id,
            'status' => 'running',
            'business_description' => 'Test platform',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
        ]);

        $response = $this->postJson("/api/ai/cancel-generation/{$session->session_id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Generation cancelled successfully',
        ]);

        $session->refresh();
        expect($session->status)->toBe('cancelled');
    });

    it('returns error for completed generation', function (): void {
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'business_description' => 'Test platform',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
        ]);

        $response = $this->postJson("/api/ai/cancel-generation/{$session->session_id}");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Cannot cancel a completed generation',
        ]);
    });

    it('returns 404 for non-existent generation', function (): void {
        $response = $this->postJson('/api/ai/cancel-generation/non-existent-id');

        $response->assertStatus(404);
    });
});

describe('GET /api/ai/models', function (): void {
    it('returns available AI models and their status', function (): void {
        $response = $this->getJson('/api/ai/models');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'models' => [
                '*' => [
                    'name',
                    'display_name',
                    'available',
                    'description',
                    'capabilities',
                ],
            ],
        ]);

        $models = $response->json('models');
        expect($models)->toHaveKey('gpt-4');
        expect($models)->toHaveKey('claude-3.5-sonnet');
        expect($models)->toHaveKey('gemini-1.5-pro');
        expect($models)->toHaveKey('grok-beta');
    });

    it('includes user preferences if available', function (): void {
        UserAIPreferences::create([
            'user_id' => $this->user->id,
            'preferred_models' => ['gpt-4', 'claude-3.5-sonnet'],
            'default_generation_mode' => 'professional',
            'default_deep_thinking' => true,
        ]);

        $response = $this->getJson('/api/ai/models');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'models',
            'user_preferences' => [
                'preferred_models',
                'default_generation_mode',
                'default_deep_thinking',
            ],
        ]);
        expect($response->json('user_preferences.preferred_models'))->toBe(['gpt-4', 'claude-3.5-sonnet']);
    });
});

describe('PUT /api/ai/preferences', function (): void {
    it('updates user AI preferences', function (): void {
        $response = $this->putJson('/api/ai/preferences', [
            'preferred_models' => ['claude-3.5-sonnet', 'gpt-4'],
            'default_generation_mode' => 'brandable',
            'default_deep_thinking' => true,
            'auto_select_best_model' => false,
            'enable_model_comparison' => true,
            'max_concurrent_generations' => 2,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Preferences updated successfully',
        ]);

        $preferences = UserAIPreferences::where('user_id', $this->user->id)->first();
        expect($preferences->preferred_models)->toBe(['claude-3.5-sonnet', 'gpt-4']);
        expect($preferences->default_generation_mode)->toBe('brandable');
        expect($preferences->default_deep_thinking)->toBeTrue();
    });

    it('validates preferred models', function (): void {
        $response = $this->putJson('/api/ai/preferences', [
            'preferred_models' => ['invalid-model'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['preferred_models.0']);
    });

    it('validates generation mode', function (): void {
        $response = $this->putJson('/api/ai/preferences', [
            'default_generation_mode' => 'invalid-mode',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['default_generation_mode']);
    });

    it('creates preferences if not exists', function (): void {
        expect(UserAIPreferences::where('user_id', $this->user->id)->exists())->toBeFalse();

        $response = $this->putJson('/api/ai/preferences', [
            'preferred_models' => ['gpt-4'],
            'default_generation_mode' => 'creative',
        ]);

        $response->assertStatus(200);
        expect(UserAIPreferences::where('user_id', $this->user->id)->exists())->toBeTrue();
    });
});

describe('GET /api/ai/preferences', function (): void {
    it('returns current user preferences', function (): void {
        UserAIPreferences::create([
            'user_id' => $this->user->id,
            'preferred_models' => ['gpt-4', 'claude-3.5-sonnet'],
            'default_generation_mode' => 'professional',
            'default_deep_thinking' => true,
            'auto_select_best_model' => true,
            'enable_model_comparison' => false,
            'max_concurrent_generations' => 3,
        ]);

        $response = $this->getJson('/api/ai/preferences');

        $response->assertStatus(200);
        $response->assertJson([
            'preferred_models' => ['gpt-4', 'claude-3.5-sonnet'],
            'default_generation_mode' => 'professional',
            'default_deep_thinking' => true,
            'auto_select_best_model' => true,
            'enable_model_comparison' => false,
            'max_concurrent_generations' => 3,
        ]);
    });

    it('returns default preferences if none exist', function (): void {
        $response = $this->getJson('/api/ai/preferences');

        $response->assertStatus(200);
        $response->assertJson([
            'preferred_models' => ['gpt-4', 'claude-3.5-sonnet'],
            'default_generation_mode' => 'creative',
            'default_deep_thinking' => false,
        ]);
    });
});

describe('GET /api/ai/history', function (): void {
    it('returns generation history with pagination', function (): void {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        // Create multiple generation sessions
        for ($i = 0; $i < 15; $i++) {
            GenerationSession::create([
                'session_id' => GenerationSession::generateSessionId(),
                'user_id' => $this->user->id,
                'status' => 'completed',
                'business_description' => "Test platform {$i}",
                'generation_mode' => 'creative',
                'deep_thinking' => false,
                'requested_models' => ['gpt-4'],
                'generation_strategy' => 'quick',
                'created_at' => now()->subDays($i),
            ]);
        }

        $response = $this->getJson('/api/ai/history');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'session_id',
                    'status',
                    'business_description',
                    'generation_mode',
                    'requested_models',
                    'created_at',
                ],
            ],
            'links',
            'meta' => [
                'current_page',
                'total',
                'per_page',
            ],
        ]);
        expect($response->json('meta.total'))->toBe(15);
        expect($response->json('data'))->toHaveCount(10); // Default pagination
    });

    it('filters history by project', function (): void {
        $project1 = Project::factory()->create(['user_id' => $this->user->id]);
        $project2 = Project::factory()->create(['user_id' => $this->user->id]);

        GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'business_description' => 'Project 1 generation',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
            'project_id' => $project1->id,
        ]);

        GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'business_description' => 'Project 2 generation',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
            'project_id' => $project2->id,
        ]);

        $response = $this->getJson("/api/ai/history?project_id={$project1->id}");

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.business_description'))->toBe('Project 1 generation');
    });

    it('filters history by status', function (): void {
        GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'business_description' => 'Completed generation',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
        ]);

        GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'user_id' => $this->user->id,
            'status' => 'failed',
            'business_description' => 'Failed generation',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
            'error_message' => 'API error',
        ]);

        $response = $this->getJson('/api/ai/history?status=failed');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.status'))->toBe('failed');
    });
});

describe('API Authentication', function (): void {
    it('requires authentication for all endpoints', function (): void {
        // First create a session while authenticated
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'user_id' => $this->user->id,
            'status' => 'pending',
            'business_description' => 'Test platform',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
            'project_id' => $project->id,
        ]);

        Auth::logout(); // Logout the user
        $this->app['session']->flush();

        // All endpoints should return 401 unauthorized when not authenticated
        $this->postJson('/api/ai/generate-names', [])->assertUnauthorized();

        // Session endpoints return 401 when not authenticated
        $this->getJson("/api/ai/generation/{$session->session_id}")->assertUnauthorized();
        $this->postJson("/api/ai/cancel-generation/{$session->session_id}")->assertUnauthorized();

        // Other endpoints should return 401 unauthorized
        $this->getJson('/api/ai/models')->assertUnauthorized();
        $this->getJson('/api/ai/preferences')->assertUnauthorized();
        $this->getJson('/api/ai/history')->assertUnauthorized();

        // PUT /api/ai/preferences should also return 401 for empty body
        $this->putJson('/api/ai/preferences', [])->assertUnauthorized();
    });
});

describe('Rate Limiting', function (): void {
    it('applies rate limiting to generation endpoint', function (): void {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        // Make multiple requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/ai/generate-names', [
                'project_id' => $project->id,
                'business_description' => 'Test description',
                'models' => ['gpt-4'],
                'generation_mode' => 'creative',
            ]);

            if ($i < 5) {
                $response->assertStatus(200);
            }
        }

        // The 6th request should be rate limited
        $response = $this->postJson('/api/ai/generate-names', [
            'project_id' => $project->id,
            'business_description' => 'Test description',
            'models' => ['gpt-4'],
            'generation_mode' => 'creative',
        ]);

        $response->assertStatus(429); // Too Many Requests
    });
});
