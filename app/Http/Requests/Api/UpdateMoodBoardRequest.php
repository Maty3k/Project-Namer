<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\MoodBoardLayout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMoodBoardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'layout_type' => ['sometimes', 'string', Rule::in(array_map(fn ($case) => $case->value, MoodBoardLayout::cases()))],
            'layout_config' => ['sometimes', 'array'],
            'layout_config.background_color' => ['nullable', 'string', 'regex:/^#[0-9a-f]{6}$/i'],
            'layout_config.grid_size' => ['nullable', 'integer', 'min:10', 'max:50'],
            'layout_config.snap_to_grid' => ['nullable', 'boolean'],
            'layout_config.images' => ['nullable', 'array', 'max:100'],
            'layout_config.images.*.image_uuid' => ['required_with:layout_config.images.*', 'string', 'uuid'],
            'layout_config.images.*.x' => ['required_with:layout_config.images.*', 'numeric'],
            'layout_config.images.*.y' => ['required_with:layout_config.images.*', 'numeric'],
            'layout_config.images.*.width' => ['required_with:layout_config.images.*', 'numeric', 'min:50'],
            'layout_config.images.*.height' => ['required_with:layout_config.images.*', 'numeric', 'min:50'],
            'layout_config.images.*.rotation' => ['nullable', 'numeric', 'between:-360,360'],
            'layout_config.images.*.z_index' => ['nullable', 'integer', 'min:0'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Mood board name must not exceed 255 characters.',
            'description.max' => 'Description must not exceed 1000 characters.',
            'layout_type.in' => 'Invalid layout type. Choose from: grid, collage, freeform.',
            'layout_config.background_color.regex' => 'Background color must be a valid hex color (e.g., #ffffff).',
            'layout_config.grid_size.min' => 'Grid size must be at least 10 pixels.',
            'layout_config.grid_size.max' => 'Grid size must not exceed 50 pixels.',
            'layout_config.images.max' => 'Mood board cannot contain more than 100 images.',
            'layout_config.images.*.image_uuid.required_with' => 'Image UUID is required for each image position.',
            'layout_config.images.*.x.required_with' => 'X position is required for each image.',
            'layout_config.images.*.y.required_with' => 'Y position is required for each image.',
            'layout_config.images.*.width.min' => 'Image width must be at least 50 pixels.',
            'layout_config.images.*.height.min' => 'Image height must be at least 50 pixels.',
            'layout_config.images.*.rotation.between' => 'Rotation must be between -360 and 360 degrees.',
            'layout_config.images.*.z_index.min' => 'Z-index must be a non-negative number.',
        ];
    }
}
