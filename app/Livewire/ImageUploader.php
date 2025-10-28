<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Project;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ImageUploader extends Component
{
    use WithFileUploads;

    public Project $project;

    /** @var array<TemporaryUploadedFile> */
    public array $images = [];

    /** @var array<TemporaryUploadedFile> */
    public array $newFiles = [];

    public string $inspiration = '';

    /** @var array<string> */
    public array $tags = [];

    public bool $isPublic = false;

    public bool $isUploading = false;

    public string $uploadProgress = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:1'],
            'images.*' => [
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:51200', // 50MB
            ],
            'newFiles' => ['nullable', 'array', 'max:1'],
            'newFiles.*' => [
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:51200', // 50MB
            ],
            'inspiration' => ['required', 'string', 'max:1000'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            'isPublic' => ['boolean'],
        ];
    }

    public function updatedImages(): void
    {
        $this->validate(['images' => $this->rules()['images']]);

        foreach ($this->images as $index => $image) {
            try {
                $this->validate([
                    "images.{$index}" => $this->rules()['images.*'],
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->addError("images.{$index}", $e->getMessage());
                unset($this->images[$index]);
            }
        }
    }

    public function addTag(): void
    {
        $this->tags[] = '';
    }

    public function removeTag(int $index): void
    {
        unset($this->tags[$index]);
        $this->tags = array_values($this->tags);
    }

    public function removeImage(int $index): void
    {
        unset($this->images[$index]);
        $this->images = array_values($this->images);
    }

    public function updatedNewFiles(): void
    {
        if (! empty($this->newFiles)) {
            // Append new files to existing images array
            foreach ($this->newFiles as $file) {
                $this->images[] = $file;
            }

            // Clear the temporary newFiles array
            $this->newFiles = [];

            // Validate the updated images array
            $this->updatedImages();
        }
    }

    public function uploadImages(): void
    {
        // Check if project already has an image
        $existingImageCount = \App\Models\ProjectImage::where('project_id', $this->project->id)->count();
        if ($existingImageCount >= 1) {
            $this->addError('images', 'This project already has an inspiration image. Please delete the existing one before uploading a new one.');

            return;
        }

        $this->validate();

        if (empty($this->images)) {
            $this->addError('images', 'Please select at least one image to upload.');

            return;
        }

        $this->isUploading = true;
        $this->uploadProgress = 'Preparing upload...';

        try {
            // Process images directly without HTTP API call
            $uploadedImages = [];

            foreach ($this->images as $file) {
                // Store the original file
                $uuid = \Illuminate\Support\Str::uuid()->toString();
                $extension = $file->getClientOriginalExtension();
                $storedFilename = "{$uuid}.{$extension}";
                $filePath = $file->storeAs(
                    "projects/{$this->project->id}/images/originals",
                    $storedFilename,
                    'public'
                );

                // Create the database record
                $image = \App\Models\ProjectImage::create([
                    'uuid' => $uuid,
                    'project_id' => $this->project->id,
                    'user_id' => auth()->id(),
                    'original_filename' => $file->getClientOriginalName(),
                    'stored_filename' => $storedFilename,
                    'file_path' => $filePath,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'width' => null,
                    'height' => null,
                    'title' => $this->inspiration ?: null,
                    'description' => $this->inspiration ?: null,
                    'tags' => array_filter($this->tags) ?: null,
                    'processing_status' => 'pending',
                    'is_public' => $this->isPublic,
                ]);

                // Dispatch job for background processing
                dispatch(new \App\Jobs\ProcessUploadedImageJob($image));

                $uploadedImages[] = $image;
            }

            // Update project counters
            $this->project->increment('total_images', count($uploadedImages));
            $totalSize = collect($this->images)->sum(fn ($file) => $file->getSize());
            $this->project->increment('storage_used_bytes', $totalSize);

            $this->uploadProgress = 'Upload completed successfully!';
            $this->reset(['images', 'inspiration', 'tags', 'isPublic']);

            // Emit event to refresh gallery
            $this->dispatch('images-uploaded');

            // Show success notification
            $this->dispatch('notify',
                message: 'Images uploaded successfully! Processing in background.',
                type: 'success'
            );

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Image upload exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'project_id' => $this->project->id,
            ]);

            $this->uploadProgress = 'Upload failed. Please try again.';
            $this->addError('upload', 'An error occurred during upload: '.$e->getMessage());
        }

        $this->isUploading = false;
    }

    #[On('refresh-uploader')]
    public function refresh(): void
    {
        // Component refresh trigger
    }

    /**
     * Handle Livewire serialization to prevent toJSON errors.
     */
    protected function serializeProperty(string $property): mixed
    {
        if ($this->$property instanceof \App\Models\Project) {
            return $this->$property->id;
        }

        return $this->$property;
    }

    /**
     * Handle Livewire hydration to restore objects from serialized data.
     */
    protected function hydrateProperty(string $property, mixed $value): mixed
    {
        if ($property === 'project' && is_int($value)) {
            return \App\Models\Project::find($value);
        }

        return $value;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.image-uploader');
    }
}
