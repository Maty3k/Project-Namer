<?php

declare(strict_types=1);

use App\Models\MoodBoard;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);

    // Create test images for mood board use
    $this->images = ProjectImage::factory()->count(5)->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'processing_status' => 'completed',
    ]);
});

test('can list mood boards for a project', function (): void {
    $moodBoards = MoodBoard::factory()->count(3)->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}/mood-boards");

    $response->assertSuccessful()
        ->assertJsonCount(3, 'mood_boards')
        ->assertJsonStructure([
            'mood_boards' => [
                '*' => [
                    'id', 'uuid', 'name', 'description', 'layout_type',
                    'is_public', 'created_at', 'updated_at',
                ],
            ],
        ]);
});

test('can create a new mood board', function (): void {
    $moodBoardData = [
        'name' => 'Test Mood Board',
        'description' => 'A test mood board for our project',
        'layout_type' => 'grid',
        'is_public' => false,
    ];

    $response = $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/mood-boards", $moodBoardData);

    $response->assertCreated()
        ->assertJsonStructure([
            'mood_board' => [
                'id', 'uuid', 'name', 'description', 'layout_type',
                'is_public', 'created_at', 'updated_at',
            ],
        ]);

    $this->assertDatabaseHas('mood_boards', [
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Test Mood Board',
        'layout_type' => 'grid',
    ]);
});

test('validates required fields when creating mood board', function (): void {
    $response = $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/mood-boards", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'layout_type']);
});

test('validates layout type when creating mood board', function (): void {
    $response = $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/mood-boards", [
            'name' => 'Test Board',
            'layout_type' => 'invalid_layout',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['layout_type']);
});

test('can show specific mood board', function (): void {
    $moodBoard = MoodBoard::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}/mood-boards/{$moodBoard->uuid}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'mood_board' => [
                'id', 'uuid', 'name', 'description', 'layout_type',
                'layout_config', 'is_public', 'share_token',
            ],
        ]);
});

test('cannot access mood board from different project', function (): void {
    $otherProject = Project::factory()->create();
    $moodBoard = MoodBoard::factory()->create([
        'project_id' => $otherProject->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}/mood-boards/{$moodBoard->uuid}");

    $response->assertNotFound();
});

test('can update mood board details', function (): void {
    $moodBoard = MoodBoard::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Original Name',
    ]);

    $updateData = [
        'name' => 'Updated Name',
        'description' => 'Updated description',
        'layout_type' => 'collage',
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/projects/{$this->project->id}/mood-boards/{$moodBoard->uuid}", $updateData);

    $response->assertSuccessful()
        ->assertJson([
            'mood_board' => [
                'name' => 'Updated Name',
                'description' => 'Updated description',
                'layout_type' => 'collage',
            ],
        ]);

    expect($moodBoard->fresh()->name)->toBe('Updated Name');
});

test('can update mood board canvas configuration', function (): void {
    $moodBoard = MoodBoard::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
    ]);

    $layoutConfig = [
        'background_color' => '#f3f4f6',
        'grid_size' => 20,
        'snap_to_grid' => true,
        'images' => [
            [
                'image_uuid' => $this->images->first()->uuid,
                'x' => 100,
                'y' => 150,
                'width' => 200,
                'height' => 200,
                'rotation' => 0,
                'z_index' => 1,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/projects/{$this->project->id}/mood-boards/{$moodBoard->uuid}", [
            'layout_config' => $layoutConfig,
        ]);

    $response->assertSuccessful();

    expect($moodBoard->fresh()->layout_config)->toBe($layoutConfig);
});

test('can delete mood board', function (): void {
    $moodBoard = MoodBoard::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/projects/{$this->project->id}/mood-boards/{$moodBoard->uuid}");

    $response->assertSuccessful();

    expect(MoodBoard::find($moodBoard->id))->toBeNull();
});

test('can add images to mood board', function (): void {
    $moodBoard = MoodBoard::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
    ]);

    $imageData = [
        'image_uuids' => $this->images->take(2)->pluck('uuid')->toArray(),
        'positions' => [
            [
                'image_uuid' => $this->images->first()->uuid,
                'x' => 100,
                'y' => 100,
                'width' => 200,
                'height' => 200,
            ],
            [
                'image_uuid' => $this->images->skip(1)->first()->uuid,
                'x' => 300,
                'y' => 100,
                'width' => 200,
                'height' => 200,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/mood-boards/{$moodBoard->uuid}/images", $imageData);

    $response->assertSuccessful();

    expect($moodBoard->projectImages)->toHaveCount(2);
});

test('can remove images from mood board', function (): void {
    $moodBoard = MoodBoard::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
    ]);

    // Add images first
    $moodBoard->projectImages()->attach($this->images->take(3)->pluck('id'));

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/projects/{$this->project->id}/mood-boards/{$moodBoard->uuid}/images", [
            'image_uuids' => $this->images->take(2)->pluck('uuid')->toArray(),
        ]);

    $response->assertSuccessful();

    expect($moodBoard->fresh()->projectImages)->toHaveCount(1);
});

test('can generate public sharing token', function (): void {
    $moodBoard = MoodBoard::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'is_public' => false,
        'share_token' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/mood-boards/{$moodBoard->uuid}/share");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'sharing_url',
            'public_token',
        ]);

    expect($moodBoard->fresh())
        ->is_public->toBe(true)
        ->share_token->not->toBeNull();
});

test('can revoke public sharing', function (): void {
    $moodBoard = MoodBoard::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'is_public' => true,
        'share_token' => 'test-token-123',
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/projects/{$this->project->id}/mood-boards/{$moodBoard->uuid}/share");

    $response->assertSuccessful();

    expect($moodBoard->fresh())
        ->is_public->toBe(false)
        ->share_token->toBeNull();
});

test('can export mood board as PDF', function (): void {
    Storage::fake('local');

    $moodBoard = MoodBoard::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Export Test Board',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/mood-boards/{$moodBoard->uuid}/export", [
            'format' => 'pdf',
        ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'download_url',
            'file_path',
            'expires_at',
        ]);
});

test('requires authentication for all mood board operations', function (): void {
    $moodBoard = MoodBoard::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
    ]);

    // Test without authentication
    $this->getJson("/api/projects/{$this->project->id}/mood-boards")
        ->assertUnauthorized();

    $this->postJson("/api/projects/{$this->project->id}/mood-boards", [
        'name' => 'Test',
        'layout_type' => 'grid',
    ])
        ->assertUnauthorized();

    $this->putJson("/api/projects/{$this->project->id}/mood-boards/{$moodBoard->uuid}", [
        'name' => 'Updated',
    ])
        ->assertUnauthorized();

    $this->deleteJson("/api/projects/{$this->project->id}/mood-boards/{$moodBoard->uuid}")
        ->assertUnauthorized();
});

test('prevents unauthorized access to other users mood boards', function (): void {
    $otherUser = User::factory()->create();
    $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);
    $otherMoodBoard = MoodBoard::factory()->create([
        'project_id' => $otherProject->id,
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$otherProject->id}/mood-boards/{$otherMoodBoard->uuid}");

    $response->assertForbidden();
});

test('can view publicly shared mood board', function (): void {
    $moodBoard = MoodBoard::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'is_public' => true,
        'share_token' => 'test-share-token',
    ]);

    $response = $this->get("/share/mood-board/{$moodBoard->share_token}");

    $response->assertSuccessful()
        ->assertViewIs('shares.mood-board')
        ->assertViewHas('moodBoard', $moodBoard);
});
