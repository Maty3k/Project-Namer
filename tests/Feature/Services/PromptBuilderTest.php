<?php

declare(strict_types=1);

use App\Services\PromptBuilder;
use App\Services\PromptLoaderService;

beforeEach(function (): void {
    $this->builder = new PromptBuilder(new PromptLoaderService);
});

describe('System Prompt Generation', function (): void {
    test('generates system prompt for creative mode', function (): void {
        $prompt = $this->builder->buildSystemPrompt(
            model: 'gpt-4o',
            count: 10,
            mode: 'creative'
        );

        expect($prompt)
            ->toContain('expert business naming consultant')
            ->toContain('exactly 10 unique business names')
            ->toContain('CRITICAL RULES')
            ->toContain('CREATIVE MODE')
            ->toContain('Spotify')
            ->toContain('Airbnb');
    });

    test('generates system prompt for professional mode', function (): void {
        $prompt = $this->builder->buildSystemPrompt(
            model: 'gpt-4o',
            count: 10,
            mode: 'professional',
        );

        expect($prompt)
            ->toContain('PROFESSIONAL MODE')
            ->toContain('Goldman Sachs')
            ->toContain('McKinsey')
            ->toContain('trustworthy');
    });

    test('generates system prompt for brandable mode', function (): void {
        $prompt = $this->builder->buildSystemPrompt(
            model: 'gpt-4o',
            count: 10,
            mode: 'brandable',
        );

        expect($prompt)
            ->toContain('BRANDABLE MODE')
            ->toContain('Google')
            ->toContain('Amazon')
            ->toContain('catchy');
    });

    test('generates system prompt for tech-focused mode', function (): void {
        $prompt = $this->builder->buildSystemPrompt(
            model: 'gpt-4o',
            count: 10,
            mode: 'tech-focused',
        );

        expect($prompt)
            ->toContain('TECH-FOCUSED MODE')
            ->toContain('GitHub')
            ->toContain('Stripe')
            ->toContain('Slack');
    });

    test('does not include deep thinking instructions', function (): void {
        $prompt = $this->builder->buildSystemPrompt(
            model: 'gpt-4o',
            count: 10,
            mode: 'creative',
        );

        expect($prompt)->not->toContain('DEEP THINKING MODE');
    });

    test('includes all critical rules', function (): void {
        $prompt = $this->builder->buildSystemPrompt(
            model: 'gpt-4o',
            count: 10,
            mode: 'creative',
        );

        expect($prompt)
            ->toContain('NO generic tech suffixes')
            ->toContain('NO unnecessary prefixes')
            ->toContain('memorable, pronounceable, and brandable')
            ->toContain('Avoid overused words');
    });

    test('adjusts count in system prompt', function (): void {
        $prompt = $this->builder->buildSystemPrompt(
            model: 'gpt-4o',
            count: 20,
            mode: 'creative',
        );

        expect($prompt)
            ->toContain('exactly 20 unique business names')
            ->toContain('numbered 1-20');
    });

    test('defaults to creative mode for unknown modes', function (): void {
        $prompt = $this->builder->buildSystemPrompt(
            model: 'gpt-4o',
            count: 10,
            mode: 'unknown-mode',
        );

        expect($prompt)->toContain('CREATIVE MODE');
    });
});

describe('User Prompt Generation', function (): void {
    test('builds basic user prompt with business idea', function (): void {
        $prompt = $this->builder->buildUserPrompt(
            businessIdea: 'A meal planning app',
            model: 'gpt-4o',
            mode: 'creative',
        );

        expect($prompt)
            ->toContain('Business concept: A meal planning app')
            ->toContain('Business Analysis')
            ->toContain('Type:')
            ->toContain('Target Audience:');
    });

    test('detects technology business type', function (): void {
        $prompt = $this->builder->buildUserPrompt(
            businessIdea: 'A software platform for developers',
            model: 'gpt-4o',
            mode: 'creative',
        );

        expect($prompt)
            ->toContain('Technology/Software')
            ->toContain('Tech Users')
            ->toContain('GitHub, Stripe, Slack, Figma');
    });

    test('detects food business type', function (): void {
        $prompt = $this->builder->buildUserPrompt(
            businessIdea: 'A bakery specializing in sourdough bread',
            model: 'gpt-4o',
            mode: 'creative',
        );

        expect($prompt)
            ->toContain('Food & Beverage')
            ->toContain('Food Lovers')
            ->toContain('Sweetgreen, Chipotle, Panera, Starbucks');
    });

    test('detects retail business type', function (): void {
        $prompt = $this->builder->buildUserPrompt(
            businessIdea: 'An online store for handmade crafts',
            model: 'gpt-4o',
            mode: 'creative',
        );

        expect($prompt)
            ->toContain('Retail/E-commerce')
            ->toContain('Shoppers')
            ->toContain('Amazon, Etsy, Warby Parker, Casper');
    });

    test('detects consulting business type', function (): void {
        $prompt = $this->builder->buildUserPrompt(
            businessIdea: 'A consulting agency for small businesses',
            model: 'gpt-4o',
            mode: 'creative',
        );

        expect($prompt)
            ->toContain('Professional Services')
            ->toContain('Business Clients')
            ->toContain('McKinsey, Deloitte, Accenture, IDEO');
    });

    test('defaults to general business when no keywords match', function (): void {
        $prompt = $this->builder->buildUserPrompt(
            businessIdea: 'Something completely unique',
            model: 'gpt-4o',
            mode: 'creative',
        );

        expect($prompt)
            ->toContain('General Business')
            ->toContain('General Public');
    });

    test('includes contextual guidance questions', function (): void {
        $prompt = $this->builder->buildUserPrompt(
            businessIdea: 'A meal planning app',
            model: 'gpt-4o',
            mode: 'creative',
        );

        expect($prompt)
            ->toContain('What problem does this business solve?')
            ->toContain('What makes it different from competitors?')
            ->toContain('What emotion should the brand evoke?')
            ->toContain('Who is the primary customer?');
    });
});

describe('Complete Prompt Building', function (): void {
    test('builds complete prompt combining system and user prompts', function (): void {
        $result = $this->builder->build(
            businessIdea: 'A fitness tracking app',
            model: 'gpt-4o',
            count: 10,
            mode: 'tech-focused',
        );

        expect($result)->toHaveKeys(['system', 'user']);
        expect($result['system'])
            ->toContain('TECH-FOCUSED MODE')
            ->toContain('exactly 10 unique business names');
        expect($result['user'])
            ->toContain('Business concept: A fitness tracking app')
            ->toContain('Technology/Software');
    });

    test('handles different name counts', function (): void {
        $result = $this->builder->build(
            businessIdea: 'A photography portfolio site',
            model: 'gpt-4o',
            count: 25,
            mode: 'creative',
        );

        expect($result['system'])->toContain('exactly 25 unique business names');
    });
});

describe('Legacy Compatibility', function (): void {
    test('optimizePrompt method returns complete combined prompt', function (): void {
        $prompt = $this->builder->optimizePrompt(
            modelId: 'gpt-4',
            basePrompt: 'A project management software platform',
            mode: 'brandable'
        );

        expect($prompt)
            ->toContain('expert business naming consultant')
            ->toContain('BRANDABLE MODE')
            ->toContain('Business concept: A project management software platform')
            ->toContain('Technology/Software');
    });
});
