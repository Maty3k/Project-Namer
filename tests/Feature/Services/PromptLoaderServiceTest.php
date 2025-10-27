<?php

declare(strict_types=1);

use App\Exceptions\InvalidPromptException;
use App\Exceptions\PromptNotFoundException;
use App\Services\PromptLoaderService;
use Illuminate\Support\Facades\Cache;
use Prism\Prism\Enums\Provider;

uses()->group('unit');

beforeEach(function () {
    // Clear cache before each test
    Cache::flush();

    // Create service instance with test fixtures path
    $this->promptLoader = new PromptLoaderService(base_path('tests/fixtures/prompts'));
});

describe('PromptLoaderService Basic Loading', function () {
    test('it loads valid prompt markdown file with complete frontmatter', function () {
        $promptData = $this->promptLoader->load('test-valid-prompt');

        expect($promptData->provider)->toBe(Provider::OpenAI)
            ->and($promptData->model)->toBe('gpt-4o')
            ->and($promptData->temperature)->toBe(0.7)
            ->and($promptData->maxTokens)->toBe(200)
            ->and($promptData->deepThinkingTemperature)->toBe(0.3)
            ->and($promptData->description)->toBe('Test prompt with all fields')
            ->and($promptData->promptText)->toContain('This is a test prompt');
    });

    test('it loads minimal prompt with only required fields', function () {
        $promptData = $this->promptLoader->load('test-minimal-prompt');

        expect($promptData->provider)->toBe(Provider::Anthropic)
            ->and($promptData->model)->toBe('claude-3-5-sonnet-20241022')
            ->and($promptData->temperature)->toBeNull()
            ->and($promptData->maxTokens)->toBeNull()
            ->and($promptData->promptText)->toContain('Minimal prompt');
    });

    test('it extracts prompt text without frontmatter delimiters', function () {
        $promptData = $this->promptLoader->load('test-valid-prompt');

        expect($promptData->promptText)->not->toContain('---')
            ->and($promptData->promptText)->not->toContain('provider:')
            ->and($promptData->promptText)->not->toContain('model:');
    });

    test('it maps provider string to Provider enum', function () {
        $promptData = $this->promptLoader->load('test-valid-prompt');

        expect($promptData->provider)->toBeInstanceOf(Provider::class)
            ->and($promptData->provider->value)->toBe('openai');
    });
});

describe('PromptLoaderService Caching', function () {
    test('it caches loaded prompts for 1 hour', function () {
        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl, $callback) {
                return str_contains($key, 'test-valid-prompt') && $ttl === 3600;
            })
            ->andReturn($this->promptLoader->load('test-valid-prompt'));

        $this->promptLoader->loadWithCache('test-valid-prompt');
    });

    test('it returns cached prompt on subsequent loads', function () {
        // First load - should hit file system
        $first = $this->promptLoader->loadWithCache('test-valid-prompt');

        // Second load - should return cached
        $second = $this->promptLoader->loadWithCache('test-valid-prompt');

        expect($first->promptText)->toBe($second->promptText);
    });

    test('it clears cache for specific prompt', function () {
        // Load and cache
        $this->promptLoader->loadWithCache('test-valid-prompt');

        // Clear cache
        $this->promptLoader->clearCache('test-valid-prompt');

        // Verify cache is cleared by checking cache directly
        $cacheKey = 'prompt:test-valid-prompt:parsed';
        expect(Cache::has($cacheKey))->toBeFalse();
    });
});

describe('PromptLoaderService Error Handling', function () {
    test('it throws PromptNotFoundException when file does not exist', function () {
        $this->promptLoader->load('non-existent-file');
    })->throws(PromptNotFoundException::class, 'Prompt file not found');

    test('it throws InvalidPromptException when YAML is malformed', function () {
        $this->promptLoader->load('test-invalid-yaml');
    })->throws(InvalidPromptException::class, 'Invalid YAML frontmatter');

    test('it throws InvalidPromptException when provider field is missing', function () {
        $this->promptLoader->load('test-missing-provider');
    })->throws(InvalidPromptException::class, 'Missing required field \'provider\'');

    test('it throws InvalidPromptException when model field is missing', function () {
        $this->promptLoader->load('test-missing-model');
    })->throws(InvalidPromptException::class, 'Missing required field \'model\'');

    test('it throws InvalidPromptException when provider value is invalid', function () {
        $this->promptLoader->load('test-invalid-provider');
    })->throws(InvalidPromptException::class);
});

