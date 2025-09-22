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
                ->toContain('expert business naming consultant')
                ->toContain('Generate exactly 10 unique business names')
                ->toContain('CREATIVE MODE')
                ->toContain('creative and artistic names')
                ->toContain('Spotify", "Airbnb", or "Etsy"')
                ->toContain('CRITICAL RULES');
        });

        it('generates correct system prompt for professional mode', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('claude-3.5-sonnet', 10, 'professional', false);

            expect($systemPrompt)
                ->toContain('PROFESSIONAL MODE')
                ->toContain('sophisticated, trustworthy names')
                ->toContain('Goldman Sachs", "McKinsey", or "Deloitte"')
                ->toContain('B2B environments');
        });

        it('generates correct system prompt for brandable mode', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('gemini-1.5-pro', 10, 'brandable', false);

            expect($systemPrompt)
                ->toContain('BRANDABLE MODE')
                ->toContain('catchy, market-ready names')
                ->toContain('Google", "Amazon", or "Nike"')
                ->toContain('advertising, social media');
        });

        it('generates correct system prompt for tech-focused mode', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('grok-beta', 10, 'tech-focused', false);

            expect($systemPrompt)
                ->toContain('TECH-FOCUSED MODE')
                ->toContain('appeal to technical audiences')
                ->toContain('GitHub", "Stripe", or "Slack"')
                ->toContain('developer-friendly');
        });

        it('includes deep thinking instructions when enabled', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('gpt-4', 10, 'creative', true);

            expect($systemPrompt)
                ->toContain('DEEP THINKING MODE')
                ->toContain('Take extra time to analyze')
                ->toContain('market positioning')
                ->toContain('commercial viability');
        });

        it('respects count parameter in system prompt', function (): void {
            $systemPrompt = $this->promptBuilder->buildSystemPrompt('gpt-4', 5, 'creative', false);

            expect($systemPrompt)
                ->toContain('Generate exactly 5 unique business names')
                ->toContain('numbered 1-5');
        });

        it('includes core rules in all system prompts', function (): void {
            $modes = ['creative', 'professional', 'brandable', 'tech-focused'];

            foreach ($modes as $mode) {
                $systemPrompt = $this->promptBuilder->buildSystemPrompt('gpt-4', 10, $mode, false);

                expect($systemPrompt)
                    ->toContain('CRITICAL RULES')
                    ->toContain('Names must be directly relevant')
                    ->toContain('NO generic tech suffixes')
                    ->toContain('memorable, pronounceable, and brandable');
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
                ->toContain('Business concept: A project management tool for creative teams')
                ->toContain('Business Analysis')
                ->toContain('Target Audience');
        });

        it('identifies technology business type correctly', function (): void {
            $businessIdeas = [
                'A mobile app for fitness tracking',
                'Software for accounting professionals',
                'A platform for online learning'
            ];

            foreach ($businessIdeas as $idea) {
                $userPrompt = $this->promptBuilder->buildUserPrompt($idea, 'gpt-4', 'creative', false);

                expect($userPrompt)
                    ->toContain('Type: Technology/Software')
                    ->toContain('Target Audience: Tech Users')
                    ->toContain('Good examples for tech businesses: GitHub, Stripe, Slack, Figma');
            }
        });

        it('identifies food business type correctly', function (): void {
            $businessIdeas = [
                'A restaurant serving healthy meals',
                'Food delivery service for organic produce',
                'Bakery specializing in gluten-free products'
            ];

            foreach ($businessIdeas as $idea) {
                $userPrompt = $this->promptBuilder->buildUserPrompt($idea, 'gpt-4', 'creative', false);

                expect($userPrompt)
                    ->toContain('Type: Food & Beverage')
                    ->toContain('Target Audience: Food Lovers')
                    ->toContain('Good examples for food businesses: Sweetgreen, Chipotle, Panera, Starbucks');
            }
        });

        it('identifies retail business type correctly', function (): void {
            $businessIdeas = [
                'Online shop for handmade crafts',
                'Retail store for vintage clothing',
                'Store selling eco-friendly products'
            ];

            foreach ($businessIdeas as $idea) {
                $userPrompt = $this->promptBuilder->buildUserPrompt($idea, 'gpt-4', 'creative', false);

                expect($userPrompt)
                    ->toContain('Type: Retail/E-commerce')
                    ->toContain('Target Audience: Shoppers')
                    ->toContain('Good examples for retail: Amazon, Etsy, Warby Parker, Casper');
            }
        });

        it('identifies service business type correctly', function (): void {
            $businessIdeas = [
                'Consulting agency for small businesses',
                'Marketing service for startups',
                'Legal services for tech companies'
            ];

            foreach ($businessIdeas as $idea) {
                $userPrompt = $this->promptBuilder->buildUserPrompt($idea, 'gpt-4', 'creative', false);

                expect($userPrompt)
                    ->toContain('Type: Professional Services')
                    ->toContain('Target Audience: Business Clients')
                    ->toContain('Good examples for services: McKinsey, Deloitte, Accenture, IDEO');
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
                ->toContain('Type: General Business')
                ->toContain('Target Audience: General Public');
        });

        it('includes contextual guidance questions', function (): void {
            $userPrompt = $this->promptBuilder->buildUserPrompt(
                'Test business idea',
                'gpt-4',
                'creative',
                false
            );

            expect($userPrompt)
                ->toContain('What problem does this business solve?')
                ->toContain('What makes it different from competitors?')
                ->toContain('What emotion should the brand evoke?')
                ->toContain('Who is the primary customer?');
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
                ->toContain('expert business naming consultant')
                ->toContain('Generate exactly 10 unique business names')
                ->toContain('creative and artistic names')
                ->toContain('Business concept: A project management tool');
        });

        it('includes deep thinking instructions in optimized prompts', function (): void {
            $optimizedPrompt = $this->promptBuilder->optimizePrompt(
                'claude-3.5-sonnet',
                'A SaaS platform',
                'professional',
                true
            );

            expect($optimizedPrompt)
                ->toContain('Take extra time to analyze the business concept deeply')
                ->toContain('market positioning')
                ->toContain('competitive landscape');
        });

        it('generates mode-specific instructions in optimized prompts', function (): void {
            $modes = [
                'creative' => 'Spotify, Airbnb, Etsy',
                'professional' => 'Goldman Sachs, McKinsey, Deloitte',
                'brandable' => 'Google, Amazon, Nike',
                'tech-focused' => 'GitHub, Stripe, Slack'
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
                ['app for fitness', 'Technology/Software', 'Tech Users'],
                ['restaurant in downtown', 'Food & Beverage', 'Food Lovers'],
                ['online shop for books', 'Retail/E-commerce', 'Shoppers'],
                ['consulting for startups', 'Professional Services', 'Business Clients'],
                ['unique art project', 'General Business', 'General Public']
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
                ->toContain('GitHub, Stripe, Slack, Figma')
                ->toContain('avoid generic tech terms');
        });
    });
});
