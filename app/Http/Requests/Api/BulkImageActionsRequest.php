<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class BulkImageActionsRequest extends FormRequest
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
        // For DELETE requests, action defaults to 'delete'
        $isDeleteRequest = $this->isMethod('DELETE');

        return [
            'image_uuids' => ['required', 'array', 'min:1', 'max:100'],
            'image_uuids.*' => ['string', 'uuid'],
            'action' => [
                $isDeleteRequest ? 'sometimes' : 'required',
                'string',
                'in:delete,add_tags,remove_tags,toggle_public',
            ],
            'tags' => ['required_if:action,add_tags,remove_tags', 'array', 'max:20'],
            'tags.*' => ['string', 'max:50'],
            'is_public' => ['required_if:action,toggle_public', 'boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default action for DELETE requests
        if ($this->isMethod('DELETE') && ! $this->has('action')) {
            $this->merge(['action' => 'delete']);
        }
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
            'image_uuids.max' => 'You cannot perform bulk actions on more than 100 images at once.',
            'image_uuids.*.uuid' => 'Invalid image identifier provided.',
            'action.in' => 'Invalid action. Supported actions: delete, add_tags, remove_tags, toggle_public.',
            'tags.required_if' => 'Tags are required for tag-related actions.',
            'tags.max' => 'You cannot add more than 20 tags per image.',
            'tags.*.max' => 'Each tag must not exceed 50 characters.',
            'is_public.required_if' => 'Public status is required when toggling visibility.',
        ];
    }
}
