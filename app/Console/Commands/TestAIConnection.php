<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Prism\Prism\Prism;

class TestAIConnection extends Command
{
    protected $signature = 'ai:test {model=gpt-4o}';

    protected $description = 'Test AI model connection';

    public function handle(): int
    {
        $model = $this->argument('model');

        $this->info("Testing connection to {$model}...");

        try {
            // Determine provider and model based on input
            [$provider, $actualModel] = match ($model) {
                'gpt-4o' => ['openai', 'gpt-4o'],
                'claude-3-5-sonnet-20241022' => ['anthropic', 'claude-3-5-sonnet-20241022'],
                'gemini-1.5-pro' => ['gemini', 'gemini-1.5-pro'],
                'grok-beta' => ['xai', 'grok-beta'],
                default => ['openai', 'gpt-4o'],
            };

            $this->info("Using provider: {$provider}, model: {$actualModel}");

            $response = Prism::text()
                ->using($provider, $actualModel)
                ->withPrompt('Say "Hello, I am working!" in exactly those words.')
                ->withClientOptions([
                    'max_tokens' => 50,
                    'temperature' => 0.7,
                ])
                ->asText();

            $this->info('✅ Success! Response: '.$response->text);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());

            return self::FAILURE;
        }
    }
}
