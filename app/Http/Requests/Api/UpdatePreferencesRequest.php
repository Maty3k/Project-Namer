<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for updating user AI preferences.
 */
final class UpdatePreferencesRequest extends FormRequest
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
            'preferred_models' => [
                'sometimes',
                'array',
                'min:1',
                'max:4',
            ],
            'preferred_models.*' => [
                'required',
                'string',
                'in:gpt-4,claude-3.5-sonnet,gemini-1.5-pro,grok-beta',
            ],
            'default_generation_mode' => [
                'sometimes',
                'string',
                'in:creative,professional,brandable,tech-focused',
            ],
            'default_deep_thinking' => [
                'sometimes',
                'boolean',
            ],
            'enable_model_comparison' => [
                'sometimes',
                'boolean',
            ],
            'auto_select_best_model' => [
                'sometimes',
                'boolean',
            ],
            'max_concurrent_generations' => [
                'sometimes',
                'integer',
                'min:1',
                'max:5',
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
            'preferred_models.min' => 'At least one preferred model must be selected.',
            'preferred_models.max' => 'A maximum of 4 preferred models can be selected.',
            'preferred_models.*.in' => 'The selected AI model is not supported.',
            'default_generation_mode.in' => 'The selected generation mode is not valid.',
            'default_deep_thinking.boolean' => 'Deep thinking preference must be true or false.',
            'enable_model_comparison.boolean' => 'Model comparison preference must be true or false.',
        ];
    }
}
