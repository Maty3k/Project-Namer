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
                ->toContain('world-class brand strategist')
                ->toContain('ONLY output the numbered list of names')
                ->toContain('CREATIVE MODE MASTERY')
                ->toContain('spark curiosity, evoke emotion')
                ->toContain('Airbnb: Air (universal) + BnB (belonging)')
                ->toContain('ADVANCED NAMING PRINCIPLES');
        });

        it('generates correct system prompt for professional mode', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('claude-3.5-sonnet', 10, 'professional', false);

            expect($systemPrompt)
                ->toContain('PROFESSIONAL MODE MASTERY')
                ->toContain('command respect, inspire confidence')
                ->toContain('McKinsey: Founder surname suggesting heritage')
                ->toContain('boardroom');
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
                ->toContain('resonate with technical audiences')
                ->toContain('GitHub: Git (version control) + Hub (central place)')
                ->toContain('developer culture');
        });

        it('includes deep thinking instructions when enabled', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('gpt-4', 10, 'creative', true);

            expect($systemPrompt)
                ->toContain('DEEP THINKING MODE ACTIVATED')
                ->toContain('advanced analysis protocols')
                ->toContain('semantic field analysis')
                ->toContain('neuro-linguistic');
        });

        it('respects count parameter in system prompt', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('gpt-4', 5, 'creative', false);

            expect($systemPrompt)
                ->toContain('ONLY output the numbered list of names')
                ->toContain('Format: "1. NameHere" through "5. NameHere"');
        });

        it('includes core rules in all system prompts', function (): void {
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
                ->toContain('STRATEGIC CONTEXT')
                ->toContain('BRAND PERSONALITY TARGET');
        });

        it('identifies technology business type correctly', function (): void {
            $businessIdeas = [
                'A mobile app for fitness tracking',
                'Software for accounting professionals',
                'A platform for online learning',
            ];

            foreach ($businessIdeas as $idea) {
                $userPrompt = $this->promptBuilder->buildUserPrompt($idea, 'gpt-4', 'creative', false);

                expect($userPrompt)
                    ->toContain('Technology/Software')
                    ->toContain('Tech Users & Decision Makers')
                    ->toContain('GitHub: Git + Hub = collaboration made simple');
            }
        });

        it('identifies food business type correctly', function (): void {
            $businessIdeas = [
                'A restaurant serving healthy meals',
                'Food delivery service for organic produce',
                'Bakery specializing in gluten-free products',
            ];

            foreach ($businessIdeas as $idea) {
                $userPrompt = $this->promptBuilder->buildUserPrompt($idea, 'gpt-4', 'creative', false);

                expect($userPrompt)
                    ->toContain('Food & Beverage')
                    ->toContain('Food Enthusiasts & Diners')
                    ->toContain('Sweetgreen: Sweet + Green = healthy indulgence');
            }
        });

        it('identifies retail business type correctly', function (): void {
            $businessIdeas = [
                'Online shop for handmade crafts',
                'Retail store for vintage clothing',
                'Store selling eco-friendly products',
            ];

            foreach ($businessIdeas as $idea) {
                $userPrompt = $this->promptBuilder->buildUserPrompt($idea, 'gpt-4', 'creative', false);

                expect($userPrompt)
                    ->toContain('Retail/E-commerce')
                    ->toContain('Consumers & Shoppers')
                    ->toContain('Amazon: Vast selection like the river');
            }
        });

        it('identifies service business type correctly', function (): void {
            $businessIdeas = [
                'Consulting agency for small businesses',
                'Marketing service for startups',
                'Legal services for tech companies',
            ];

            foreach ($businessIdeas as $idea) {
                $userPrompt = $this->promptBuilder->buildUserPrompt($idea, 'gpt-4', 'creative', false);

                expect($userPrompt)
                    ->toContain('Professional Services')
                    ->toContain('Business Decision Makers')
                    ->toContain('McKinsey: Strong surname suggesting heritage');
            }
        });

        it('defaults to general business type for unrecognized concepts', function (): void {
            $userPrompt = $this->promptBuilder->buildUserPrompt(
                'A unique concept that doesn\'t fit standard categories',
                'gpt-4',
                'creative',
                false
            );

            expect($userPrompt)
                ->toContain('General Business')
                ->toContain('Diverse Customer Base');
        });

        it('includes contextual guidance questions', function (): void {
            $userPrompt = $this->promptBuilder->buildUserPrompt(
                'Test business idea',
                'gpt-4',
                'creative',
                false
            );

            expect($userPrompt)
                ->toContain('NAMING MISSION')
                ->toContain('core value proposition')
                ->toContain('emotional level')
                ->toContain('brand storytelling');
        });
    });

    describe('Prompt Optimization', function (): void {
        it('optimizes prompt for specific model and mode combination', function (): void {
            $optimizedPrompt = $this->promptBuilder->optimizePrompt(
                'gpt-4',
                'A project management tool',
                'creative',
                false
            );

            expect($optimizedPrompt)
                ->toContain('world-class brand strategist')
                ->toContain('ONLY output the numbered list of names')
                ->toContain('spark curiosity, evoke emotion')
                ->toContain('BUSINESS CONCEPT: A project management tool');
        });

        it('includes deep thinking instructions in optimized prompts', function (): void {
            $optimizedPrompt = $this->promptBuilder->optimizePrompt(
                'claude-3.5-sonnet',
                'A SaaS platform',
                'professional',
                true
            );

            expect($optimizedPrompt)
                ->toContain('DEEP THINKING MODE ACTIVATED')
                ->toContain('advanced analysis protocols')
                ->toContain('semantic field analysis');
        });

        it('generates mode-specific instructions in optimized prompts', function (): void {
            $modes = [
                'creative' => 'Airbnb: Air (universal) + BnB (belonging)',
                'professional' => 'McKinsey: Founder surname suggesting heritage',
                'brandable' => 'Google: Playful take on "googol"',
                'tech-focused' => 'GitHub: Git (version control) + Hub (central place)',
            ];

            foreach ($modes as $mode => $expectedExamples) {
                $optimizedPrompt = $this->promptBuilder->optimizePrompt(
                    'gpt-4',
                    'Test business',
                    $mode,
                    false
                );

                expect($optimizedPrompt)->toContain($expectedExamples);
            }
        });
    });

    describe('Business Analysis', function (): void {
        it('analyzes business type based on keywords', function (): void {
            $testCases = [
                ['app for fitness', 'Technology/Software', 'Tech Users & Decision Makers'],
                ['restaurant in downtown', 'Food & Beverage', 'Food Enthusiasts & Diners'],
                ['online shop for books', 'Retail/E-commerce', 'Consumers & Shoppers'],
                ['consulting for startups', 'Professional Services', 'Business Decision Makers'],
                ['unique art project', 'General Business', 'Diverse Customer Base'],
            ];

            foreach ($testCases as [$businessIdea, $expectedType, $expectedAudience]) {
                $analysis = $this->promptBuilder->analyzeBusinessType($businessIdea);

                expect($analysis['type'])->toBe($expectedType);
                expect($analysis['audience'])->toBe($expectedAudience);
            }
        });

        it('provides appropriate examples for each business type', function (): void {
            $analysis = $this->promptBuilder->analyzeBusinessType('software platform for teams');

            expect($analysis['examples'])
                ->toContain('GitHub: Git + Hub = collaboration made simple')
                ->toContain('STRATEGIC NAMING INSPIRATION');
        });
    });
});
