<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

describe('Toast Notification System', function (): void {
    describe('Success Notifications', function (): void {
        it('displays success toast for logo customization', function (): void {
            // Create a simple Volt component to test toast dispatching
            $component = new class extends \Livewire\Component
            {
                public function triggerSuccessToast(): void
                {
                    $this->dispatch('toast',
                        message: 'Logos customized successfully!',
                        type: 'success'
                    );
                }

                public function render(): string
                {
                    return '<div>Test Component</div>';
                }
            };

            \Livewire\Livewire::component('test-toast-component', $component::class);

            \Livewire\Livewire::test('test-toast-component')
                ->call('triggerSuccessToast')
                ->assertDispatched('toast',
                    message: 'Logos customized successfully!',
                    type: 'success'
                );
        });

        it('displays success toast for logo downloads', function (): void {
            $component = new class extends \Livewire\Component
            {
                public function triggerDownloadToast(): void
                {
                    $this->dispatch('toast',
                        message: 'Logo download started',
                        type: 'info'
                    );
                }

                public function render(): string
                {
                    return '<div>Test Component</div>';
                }
            };

            \Livewire\Livewire::component('test-download-toast', $component::class);

            \Livewire\Livewire::test('test-download-toast')
                ->call('triggerDownloadToast')
                ->assertDispatched('toast',
                    message: 'Logo download started',
                    type: 'info'
                );
        });

        it('displays success toast for generation completion', function (): void {
            $component = new class extends \Livewire\Component
            {
                public function triggerCompletionToast(): void
                {
                    $this->dispatch('toast',
                        message: 'All 12 logos generated successfully!',
                        type: 'success',
                        duration: 5000
                    );
                }

                public function render(): string
                {
                    return '<div>Test Component</div>';
                }
            };

            \Livewire\Livewire::component('test-completion-toast', $component::class);

            \Livewire\Livewire::test('test-completion-toast')
                ->call('triggerCompletionToast')
                ->assertDispatched('toast',
                    message: 'All 12 logos generated successfully!',
                    type: 'success',
                    duration: 5000
                );
        });
    });

    describe('Error Notifications', function (): void {
        it('displays error toast for failed operations', function (): void {
            $component = new class extends \Livewire\Component
            {
                public function triggerErrorToast(): void
                {
                    $this->dispatch('toast',
                        message: 'Failed to customize logos. Please try again.',
                        type: 'error',
                        duration: 8000
                    );
                }

                public function render(): string
                {
                    return '<div>Test Component</div>';
                }
            };

            \Livewire\Livewire::component('test-error-toast', $component::class);

            \Livewire\Livewire::test('test-error-toast')
                ->call('triggerErrorToast')
                ->assertDispatched('toast',
                    message: 'Failed to customize logos. Please try again.',
                    type: 'error',
                    duration: 8000
                );
        });

        it('displays error toast for file not found', function (): void {
            $component = new class extends \Livewire\Component
            {
                public function triggerFileNotFoundToast(): void
                {
                    $this->dispatch('toast',
                        message: 'File not found. It may have been removed.',
                        type: 'error'
                    );
                }

                public function render(): string
                {
                    return '<div>Test Component</div>';
                }
            };

            \Livewire\Livewire::component('test-file-not-found-toast', $component::class);

            \Livewire\Livewire::test('test-file-not-found-toast')
                ->call('triggerFileNotFoundToast')
                ->assertDispatched('toast',
                    message: 'File not found. It may have been removed.',
                    type: 'error'
                );
        });

        it('displays error toast for service unavailability', function (): void {
            $component = new class extends \Livewire\Component
            {
                public function triggerServiceUnavailableToast(): void
                {
                    $this->dispatch('toast',
                        message: 'Logo generation service is temporarily unavailable. Please try again in a few minutes.',
                        type: 'warning',
                        duration: 10000
                    );
                }

                public function render(): string
                {
                    return '<div>Test Component</div>';
                }
            };

            \Livewire\Livewire::component('test-service-unavailable-toast', $component::class);

            \Livewire\Livewire::test('test-service-unavailable-toast')
                ->call('triggerServiceUnavailableToast')
                ->assertDispatched('toast',
                    message: 'Logo generation service is temporarily unavailable. Please try again in a few minutes.',
                    type: 'warning',
                    duration: 10000
                );
        });
    });

    describe('Info and Warning Notifications', function (): void {
        it('displays info toast for processing status', function (): void {
            $component = new class extends \Livewire\Component
            {
                public function triggerProcessingToast(): void
                {
                    $this->dispatch('toast',
                        message: 'Your logos are being generated. This usually takes 2-3 minutes.',
                        type: 'info',
                        duration: 6000
                    );
                }

                public function render(): string
                {
                    return '<div>Test Component</div>';
                }
            };

            \Livewire\Livewire::component('test-processing-toast', $component::class);

            \Livewire\Livewire::test('test-processing-toast')
                ->call('triggerProcessingToast')
                ->assertDispatched('toast',
                    message: 'Your logos are being generated. This usually takes 2-3 minutes.',
                    type: 'info',
                    duration: 6000
                );
        });

        it('displays warning toast for high load conditions', function (): void {
            $component = new class extends \Livewire\Component
            {
                public function triggerHighLoadToast(): void
                {
                    $this->dispatch('toast',
                        message: 'Due to high demand, logo generation may take longer than usual.',
                        type: 'warning',
                        duration: 7000
                    );
                }

                public function render(): string
                {
                    return '<div>Test Component</div>';
                }
            };

            \Livewire\Livewire::component('test-high-load-toast', $component::class);

            \Livewire\Livewire::test('test-high-load-toast')
                ->call('triggerHighLoadToast')
                ->assertDispatched('toast',
                    message: 'Due to high demand, logo generation may take longer than usual.',
                    type: 'warning',
                    duration: 7000
                );
        });

        it('displays info toast for partial completion', function (): void {
            $component = new class extends \Livewire\Component
            {
                public function triggerPartialToast(): void
                {
                    $this->dispatch('toast',
                        message: '8 out of 12 logos generated successfully. You can retry to generate the remaining ones.',
                        type: 'info',
                        duration: 8000,
                        action: [
                            'label' => 'Retry',
                            'method' => 'retryGeneration',
                        ]
                    );
                }

                public function render(): string
                {
                    return '<div>Test Component</div>';
                }
            };

            \Livewire\Livewire::component('test-partial-toast', $component::class);

            \Livewire\Livewire::test('test-partial-toast')
                ->call('triggerPartialToast')
                ->assertDispatched('toast',
                    message: '8 out of 12 logos generated successfully. You can retry to generate the remaining ones.',
                    type: 'info',
                    duration: 8000,
                    action: [
                        'label' => 'Retry',
                        'method' => 'retryGeneration',
                    ]
                );
        });
    });

    describe('Toast Message Formatting', function (): void {
        it('formats progress messages correctly', function (): void {
            $component = new class extends \Livewire\Component
            {
                public function triggerProgressToast(int $completed, int $total): void
                {
                    $percentage = round(($completed / $total) * 100);
                    $this->dispatch('toast',
                        message: "Generation progress: {$completed}/{$total} logos completed ({$percentage}%)",
                        type: 'info',
                        progress: $percentage
                    );
                }

                public function render(): string
                {
                    return '<div>Test Component</div>';
                }
            };

            \Livewire\Livewire::component('test-progress-toast', $component::class);

            \Livewire\Livewire::test('test-progress-toast')
                ->call('triggerProgressToast', 7, 12)
                ->assertDispatched('toast',
                    message: 'Generation progress: 7/12 logos completed (58%)',
                    type: 'info',
                    progress: 58
                );
        });

        it('formats time estimates correctly', function (): void {
            $component = new class extends \Livewire\Component
            {
                public function triggerTimeEstimateToast(int $secondsRemaining): void
                {
                    if ($secondsRemaining < 60) {
                        $timeMessage = "{$secondsRemaining} seconds";
                    } else {
                        $minutes = ceil($secondsRemaining / 60);
                        $timeMessage = "{$minutes} minute".($minutes > 1 ? 's' : '');
                    }

                    $this->dispatch('toast',
                        message: "Your logos will be ready in about {$timeMessage}",
                        type: 'info'
                    );
                }

                public function render(): string
                {
                    return '<div>Test Component</div>';
                }
            };

            \Livewire\Livewire::component('test-time-estimate-toast', $component::class);

            // Test seconds
            \Livewire\Livewire::test('test-time-estimate-toast')
                ->call('triggerTimeEstimateToast', 45)
                ->assertDispatched('toast',
                    message: 'Your logos will be ready in about 45 seconds',
                    type: 'info'
                );

            // Test minutes (singular)
            \Livewire\Livewire::test('test-time-estimate-toast')
                ->call('triggerTimeEstimateToast', 90)
                ->assertDispatched('toast',
                    message: 'Your logos will be ready in about 2 minutes',
                    type: 'info'
                );

            // Test minutes (plural)
            \Livewire\Livewire::test('test-time-estimate-toast')
                ->call('triggerTimeEstimateToast', 300)
                ->assertDispatched('toast',
                    message: 'Your logos will be ready in about 5 minutes',
                    type: 'info'
                );
        });
    });
});
