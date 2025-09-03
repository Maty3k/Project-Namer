<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportMoodBoardRequest extends FormRequest
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
            'format' => ['required', 'string', Rule::in(['pdf', 'png', 'jpg'])],
            'quality' => ['nullable', 'integer', 'min:60', 'max:100'],
            'width' => ['nullable', 'integer', 'min:800', 'max:4000'],
            'height' => ['nullable', 'integer', 'min:600', 'max:4000'],
            'include_metadata' => ['nullable', 'boolean'],
            'background_transparent' => ['nullable', 'boolean'],
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
            'format.required' => 'Export format is required.',
            'format.in' => 'Invalid export format. Choose from: pdf, png, jpg.',
            'quality.min' => 'Quality must be at least 60%.',
            'quality.max' => 'Quality must not exceed 100%.',
            'width.min' => 'Width must be at least 800 pixels.',
            'width.max' => 'Width must not exceed 4000 pixels.',
            'height.min' => 'Height must be at least 600 pixels.',
            'height.max' => 'Height must not exceed 4000 pixels.',
        ];
    }
}
