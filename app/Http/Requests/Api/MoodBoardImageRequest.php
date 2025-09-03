<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class MoodBoardImageRequest extends FormRequest
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
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'image_uuids' => ['required', 'array', 'min:1', 'max:50'],
            'image_uuids.*' => ['string', 'uuid'],
            'positions' => ['nullable', 'array'],
            'positions.*.image_uuid' => ['required_with:positions.*', 'string', 'uuid'],
            'positions.*.x' => ['required_with:positions.*', 'numeric'],
            'positions.*.y' => ['required_with:positions.*', 'numeric'],
            'positions.*.width' => ['required_with:positions.*', 'numeric', 'min:50', 'max:2000'],
            'positions.*.height' => ['required_with:positions.*', 'numeric', 'min:50', 'max:2000'],
            'positions.*.rotation' => ['nullable', 'numeric', 'between:-360,360'],
            'positions.*.z_index' => ['nullable', 'integer', 'min:0', 'max:1000'],
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
            'image_uuids.required' => 'You must select at least one image.',
            'image_uuids.max' => 'You cannot add more than 50 images to a mood board at once.',
            'image_uuids.*.uuid' => 'Invalid image identifier provided.',
            'positions.*.image_uuid.required_with' => 'Image UUID is required for each position.',
            'positions.*.x.required_with' => 'X position is required for each image.',
            'positions.*.y.required_with' => 'Y position is required for each image.',
            'positions.*.width.required_with' => 'Width is required for each image.',
            'positions.*.height.required_with' => 'Height is required for each image.',
            'positions.*.width.min' => 'Image width must be at least 50 pixels.',
            'positions.*.width.max' => 'Image width must not exceed 2000 pixels.',
            'positions.*.height.min' => 'Image height must be at least 50 pixels.',
            'positions.*.height.max' => 'Image height must not exceed 2000 pixels.',
            'positions.*.rotation.between' => 'Rotation must be between -360 and 360 degrees.',
            'positions.*.z_index.min' => 'Z-index must be a non-negative number.',
            'positions.*.z_index.max' => 'Z-index must not exceed 1000.',
        ];
    }
}
