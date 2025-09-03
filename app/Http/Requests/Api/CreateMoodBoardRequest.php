<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\MoodBoardLayout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMoodBoardRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'layout_type' => ['required', 'string', Rule::in(array_map(fn ($case) => $case->value, MoodBoardLayout::cases()))],
            'layout_config' => ['nullable', 'array'],
            'layout_config.background_color' => ['nullable', 'string', 'regex:/^#[0-9a-f]{6}$/i'],
            'layout_config.grid_size' => ['nullable', 'integer', 'min:10', 'max:50'],
            'layout_config.snap_to_grid' => ['nullable', 'boolean'],
            'layout_config.images' => ['nullable', 'array', 'max:100'],
            'is_public' => ['nullable', 'boolean'],
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
            'name.required' => 'Mood board name is required.',
            'name.max' => 'Mood board name must not exceed 255 characters.',
            'description.max' => 'Description must not exceed 1000 characters.',
            'layout_type.required' => 'Layout type is required.',
            'layout_type.in' => 'Invalid layout type. Choose from: grid, collage, freeform.',
            'layout_config.background_color.regex' => 'Background color must be a valid hex color (e.g., #ffffff).',
            'layout_config.grid_size.min' => 'Grid size must be at least 10 pixels.',
            'layout_config.grid_size.max' => 'Grid size must not exceed 50 pixels.',
            'layout_config.images.max' => 'Mood board cannot contain more than 100 images.',
        ];
    }
}
