<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogoGenerationRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'business_description' => ['required', 'string', 'min:10', 'max:1000'],
            'session_id' => ['required', 'string', 'min:1'],
            'style' => ['sometimes', 'string', 'in:minimalist,modern,playful,corporate'],
            'count' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'use_fallback' => ['sometimes', 'boolean'],
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
            'business_name.required' => 'Please provide your business name',
            'business_name.max' => 'Business name must be less than 255 characters',
            'business_description.required' => 'Please describe your business to help us create relevant logos',
            'business_description.min' => 'Please provide at least 10 characters to describe your business',
            'business_description.max' => 'Business description must be less than 1000 characters',
            'session_id.required' => 'Session ID is required',
            'session_id.min' => 'Session ID cannot be empty',
            'style.in' => 'Please select a valid style: minimalist, modern, playful, or corporate',
            'count.min' => 'Please generate at least 1 logo',
            'count.max' => 'You can generate up to 10 logos at once',
        ];
    }
}