describe('PromptLoaderService Config Source Resolution', function () {
    test('it loads user prompt with config_source and resolves config from system prompt', function () {
        $promptData = $this->promptLoader->load('test-user-prompt-with-config-source');

        // Should use config from test-system-prompt.md
        expect($promptData->provider)->toBe(Provider::Gemini)
            ->and($promptData->model)->toBe('gemini-1.5-pro')
            ->and($promptData->temperature)->toBe(0.8)
            ->and($promptData->maxTokens)->toBe(300)
            // But prompt text from test-user-prompt-with-config-source.md
            ->and($promptData->promptText)->toContain('Business concept: {$businessIdea}');
    });

    test('it throws PromptNotFoundException when config_source file does not exist', function () {
        $this->promptLoader->load('test-config-source-not-found');
    })->throws(PromptNotFoundException::class, 'Config source file not found');

    test('it throws InvalidPromptException when config_source references itself', function () {
        $this->promptLoader->load('test-config-source-self-reference');
    })->throws(InvalidPromptException::class, 'Config source cannot reference itself');

    test('it caches both user and system prompts separately', function () {
        // Load user prompt (which references system prompt)
        $this->promptLoader->loadWithCache('test-user-prompt-with-config-source');

        // Both should be cached
        $userCacheKey = 'prompt:test-user-prompt-with-config-source:parsed';
        $systemCacheKey = 'prompt:test-system-prompt:parsed';

        expect(Cache::has($userCacheKey))->toBeTrue()
            ->and(Cache::has($systemCacheKey))->toBeTrue();
    });
});

describe('PromptLoaderService Interpolation', function () {
    test('it interpolates single variable in prompt text', function () {
        $promptData = $this->promptLoader->load('test-interpolation');

        $interpolated = $this->promptLoader->interpolate($promptData->promptText, [
            'businessName' => 'TechCorp',
        ]);

        expect($interpolated)->toContain('TechCorp')
            ->and($interpolated)->not->toContain('{$businessName}');
    });

    test('it interpolates multiple variables in prompt text', function () {
        $promptData = $this->promptLoader->load('test-interpolation');

        $interpolated = $this->promptLoader->interpolate($promptData->promptText, [
            'styleDescription' => 'minimalist, modern',
            'businessName' => 'BrandCo',
            'businessDescription' => 'provides cloud services',
        ]);

        expect($interpolated)->toContain('minimalist, modern')
            ->and($interpolated)->toContain('BrandCo')
            ->and($interpolated)->toContain('provides cloud services')
            ->and($interpolated)->not->toContain('{$');
    });

    test('it handles missing variables gracefully (leaves placeholder)', function () {
        $promptData = $this->promptLoader->load('test-interpolation');

        $interpolated = $this->promptLoader->interpolate($promptData->promptText, [
            'businessName' => 'TestCo',
            // businessDescription is missing
        ]);

        expect($interpolated)->toContain('TestCo')
            ->and($interpolated)->toContain('{$businessDescription}'); // Still present
    });

    test('it handles empty variable values', function () {
        $promptData = $this->promptLoader->load('test-interpolation');

        $interpolated = $this->promptLoader->interpolate($promptData->promptText, [
            'styleDescription' => '',
            'businessName' => 'EmptyTest',
            'businessDescription' => '',
        ]);

        expect($interpolated)->toContain('EmptyTest')
            ->and($interpolated)->toContain('Create a  logo') // Empty style
            ->and($interpolated)->toContain('that .'); // Empty description
    });

    test('it handles numeric variable values', function () {
        $template = 'Generate {$count} names for {$businessName}';

        $interpolated = $this->promptLoader->interpolate($template, [
            'count' => 10,
            'businessName' => 'NumTest',
        ]);

        expect($interpolated)->toBe('Generate 10 names for NumTest');
    });
});

describe('PromptLoaderService Validation', function () {
    test('it validates temperature is float', function () {
        // This is implicitly tested by successful loading of test-valid-prompt
        $promptData = $this->promptLoader->load('test-valid-prompt');

        expect($promptData->temperature)->toBeFloat();
    });

    test('it validates max_tokens is integer', function () {
        $promptData = $this->promptLoader->load('test-valid-prompt');

        expect($promptData->maxTokens)->toBeInt();
    });

    test('it handles optional deep_thinking_temperature field', function () {
        $promptData = $this->promptLoader->load('test-valid-prompt');

        expect($promptData->deepThinkingTemperature)->toBe(0.3)
            ->and($promptData->deepThinkingTemperature)->toBeFloat();
    });

    test('it handles optional description field', function () {
        $promptData = $this->promptLoader->load('test-valid-prompt');

        expect($promptData->description)->toBe('Test prompt with all fields');
    });
});

describe('PromptLoaderService Directory Operations', function () {
    test('it returns all available prompts in directory', function () {
        $prompts = $this->promptLoader->getAllPrompts();

        expect($prompts)->toBeArray()
            ->and($prompts)->toContain('test-valid-prompt')
            ->and($prompts)->toContain('test-minimal-prompt')
            ->and($prompts)->toContain('test-system-prompt');
    });
});
