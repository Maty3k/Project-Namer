<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Logo generation request validation.
 */
class GenerateLogosRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'min:1', 'max:255'],
            'business_description' => ['nullable', 'string', 'max:1000'],
            'session_id' => ['required', 'string', 'min:1', 'max:255'],
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
            'business_name.required' => 'A business name is required to generate logos.',
            'business_name.min' => 'Business name cannot be empty.',
            'business_name.max' => 'Business name cannot exceed 255 characters.',
            'business_description.max' => 'Business description cannot exceed 1000 characters.',
            'session_id.required' => 'A session ID is required for tracking.',
            'session_id.min' => 'Session ID cannot be empty.',
            'session_id.max' => 'Session ID cannot exceed 255 characters.',
        ];
    }
}
