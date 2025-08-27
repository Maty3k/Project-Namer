<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Services\ColorPaletteService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Logo customization request validation.
 */
class CustomizeLogosRequest extends FormRequest
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
        $logoGeneration = $this->route('logoGeneration');
        $colorPaletteService = app(ColorPaletteService::class);

        return [
            'color_scheme' => [
                'required',
                'string',
                Rule::in(array_keys($colorPaletteService->getAllColorSchemes())),
            ],
            'logo_ids' => ['required', 'array', 'min:1'],
            'logo_ids.*' => [
                'required',
                'integer',
                Rule::exists('generated_logos', 'id')->where(function ($query) use ($logoGeneration): void {
                    $query->where('logo_generation_id', $logoGeneration->id);
                }),
            ],
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
            'color_scheme.required' => 'A color scheme must be selected.',
            'color_scheme.in' => 'The selected color scheme is not valid.',
            'logo_ids.required' => 'At least one logo must be selected for customization.',
            'logo_ids.array' => 'Logo IDs must be provided as an array.',
            'logo_ids.min' => 'At least one logo must be selected.',
            'logo_ids.*.required' => 'Each logo ID is required.',
            'logo_ids.*.integer' => 'Each logo ID must be a valid integer.',
            'logo_ids.*.exists' => 'One or more selected logos do not exist or do not belong to this generation.',
        ];
    }
}
