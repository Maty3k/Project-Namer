<?php

declare(strict_types=1);

use App\Services\PromptData;
use Prism\Prism\Enums\Provider;

describe('PromptData DTO', function (): void {
    test('it creates PromptData with required fields only', function (): void {
        $promptData = new PromptData(
            provider: Provider::OpenAI,
            model: 'gpt-4o',
            promptText: 'Test prompt text'
        );

        expect($promptData->provider)->toBe(Provider::OpenAI)
            ->and($promptData->model)->toBe('gpt-4o')
            ->and($promptData->promptText)->toBe('Test prompt text')
            ->and($promptData->temperature)->toBeNull()
            ->and($promptData->maxTokens)->toBeNull()
            ->and($promptData->deepThinkingTemperature)->toBeNull()
            ->and($promptData->description)->toBeNull()
            ->and($promptData->clientOptions)->toBe([]);
    });

    test('it creates PromptData with all optional fields', function (): void {
        $promptData = new PromptData(
            provider: Provider::Anthropic,
            model: 'claude-3-5-sonnet-20241022',
            promptText: 'Test prompt with options',
            temperature: 0.7,
            maxTokens: 200,
            deepThinkingTemperature: 0.3,
            description: 'Test description',
            clientOptions: ['extra' => 'option']
        );

        expect($promptData->provider)->toBe(Provider::Anthropic)
            ->and($promptData->model)->toBe('claude-3-5-sonnet-20241022')
            ->and($promptData->promptText)->toBe('Test prompt with options')
            ->and($promptData->temperature)->toBe(0.7)
            ->and($promptData->maxTokens)->toBe(200)
            ->and($promptData->deepThinkingTemperature)->toBe(0.3)
            ->and($promptData->description)->toBe('Test description')
            ->and($promptData->clientOptions)->toBe(['extra' => 'option']);
    });

    test('it provides readonly properties', function (): void {
        $promptData = new PromptData(
            provider: Provider::Gemini,
            model: 'gemini-1.5-pro',
            promptText: 'Test readonly'
        );

        $reflection = new ReflectionClass($promptData);
        expect($reflection->isReadOnly())->toBeTrue();
    });

    test('it exports to array for serialization', function (): void {
        $promptData = new PromptData(
            provider: Provider::XAI,
            model: 'grok-beta',
            promptText: 'Export test',
            temperature: 0.9,
            maxTokens: 150
        );

        $array = $promptData->toArray();

        expect($array)->toBeArray()
            ->and($array['provider'])->toBe('xai')
            ->and($array['model'])->toBe('grok-beta')
            ->and($array['promptText'])->toBe('Export test')
            ->and($array['temperature'])->toBe(0.9)
            ->and($array['maxTokens'])->toBe(150);
    });

    test('it creates from array (named constructor)', function (): void {
        $data = [
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'promptText' => 'From array test',
            'temperature' => 0.5,
            'maxTokens' => 100,
            'deepThinkingTemperature' => 0.2,
            'description' => 'Array description',
            'clientOptions' => ['key' => 'value'],
        ];

        $promptData = PromptData::fromArray($data);

        expect($promptData->provider)->toBe(Provider::OpenAI)
            ->and($promptData->model)->toBe('gpt-4o')
            ->and($promptData->promptText)->toBe('From array test')
            ->and($promptData->temperature)->toBe(0.5)
            ->and($promptData->maxTokens)->toBe(100)
            ->and($promptData->deepThinkingTemperature)->toBe(0.2)
            ->and($promptData->description)->toBe('Array description')
            ->and($promptData->clientOptions)->toBe(['key' => 'value']);
    });
});
