<?php

declare(strict_types=1);

use App\Services\PromptBuilder;

beforeEach(function (): void {
    $this->promptBuilder = app(PromptBuilder::class);
});

describe('PromptBuilder Service', function (): void {
    describe('System Prompt Generation', function (): void {
        it('generates correct system prompt for creative mode', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('gpt-4', 10, 'creative', false);

            expect($systemPrompt)
                ->toContain('revolutionary brand alchemist')
                ->toContain('ONLY output the numbered list of names')
                ->toContain('CREATIVE MODE MASTERY')
                ->toContain('spark curiosity, evoke emotion')
                ->toContain('Airbnb: Air (universal) + BnB (belonging)');
        });

        it('generates correct system prompt for professional mode', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('claude-3.5-sonnet', 10, 'professional', false);

            expect($systemPrompt)
                ->toContain('PROFESSIONAL MODE MASTERY')
                ->toContain('command respect, inspire confidence')
                ->toContain('McKinsey: Founder surname suggesting heritage')
                ->toContain('BOARDROOM PSYCHOLOGY');
        });

        it('generates correct system prompt for brandable mode', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('gemini-1.5-pro', 10, 'brandable', false);

            expect($systemPrompt)
                ->toContain('BRANDABLE MODE MASTERY')
                ->toContain('instantly memorable, socially shareable')
                ->toContain('Google: Playful take on "googol"')
                ->toContain('viral growth');
        });

        it('generates correct system prompt for tech-focused mode', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('grok-beta', 10, 'tech-focused', false);

            expect($systemPrompt)
                ->toContain('TECH-FOCUSED MODE MASTERY')
                ->toContain('signal innovation, technical excellence')
                ->toContain('GitHub: "Git" (version control) + "Hub"')
                ->toContain('developer credibility');
        });

        it('includes deep thinking instructions when enabled', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('gpt-4', 10, 'creative', true);

            expect($systemPrompt)
                ->toContain('DEEP THINKING ACTIVATION')
                ->toContain('sophisticated naming algorithms')
                ->toContain('emotional transformation it enables');
        });

        it('respects count parameter in system prompt', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('gpt-4', 5, 'creative', false);

            expect($systemPrompt)
                ->toContain('"1. NameHere" through "5. NameHere"');
        });

        it('includes core quality standards in all system prompts', function (): void {
            $modes = ['creative', 'professional', 'brandable', 'tech-focused'];

            foreach ($modes as $mode) {
                $systemPrompt = $this->promptBuilder->buildSystemPrompt('gpt-4', 10, $mode, false);

                expect($systemPrompt)
                    ->toContain('ADVANCED NAMING PRINCIPLES')
                    ->toContain('UNIQUE and DISTINCTIVE')
                    ->toContain('FORBIDDEN ELEMENTS')
                    ->toContain('emotional resonance and memorability');
            }
        });

        it('uses default mode for unrecognized modes', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('gpt-4', 10, 'unknown-mode', false);

            expect($systemPrompt)
                ->toContain('CREATIVE MODE MASTERY')
                ->toContain('spark curiosity, evoke emotion');
        });
    });

    describe('User Prompt Generation', function (): void {
        it('builds basic user prompt with business idea', function (): void {
            $userPrompt = $this->promptBuilder->buildUserPrompt(
                'A project management tool for creative teams',
                'gpt-4',
                'creative',
                false
            );

            expect($userPrompt)
                ->toContain('BUSINESS CONCEPT: A project management tool for creative teams')
                ->toContain('Generate 10 exceptional business names')
                ->toContain('creative naming mastery');
        });

        it('includes different modes in user prompt', function (): void {
            $modes = ['creative', 'professional', 'brandable', 'tech-focused'];

            foreach ($modes as $mode) {
                $userPrompt = $this->promptBuilder->buildUserPrompt(
                    'Test business concept',
                    'gpt-4',
                    $mode,
                    false
                );

                expect($userPrompt)
                    ->toContain('Generate 10 exceptional business names')
                    ->toContain("{$mode} naming mastery");
            }
        });

        it('generates consistent count in user prompt', function (): void {
            $userPrompt = $this->promptBuilder->buildUserPrompt(
                'Test business',
                'gpt-4',
                'creative',
                false
            );

            expect($userPrompt)->toContain('Generate 10 exceptional');
        });
    });
});
