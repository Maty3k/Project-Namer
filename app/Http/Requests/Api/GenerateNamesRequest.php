<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for AI name generation.
 */
final class GenerateNamesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => [
                'required',
                'integer',
                'exists:projects,id',
            ],
            'models' => [
                'required',
                'array',
                'min:1',
                'max:4',
            ],
            'models.*' => [
                'required',
                'string',
                'in:gpt-4,claude-3.5-sonnet,gemini-1.5-pro,grok-beta',
            ],
            'generation_mode' => [
                'required',
                'string',
                'in:creative,professional,brandable,tech-focused',
            ],
            'deep_thinking' => [
                'sometimes',
                'boolean',
            ],
            'business_description' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_id.required' => 'A project ID is required.',
            'project_id.exists' => 'The selected project does not exist.',
            'models.required' => 'At least one AI model must be selected.',
            'models.min' => 'At least one AI model must be selected.',
            'models.max' => 'A maximum of 4 AI models can be selected.',
            'models.*.in' => 'The selected AI model is not supported.',
            'generation_mode.required' => 'A generation mode is required.',
            'generation_mode.in' => 'The selected generation mode is not valid.',
            'business_description.required' => 'A business description is required.',
            'business_description.min' => 'The business description must be at least 10 characters.',
            'business_description.max' => 'The business description cannot exceed 2000 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure deep_thinking defaults to false if not provided
        if (! $this->has('deep_thinking')) {
            $this->merge(['deep_thinking' => false]);
        }
    }
}
