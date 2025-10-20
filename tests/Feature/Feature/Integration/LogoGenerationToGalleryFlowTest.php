<?php

declare(strict_types=1);

use App\Jobs\GenerateLogosJob;
use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Prism;
use Prism\Prism\Testing\ImageResponseFake;
use Prism\Prism\ValueObjects\GeneratedImage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->suggestion = NameSuggestion::factory()->create([
        'project_id' => $this->project->id,
        'name' => 'TestBusiness',
    ]);
});

describe('Logo Generation to Gallery Flow', function (): void {
    it('generates logos and stores them in database automatically', function (): void {
        // Fake Prism responses for 4 logo generations
        Prism::fake([
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-1.png', revisedPrompt: 'minimalist logo'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-2.png', revisedPrompt: 'modern logo'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-3.png', revisedPrompt: 'playful logo'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-4.png', revisedPrompt: 'corporate logo'),
            ]),
        ]);

        // Fake HTTP responses for image downloads
        Http::fake([
            'example.com/*' => Http::response('fake-image-data', 200),
        ]);

        // Create LogoGeneration as the job would
        $logoGeneration = LogoGeneration::create([
            'user_id' => $this->user->id,
            'session_id' => session()->getId(),
            'business_name' => $this->suggestion->name,
            'business_description' => 'Test description',
            'status' => 'processing',
            'total_logos_requested' => 4,
            'logos_completed' => 0,
        ]);

        // Run the job
        $job = new GenerateLogosJob($logoGeneration);
        $job->handle();

        // Verify LogoGeneration status is updated
        $logoGeneration->refresh();
        expect($logoGeneration->status)->toBe('completed')
            ->and($logoGeneration->logos_completed)->toBe(4);

        // Verify GeneratedLogo records were created
        $generatedLogos = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)->get();
        expect($generatedLogos)->toHaveCount(4);

        // Verify each logo has proper data
        foreach ($generatedLogos as $logo) {
            expect($logo->logo_generation_id)->toBe($logoGeneration->id)
                ->and($logo->style)->toBeIn(['minimalist', 'modern', 'playful', 'corporate'])
                ->and($logo->original_file_path)->not->toBeNull()
                ->and($logo->file_size)->toBeGreaterThan(0);

            // Verify file was actually saved
            Storage::disk('public')->assertExists($logo->original_file_path);
        }
    });

    it('updates NameSuggestion logos field automatically', function (): void {
        // Setup mocks
        Prism::fake([
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-1.png', revisedPrompt: 'test 1'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-2.png', revisedPrompt: 'test 2'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-3.png', revisedPrompt: 'test 3'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-4.png', revisedPrompt: 'test 4'),
            ]),
        ]);

        Http::fake(['example.com/*' => Http::response('fake-image-data', 200)]);

        // Create and run job
        $logoGeneration = LogoGeneration::create([
            'user_id' => $this->user->id,
            'session_id' => session()->getId(),
            'business_name' => $this->suggestion->name,
            'business_description' => 'Test',
            'status' => 'processing',
            'total_logos_requested' => 4,
        ]);

        $job = new GenerateLogosJob($logoGeneration);
        $job->handle();

        // Verify NameSuggestion.logos was updated
        $this->suggestion->refresh();
        expect($this->suggestion->logos)->not->toBeNull()
            ->and($this->suggestion->logos)->toBeArray()
            ->and($this->suggestion->logos)->toHaveCount(4);

        // Verify logo data structure
        foreach ($this->suggestion->logos as $logoData) {
            expect($logoData)->toHaveKeys(['id', 'style', 'variation', 'url', 'file_path', 'file_size', 'prompt_used']);
        }
    });

    it('allows LogoGallery to load generated logos from database', function (): void {
        // Setup mocks
        Prism::fake([
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-1.png', revisedPrompt: 'test 1'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-2.png', revisedPrompt: 'test 2'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-3.png', revisedPrompt: 'test 3'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-4.png', revisedPrompt: 'test 4'),
            ]),
        ]);

        Http::fake(['example.com/*' => Http::response('fake-image-data', 200)]);

        // Generate logos
        $logoGeneration = LogoGeneration::create([
            'user_id' => $this->user->id,
            'session_id' => session()->getId(),
            'business_name' => $this->suggestion->name,
            'status' => 'processing',
            'total_logos_requested' => 4,
        ]);

        $job = new GenerateLogosJob($logoGeneration);
        $job->handle();

        // Simulate what LogoGallery does - load with relationships
        $loadedGeneration = LogoGeneration::with(['generatedLogos'])
            ->find($logoGeneration->id);

        expect($loadedGeneration)->not->toBeNull()
            ->and($loadedGeneration->status)->toBe('completed')
            ->and($loadedGeneration->generatedLogos)->toHaveCount(4);

        // Verify logos can be accessed via relationship
        foreach ($loadedGeneration->generatedLogos as $logo) {
            expect($logo->logo_generation_id)->toBe($logoGeneration->id)
                ->and($logo->original_file_path)->not->toBeNull();
        }
    });

    it('ensures logos are accessible in gallery even without clicking view button', function (): void {
        // This test verifies that logos are stored in the database immediately
        // after generation, not dependent on any user action

        Prism::fake([
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-1.png', revisedPrompt: 'test'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-2.png', revisedPrompt: 'test'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-3.png', revisedPrompt: 'test'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-4.png', revisedPrompt: 'test'),
            ]),
        ]);

        Http::fake(['example.com/*' => Http::response('fake-image-data', 200)]);

        // Create LogoGeneration
        $logoGeneration = LogoGeneration::create([
            'user_id' => $this->user->id,
            'session_id' => session()->getId(),
            'business_name' => $this->suggestion->name,
            'status' => 'processing',
            'total_logos_requested' => 4,
        ]);

        // Run job
        $job = new GenerateLogosJob($logoGeneration);
        $job->handle();

        // Immediately check database without any user interaction
        $logosInDb = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)->count();
        expect($logosInDb)->toBe(4);

        // Verify LogoGeneration can be queried by anyone with the ID
        $foundGeneration = LogoGeneration::with('generatedLogos')
            ->where('business_name', $this->suggestion->name)
            ->where('status', 'completed')
            ->latest()
            ->first();

        expect($foundGeneration)->not->toBeNull()
            ->and($foundGeneration->id)->toBe($logoGeneration->id)
            ->and($foundGeneration->generatedLogos)->toHaveCount(4);
    });

    it('maintains correct relationship between LogoGeneration and GeneratedLogo', function (): void {
        Prism::fake([
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-1.png', revisedPrompt: 'test'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-2.png', revisedPrompt: 'test'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-3.png', revisedPrompt: 'test'),
            ]),
            ImageResponseFake::make()->withImages([
                new GeneratedImage(url: 'https://example.com/logo-4.png', revisedPrompt: 'test'),
            ]),
        ]);

        Http::fake(['example.com/*' => Http::response('fake-image-data', 200)]);

        $logoGeneration = LogoGeneration::create([
            'user_id' => $this->user->id,
            'session_id' => session()->getId(),
            'business_name' => $this->suggestion->name,
            'status' => 'processing',
            'total_logos_requested' => 4,
        ]);

        $job = new GenerateLogosJob($logoGeneration);
        $job->handle();

        // Test forward relationship (LogoGeneration -> GeneratedLogo)
        expect($logoGeneration->generatedLogos())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        expect($logoGeneration->generatedLogos)->toHaveCount(4);

        // Test each GeneratedLogo has correct logo_generation_id
        $logos = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)->get();
        foreach ($logos as $logo) {
            expect($logo->logo_generation_id)->toBe($logoGeneration->id);
        }
    });
});
