<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ShareRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'shareable_type' => [
                'required',
                'string',
                Rule::in([\App\Models\LogoGeneration::class]),
            ],
            'shareable_id' => [
                'required',
                'integer',
                'exists:logo_generations,id',
                function ($attribute, $value, $fail): void {
                    // Verify user owns the logo generation
                    $logoGeneration = \App\Models\LogoGeneration::find($value);
                    if (! $logoGeneration || $logoGeneration->user_id !== auth()->id()) {
                        $fail('You can only share your own logo generations.');
                    }
                },
            ],
            'share_type' => [
                'required',
                'string',
                Rule::in(['public', 'password_protected']),
            ],
            'title' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[\p{L}\p{N}\s\-_.,!?()]+$/u', // Allow letters, numbers, spaces, and safe punctuation
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'password' => [
                'required_if:share_type,password_protected',
                'nullable',
                'string',
                'min:6',
                'max:255',
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after:now',
                'before:'.now()->addYear()->toDateString(), // Max 1 year
            ],
            'settings' => [
                'nullable',
                'array',
            ],
            'settings.show_title' => [
                'nullable',
                'boolean',
            ],
            'settings.show_description' => [
                'nullable',
                'boolean',
            ],
            'settings.show_logos' => [
                'nullable',
                'boolean',
            ],
            'settings.show_domain_status' => [
                'nullable',
                'boolean',
            ],
        ];

        // Additional validation for updates
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['shareable_type'] = ['sometimes'] + $rules['shareable_type'];
            $rules['shareable_id'] = ['sometimes'] + $rules['shareable_id'];
            $rules['share_type'] = ['sometimes'] + $rules['share_type'];
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shareable_type.in' => 'Invalid shareable type. Only logo generations can be shared.',
            'shareable_id.exists' => 'The selected logo generation does not exist.',
            'share_type.in' => 'Share type must be either public or password protected.',
            'title.regex' => 'Title contains invalid characters. Only letters, numbers, spaces, and basic punctuation are allowed.',
            'title.max' => 'Title cannot be longer than 255 characters.',
            'description.max' => 'Description cannot be longer than 1000 characters.',
            'password.required_if' => 'Password is required for password-protected shares.',
            'password.min' => 'Password must be at least 6 characters long.',
            'expires_at.after' => 'Expiration date must be in the future.',
            'expires_at.before' => 'Expiration date cannot be more than 1 year from now.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            // Sanitize input data
            'title' => $this->input('title') ? strip_tags(trim((string) $this->input('title'))) : null,
            'description' => $this->input('description') ? strip_tags(trim((string) $this->input('description'))) : null,
        ]);
    }

    /**
     * Get validated and sanitized data.
     *
     * @return array<string, mixed>
     */
    public function getSanitizedData(): array
    {
        $data = $this->validated();

        // Additional sanitization
        if (isset($data['title'])) {
            $data['title'] = htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
        }

        if (isset($data['description'])) {
            $data['description'] = htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8');
        }

        return $data;
    }
}
