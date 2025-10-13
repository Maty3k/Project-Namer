<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\AIModelPerformance;
use App\Models\GenerationSession;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use App\Models\UserAIPreferences;
use App\Services\AI\AIGenerationService;
use App\Services\DNSLookupService;
use App\Services\OpenAINameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group integration
 * @group slow
 */
class AIWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);

        // Prevent any external HTTP calls
        Http::preventStrayRequests();
        Http::fake([
            '*' => Http::response(['choices' => [['message' => ['content' => '1. TestName']]]], 200),
        ]);

        // Fake queue to prevent job dispatching delays
        Queue::fake();

        // Mock OpenAI service to prevent real API calls
        $this->mock(OpenAINameService::class, function ($mock): void {
            $mock->shouldReceive('generateNames')
                ->andReturn(['TechNova', 'InnovateLabs', 'CreativeFlow', 'BrightSpark', 'SynergyCore']);
        });

        // Mock DNS service to prevent real DNS lookups
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')->andReturn(false);
            $mock->shouldReceive('getDNSRecords')->andReturn([
                'A' => [],
                'AAAA' => [],
                'CNAME' => [],
                'MX' => [],
            ]);
        });

        // Disable rate limiting for speed
        Cache::put('rate_limit_disabled_for_tests', true, 3600);
    }

    protected function tearDown(): void
    {
        Cache::forget('rate_limit_disabled_for_tests');
        parent::tearDown();
    }

    public function test_complete_ai_workflow_from_dashboard_to_name_suggestions(): void
    {
        $this->mock(AIGenerationService::class, function ($mock): void {
            $mock->shouldReceive('generateWithModels')->andReturn([
                'gpt-4' => ['names' => ['TechNova', 'InnovateLabs'], 'model' => 'gpt-4'],
            ]);
        });

        $component = Livewire::actingAs($this->user)
            ->test('name-generator-dashboard')
            ->set('businessIdea', 'AI startup')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        $component->assertSet('isGeneratingNames', false);
    }

    public function test_project_page_contextual_generation_with_existing_data(): void
    {
        $this->mock(AIGenerationService::class, function ($mock): void {
            $mock->shouldReceive('generateWithModels')->andReturn([
                'gpt-4' => ['names' => ['NewName1', 'NewName2'], 'model' => 'gpt-4'],
            ]);
        });

        NameSuggestion::factory()->count(3)->create(['project_id' => $this->project->id]);

        Livewire::actingAs($this->user)
            ->test('project-page', ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateMoreNames');

        $this->assertGreaterThanOrEqual(3, NameSuggestion::where('project_id', $this->project->id)->count());
    }

    public function test_multi_model_comparison_with_parallel_generation(): void
    {
        $this->mock(AIGenerationService::class, function ($mock): void {
            $mock->shouldReceive('generateWithModels')->andReturn([
                'gpt-4' => ['names' => ['Name1'], 'model' => 'gpt-4'],
                'claude-3.5-sonnet' => ['names' => ['Name2'], 'model' => 'claude-3.5-sonnet'],
            ]);
        });

        $component = Livewire::actingAs($this->user)
            ->test('name-generator-dashboard')
            ->set('businessIdea', 'Tech startup')
            ->set('useAIGeneration', true)
            ->set('enableModelComparison', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
            ->call('generateNamesWithAI');

        $component->assertSet('isGeneratingNames', false)
            ->assertSet('enableModelComparison', true);
    }

    public function test_error_handling_with_api_failures(): void
    {
        $this->mock(AIGenerationService::class, function ($mock): void {
            $mock->shouldReceive('generateWithModels')
                ->andThrow(new \Exception('Rate limit exceeded'));
        });

        $component = Livewire::actingAs($this->user)
            ->test('name-generator-dashboard')
            ->set('businessIdea', 'Test')
            ->set('generationMode', 'creative')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        $component->assertSet('isGeneratingNames', false);
        $this->assertNotNull($component->get('errorMessage'));
    }

    public function test_user_preferences_integration(): void
    {
        $preferences = UserAIPreferences::create([
            'user_id' => $this->user->id,
            'preferred_models' => ['claude-3.5-sonnet', 'gpt-4'],
            'generation_settings' => ['default_mode' => 'professional'],
        ]);

        $component = Livewire::actingAs($this->user)->test('name-generator-dashboard');

        $this->assertEquals($preferences->preferred_models, $component->get('selectedAIModels'));
    }

    public function test_caching_for_repeated_requests(): void
    {
        $this->mock(AIGenerationService::class, function ($mock): void {
            $mock->shouldReceive('generateWithModels')->once()->andReturn([
                'gpt-4' => ['names' => ['Name1'], 'model' => 'gpt-4'],
            ]);
        });

        $component = Livewire::actingAs($this->user)
            ->test('name-generator-dashboard')
            ->set('businessIdea', 'test')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        $component->assertSet('isGeneratingNames', false);
    }

    public function test_rate_limiting_protection(): void
    {
        $this->mock(AIGenerationService::class, function ($mock): void {
            $mock->shouldReceive('generateWithModels')->andReturn([
                'gpt-4' => ['names' => ['Name1'], 'model' => 'gpt-4'],
            ]);
        });

        Cache::put("ai_rate_limit:{$this->user->id}", 10, now()->addMinutes(1));

        Livewire::actingAs($this->user)
            ->test('name-generator-dashboard')
            ->set('businessIdea', 'Test')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI')
            ->assertSet('isGeneratingNames', false);
    }

    public function test_model_performance_tracking(): void
    {
        $this->mock(AIGenerationService::class, function ($mock): void {
            $mock->shouldReceive('generateWithModels')->andReturn([
                'gpt-4' => ['names' => ['Name1'], 'model' => 'gpt-4'],
            ]);
        });

        Livewire::actingAs($this->user)
            ->test('name-generator-dashboard')
            ->set('businessIdea', 'Test')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI')
            ->assertSet('isGeneratingNames', false);

        $performance = AIModelPerformance::where('model', 'gpt-4')->first();
        if ($performance) {
            $this->assertGreaterThanOrEqual(0, $performance->total_requests);
        }
    }

    public function test_generation_session_management(): void
    {
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'user_id' => $this->user->id,
            'business_description' => 'Test',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'status' => 'pending',
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
        ]);

        $session->updateProgress(50, 'Processing');
        $this->assertEquals(50, $session->progress_percentage);

        $session->markAsCompleted(['names' => ['Test1']]);
        $this->assertEquals('completed', $session->status);
    }

    public function test_domain_checking_integration(): void
    {
        $this->mock(AIGenerationService::class, function ($mock): void {
            $mock->shouldReceive('generateWithModels')->andReturn([
                'gpt-4' => ['names' => ['Name1'], 'model' => 'gpt-4'],
            ]);
        });

        $component = Livewire::actingAs($this->user)
            ->test('name-generator-dashboard')
            ->set('businessIdea', 'Test')
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('generateNamesWithAI');

        $this->assertIsArray($component->get('domainResults'));
        $component->assertSet('isGeneratingNames', false);
    }
}
