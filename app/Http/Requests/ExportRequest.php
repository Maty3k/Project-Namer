<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware and controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'exportable_type' => [
                'required',
                'string',
                Rule::in([\App\Models\LogoGeneration::class]),
            ],
            'exportable_id' => [
                'required',
                'integer',
                'exists:logo_generations,id',
                function ($attribute, $value, $fail): void {
                    // Verify user owns the logo generation
                    $logoGeneration = \App\Models\LogoGeneration::find($value);
                    if (! $logoGeneration || $logoGeneration->user_id !== auth()->id()) {
                        $fail('You can only export your own logo generations.');
                    }

                    // Verify logo generation is completed
                    if ($logoGeneration && $logoGeneration->status !== 'completed') {
                        $fail('You can only export completed logo generations.');
                    }
                },
            ],
            'export_type' => [
                'required',
                'string',
                Rule::in(['pdf', 'csv', 'json']),
            ],
            'expires_in_days' => [
                'required',
                'integer',
                'min:1',
                'max:30',
            ],
            'template' => [
                'required',
                'string',
                Rule::in(['default', 'professional']),
            ],
            'include_domains' => [
                'required',
                'boolean',
            ],
            'include_metadata' => [
                'required',
                'boolean',
            ],
            'include_logos' => [
                'required',
                'boolean',
            ],
            'include_branding' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'exportable_type.in' => 'Invalid exportable type. Only logo generations can be exported.',
            'exportable_id.exists' => 'The selected logo generation does not exist.',
            'export_type.in' => 'Export type must be PDF, CSV, or JSON.',
            'expires_in_days.min' => 'Export must expire in at least 1 day.',
            'expires_in_days.max' => 'Export cannot expire in more than 30 days.',
            'template.in' => 'Template must be either default or professional.',
            'include_domains.required' => 'Domain inclusion setting is required.',
            'include_metadata.required' => 'Metadata inclusion setting is required.',
            'include_logos.required' => 'Logo inclusion setting is required.',
            'include_branding.required' => 'Branding inclusion setting is required.',
        ];
    }

    /**
     * Get validated data with proper type casting.
     *
     * @return array<string, mixed>
     */
    public function getValidatedData(): array
    {
        $data = $this->validated();

        // Ensure boolean values are properly cast
        $data['include_domains'] = (bool) $data['include_domains'];
        $data['include_metadata'] = (bool) $data['include_metadata'];
        $data['include_logos'] = (bool) $data['include_logos'];
        $data['include_branding'] = (bool) $data['include_branding'];

        // Additional logic for export type restrictions
        if ($data['export_type'] === 'csv') {
            // CSV exports cannot include actual logo files
            $data['include_logos'] = false;
        }

        return $data;
    }
}
