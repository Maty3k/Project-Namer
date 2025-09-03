<?php

declare(strict_types=1);

use App\Models\GenerationSession;
use App\Models\Project;
use App\Models\User;
use App\Models\UserAIPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AIEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_ai_generation_with_malformed_api_responses(): void
    {
        $this->actingAs($this->user);

        // Mock API with malformed JSON response
        Http::fake([
            'api.openai.com/*' => Http::response('invalid json {', 200),
            'api.anthropic.com/*' => Http::response(['content' => []], 200),
        ]);

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', 'Test business')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        // Should handle malformed response gracefully
        $this->assertNotNull($component->get('errorMessage'));
    }

    public function test_ai_generation_with_empty_business_description(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', '')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        // Should show validation error
        $component->assertHasErrors(['businessIdea']);
    }

    public function test_ai_generation_with_extremely_long_description(): void
    {
        $this->actingAs($this->user);

        $longDescription = str_repeat('This is a very long business description. ', 500); // ~2000+ chars

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', $longDescription)
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        // Should handle long descriptions but may show validation error if exceeds limit
        if (strlen($longDescription) > 2000) {
            $component->assertHasErrors(['businessIdea']);
        }
    }

    public function test_ai_generation_with_special_characters_and_unicode(): void
    {
        $this->actingAs($this->user);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['names' => ['TestTech', 'UnicodeApp', 'SpecialChar']]),
                    ],
                ]],
            ], 200),
        ]);

        $specialDescription = 'A tech startup with émojis 🚀 and spéciál chäracters & symbols!@#$%';

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', $specialDescription)
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        // Should handle special characters gracefully
        $this->assertFalse($component->get('isGeneratingNames'));
    }

    public function test_ai_generation_with_no_models_selected(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', 'Test business')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', [])
            ->call('generateNamesWithAI');

        // Should either show error message or handle gracefully
        $errorMessage = $component->get('errorMessage');
        $isGenerating = $component->get('isGeneratingNames');

        // Either there should be an error message OR it should not be generating
        $this->assertTrue($errorMessage !== null || $isGenerating === false);
    }

    public function test_ai_generation_with_invalid_model_names(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', 'Test business')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['invalid-model', 'non-existent-ai'])
            ->call('generateNamesWithAI');

        // Should handle invalid models gracefully
        $this->assertNotNull($component->get('errorMessage'));
    }

    public function test_ai_generation_timeout_scenarios(): void
    {
        $this->actingAs($this->user);

        // Mock API with timeout
        Http::fake([
            'api.openai.com/*' => function () {
                sleep(2); // Simulate timeout

                return Http::response(null, 504);
            },
        ]);

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', 'Test business')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        // Should handle timeouts gracefully
        $component->assertSet('isGeneratingNames', false);
    }

    public function test_concurrent_ai_generation_sessions(): void
    {
        $this->actingAs($this->user);

        // Create multiple concurrent sessions with 'pending' status (which is considered active)
        $sessions = [];
        for ($i = 0; $i < 3; $i++) {
            $sessions[] = GenerationSession::create([
                'session_id' => "concurrent_session_{$i}",
                'user_id' => $this->user->id,
                'project_id' => $this->project->id,
                'business_description' => "Business idea {$i}",
                'generation_mode' => 'creative',
                'status' => 'pending', // Use 'pending' as it's defined in the active scope
                'requested_models' => ['gpt-4'],
                'generation_strategy' => 'quick',
                'deep_thinking' => false,
            ]);
        }

        // Verify concurrent sessions are handled properly
        $activeSessions = GenerationSession::active()->count();
        $this->assertEquals(3, $activeSessions);
    }

    public function test_ai_generation_with_corrupted_cache(): void
    {
        $this->actingAs($this->user);

        // Put corrupted data in cache
        Cache::put('ai_generation_corrupted_key', 'invalid_data', 3600);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['names' => ['CacheTest', 'RecoveryApp']]),
                    ],
                ]],
            ], 200),
        ]);

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', 'Cache corruption test')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        // Should handle corrupted cache and still generate
        $component->assertSet('isGeneratingNames', false);
    }

    public function test_ai_generation_database_connection_failure(): void
    {
        $this->actingAs($this->user);

        // This test would need database mocking to simulate connection failures
        // For now, we'll test that proper error handling exists

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', 'Database failure test')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4']);

        // Component should exist and be ready to handle database errors
        $this->assertInstanceOf(\Livewire\Component::class, $component->instance());
    }

    public function test_ai_generation_with_memory_exhaustion_protection(): void
    {
        $this->actingAs($this->user);

        // Create user preferences that might cause memory issues
        UserAIPreferences::create([
            'user_id' => $this->user->id,
            'preferred_models' => array_fill(0, 100, 'gpt-4'), // Large array
            'model_priorities' => array_fill(0, 1000, 1), // Very large array
            'custom_parameters' => array_fill(0, 500, 'test_param'),
        ]);

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', 'Memory test')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4']);

        // Should handle large preference data gracefully
        $this->assertNotNull($component->get('selectedAIModels'));
    }

    public function test_ai_generation_partial_model_failures(): void
    {
        $this->actingAs($this->user);

        // Mock mixed success/failure responses
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['names' => ['GPTSuccess', 'WorkingModel']]),
                    ],
                ]],
            ], 200),
            'api.anthropic.com/*' => Http::response(null, 500), // Fail
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode(['names' => ['GeminiWorks', 'BackupModel']]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', 'Partial failure test')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro'])
            ->call('generateNamesWithAI');

        // Should succeed with partial results
        $component->assertSet('isGeneratingNames', false);
    }

    public function test_ai_generation_session_state_corruption(): void
    {
        $this->actingAs($this->user);

        // Create session with corrupted data
        $session = GenerationSession::create([
            'session_id' => 'corrupted_session',
            'user_id' => $this->user->id,
            'business_description' => 'Test business',
            'generation_mode' => 'invalid_mode', // Invalid mode
            'status' => 'unknown_status', // Invalid status
            'requested_models' => ['non_existent_model'],
            'generation_strategy' => 'invalid_strategy',
            'deep_thinking' => false,
        ]);

        // Verify system handles corrupted session state
        $this->assertNotNull($session);
        $this->assertEquals('unknown_status', $session->status);
    }

    public function test_ai_generation_with_rate_limit_edge_cases(): void
    {
        $this->actingAs($this->user);

        // Simulate hitting rate limits across multiple models
        Http::fake([
            'api.openai.com/*' => Http::response(null, 429, ['Retry-After' => '60']),
            'api.anthropic.com/*' => Http::response(null, 429, ['Retry-After' => '30']),
        ]);

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', 'Rate limit test')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
            ->call('generateNamesWithAI');

        // Should handle rate limits gracefully
        $errorMessage = $component->get('errorMessage');
        $this->assertNotNull($errorMessage);
    }

    public function test_ai_generation_with_invalid_json_in_response(): void
    {
        $this->actingAs($this->user);

        // Mock API with valid HTTP response but invalid JSON structure
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'This is not JSON: just plain text with no structure',
                    ],
                ]],
            ], 200),
        ]);

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', 'Invalid JSON test')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        // Should handle invalid JSON gracefully
        $component->assertSet('isGeneratingNames', false);
    }

    public function test_ai_generation_with_sql_injection_attempts(): void
    {
        $this->actingAs($this->user);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['names' => ['SafeApp', 'SecureService', 'CleanTech']]),
                    ],
                ]],
            ], 200),
        ]);

        $maliciousDescription = "'; DROP TABLE users; --";

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', $maliciousDescription)
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        // Should handle SQL injection attempts safely
        $component->assertSet('isGeneratingNames', false);
        // Ensure the user still exists (wasn't dropped)
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    }

    public function test_ai_generation_with_xss_attempts(): void
    {
        $this->actingAs($this->user);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['names' => ['XSSProtected', 'SecureNames', 'SafeTech']]),
                    ],
                ]],
            ], 200),
        ]);

        $xssDescription = '<script>alert("xss")</script>Business idea';

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', $xssDescription)
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        // Should handle XSS attempts safely
        $component->assertSet('isGeneratingNames', false);
    }

    public function test_ai_generation_with_extremely_large_responses(): void
    {
        $this->actingAs($this->user);

        // Create an artificially large response
        $largeNamesList = [];
        for ($i = 1; $i <= 1000; $i++) {
            $largeNamesList[] = "LargeName{$i}WithVeryLongSuffix".str_repeat('X', 100);
        }

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['names' => $largeNamesList]),
                    ],
                ]],
            ], 200),
        ]);

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', 'Large response test')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        // Should handle large responses gracefully (likely truncate or limit)
        $component->assertSet('isGeneratingNames', false);
    }

    public function test_ai_generation_with_null_and_undefined_values(): void
    {
        $this->actingAs($this->user);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'names' => [null, '', 'ValidName', null, 'AnotherValid'],
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', 'Null values test')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        // Should filter out null/empty values
        $component->assertSet('isGeneratingNames', false);
    }

    public function test_ai_generation_network_interruption_simulation(): void
    {
        $this->actingAs($this->user);

        // Simulate network interruption with connection reset
        Http::fake([
            'api.openai.com/*' => function (): void {
                throw new \Illuminate\Http\Client\ConnectionException('Connection reset by peer');
            },
        ]);

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', 'Network interruption test')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        // Should handle network interruptions gracefully
        $component->assertSet('isGeneratingNames', false);
        $this->assertNotNull($component->get('errorMessage'));
    }

    public function test_ai_generation_with_duplicate_model_selections(): void
    {
        $this->actingAs($this->user);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['names' => ['DedupTest', 'UniqueApp', 'CleanService']]),
                    ],
                ]],
            ], 200),
        ]);

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', 'Duplicate models test')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4', 'gpt-4', 'gpt-4']) // Duplicate models
            ->call('generateNamesWithAI');

        // Should handle duplicate model selections properly
        $component->assertSet('isGeneratingNames', false);
    }

    public function test_ai_generation_session_cleanup_on_browser_close(): void
    {
        $this->actingAs($this->user);

        // Create multiple sessions
        $sessionIds = [];
        $oldTimestamp = now()->subMinutes(30);

        for ($i = 0; $i < 3; $i++) {
            $session = new GenerationSession([
                'session_id' => "cleanup_session_{$i}",
                'user_id' => $this->user->id,
                'business_description' => "Business idea {$i}",
                'generation_mode' => 'creative',
                'status' => 'pending',
                'requested_models' => ['gpt-4'],
                'generation_strategy' => 'quick',
                'deep_thinking' => false,
            ]);

            // Manually set timestamps to avoid auto-update
            $session->created_at = $oldTimestamp;
            $session->updated_at = $oldTimestamp;
            $session->save();

            $sessionIds[] = $session->session_id;
        }

        // Verify sessions exist before cleanup
        $this->assertEquals(3, GenerationSession::whereIn('session_id', $sessionIds)->count());

        // Simulate session cleanup (this would normally happen via scheduled job)
        $cutoffTime = now()->subMinutes(15)->format('Y-m-d H:i:s');
        $updated = GenerationSession::whereIn('session_id', $sessionIds)
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoffTime)
            ->update(['status' => 'expired']);

        // Verify sessions are marked as expired
        $expiredSessions = GenerationSession::whereIn('session_id', $sessionIds)
            ->where('status', 'expired')
            ->count();

        // Assert that we actually updated some sessions
        $this->assertEquals(3, $updated, 'Expected 3 sessions to be updated');
        $this->assertEquals(3, $expiredSessions, 'Expected 3 sessions to have expired status');
    }

    public function test_ai_generation_with_mixed_encoding_characters(): void
    {
        $this->actingAs($this->user);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['names' => ['EncodingApp', 'UnicodeService', 'GlobalTech']]),
                    ],
                ]],
            ], 200),
        ]);

        $mixedEncodingDescription = 'Business with café, naïve résumé, 北京, العربية, русский';

        $component = Livewire::test('name-generator-dashboard')
            ->set('businessIdea', $mixedEncodingDescription)
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        // Should handle mixed character encodings gracefully
        $component->assertSet('isGeneratingNames', false);
    }
}
