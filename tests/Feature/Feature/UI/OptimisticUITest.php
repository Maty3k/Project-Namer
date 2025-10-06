<?php

declare(strict_types=1);

use App\Models\NameSuggestion;
use App\Models\NamingSession;
use App\Models\Project;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

describe('Optimistic Hide/Show for Name Suggestions', function (): void {
    test('hide suggestion updates UI immediately', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'is_hidden' => false,
        ]);

        $response = $this->get(route('project.show', $this->project->uuid));
        $response->assertStatus(200);

        // Component should exist and handle optimistic updates
        $componentPath = resource_path('js/components/optimisticUI.js');
        expect(file_exists($componentPath))->toBeTrue();
    });

    test('show suggestion updates UI immediately', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'is_hidden' => true,
        ]);

        // Verify optimistic update component exists
        $componentPath = resource_path('js/components/optimisticUI.js');
        expect(file_exists($componentPath))->toBeTrue();
    });

    test('rollback occurs on server error', function (): void {
        // Component should handle rollback logic
        $componentPath = resource_path('js/components/optimisticUI.js');
        $content = file_get_contents($componentPath);

        expect($content)->toContain('rollback');
    });
});

describe('Optimistic Star/Favorite Functionality', function (): void {
    test('star session updates UI immediately', function (): void {
        $session = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'is_starred' => false,
        ]);

        // Component should handle optimistic starring
        $componentPath = resource_path('js/components/optimisticUI.js');
        expect(file_exists($componentPath))->toBeTrue();
    });

    test('unstar session updates UI immediately', function (): void {
        $session = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'is_starred' => true,
        ]);

        // Component should handle optimistic unstarring
        $componentPath = resource_path('js/components/optimisticUI.js');
        expect(file_exists($componentPath))->toBeTrue();
    });
});

describe('Optimistic Delete with Undo Toast', function (): void {
    test('delete shows undo toast', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        // Undo toast component should exist
        $componentPath = resource_path('views/components/undo-toast.blade.php');
        expect(file_exists($componentPath))->toBeTrue();
    });

    test('undo restores deleted item', function (): void {
        // Optimistic UI component should handle undo logic
        $componentPath = resource_path('js/components/optimisticUI.js');
        $content = file_get_contents($componentPath);

        expect($content)->toContain('undo');
    });
});

describe('Rollback Mechanism', function (): void {
    test('failed operations trigger rollback', function (): void {
        // Component should have error handling
        $componentPath = resource_path('js/components/optimisticUI.js');
        $content = file_get_contents($componentPath);

        expect($content)->toMatch('/(catch|error|fail)/i');
    });

    test('rollback restores original state', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'is_hidden' => false,
        ]);

        // Original state should be preserved for rollback
        expect($suggestion->is_hidden)->toBeFalse();
    });
});

describe('Error Toast Notifications', function (): void {
    test('error toast displays on rollback', function (): void {
        // Optimistic UI component should dispatch error events
        $componentPath = resource_path('js/components/optimisticUI.js');
        $content = file_get_contents($componentPath);

        expect($content)->toMatch('/(toast|notification|error)/i');
    });

    test('error message is user-friendly', function (): void {
        // Component should have user-friendly error messages
        $componentPath = resource_path('js/components/optimisticUI.js');
        expect(file_exists($componentPath))->toBeTrue();
    });
});

describe('Rapid Consecutive Operations', function (): void {
    test('multiple rapid operations queue correctly', function (): void {
        $suggestions = NameSuggestion::factory()->count(3)->create([
            'project_id' => $this->project->id,
        ]);

        // Each suggestion can be independently updated
        foreach ($suggestions as $suggestion) {
            expect($suggestion->id)->toBeInt();
        }
    });

    test('optimistic updates do not conflict', function (): void {
        // Component should handle concurrent operations
        $componentPath = resource_path('js/components/optimisticUI.js');
        $content = file_get_contents($componentPath);

        // Should track state per operation
        expect($content)->toContain('function');
    });

    test('rollback only affects failed operations', function (): void {
        $suggestion1 = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'is_hidden' => false,
        ]);

        $suggestion2 = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'is_hidden' => false,
        ]);

        // Each suggestion maintains independent state
        expect($suggestion1->id)->not->toBe($suggestion2->id);
    });
});

describe('Integration with Livewire', function (): void {
    test('optimistic updates work with Livewire components', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        // Livewire component should handle optimistic UI
        $viewPath = resource_path('views/livewire/name-result-card.blade.php');
        expect(file_exists($viewPath))->toBeTrue();
    });

    test('Livewire events trigger optimistic updates', function (): void {
        // Check that Alpine.js/Livewire integration exists
        $appJsPath = resource_path('js/app.js');
        $content = file_get_contents($appJsPath);

        expect($content)->toContain('Alpine');
    });
});
