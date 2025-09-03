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

    public string $title = '';

    public string $description = '';

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
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => [
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:51200', // 50MB
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
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
                $this->validate(
                    ["images.{$index}" => $image],
                    ["images.{$index}" => $this->rules()['images.*']]
                );
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

    public function uploadImages(): void
    {
        $this->validate();

        if (empty($this->images)) {
            $this->addError('images', 'Please select at least one image to upload.');

            return;
        }

        $this->isUploading = true;
        $this->uploadProgress = 'Preparing upload...';

        try {
            // Upload images via API
            $http = \Illuminate\Support\Facades\Http::asForm();

            foreach ($this->images as $index => $file) {
                $http->attach("images[{$index}]", $file->getRealPath(), $file->getClientOriginalName());
            }

            $response = $http->post(route('api.projects.images.store', $this->project), [
                'title' => $this->title,
                'description' => $this->description,
                'tags' => array_filter($this->tags),
                'is_public' => $this->isPublic,
            ]);

            if ($response->successful()) {
                $this->uploadProgress = 'Upload completed successfully!';
                $this->reset(['images', 'title', 'description', 'tags', 'isPublic']);

                // Emit event to refresh gallery
                $this->dispatch('images-uploaded');

                // Show success notification
                $this->dispatch('notify',
                    message: 'Images uploaded successfully! Processing in background.',
                    type: 'success'
                );

            } else {
                $this->uploadProgress = 'Upload failed. Please try again.';
                $this->addError('upload', 'Failed to upload images. Please check file sizes and formats.');
            }

        } catch (\Exception) {
            $this->uploadProgress = 'Upload failed. Please try again.';
            $this->addError('upload', 'An error occurred during upload. Please try again.');
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
