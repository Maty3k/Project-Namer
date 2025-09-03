<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UploadProjectImagesRequest extends FormRequest
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
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:51200', // 50MB in kilobytes
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
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
            'images.required' => 'At least one image file is required.',
            'images.array' => 'Images must be provided as an array.',
            'images.max' => 'You can upload a maximum of 20 images at once.',
            'images.*.required' => 'Each image file is required.',
            'images.*.file' => 'Each upload must be a valid file.',
            'images.*.image' => 'Each file must be a valid image.',
            'images.*.mimes' => 'Images must be in JPEG, PNG, WebP, or GIF format.',
            'images.*.max' => 'Each image must be smaller than 50MB.',
            'tags.max' => 'You can add a maximum of 10 tags.',
            'tags.*.max' => 'Each tag must be 50 characters or less.',
        ];
    }
}
