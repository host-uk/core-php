<?php

declare(strict_types=1);

namespace Core\Mod\Web\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation request for creating a new block.
 */
class StoreBlockRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                'max:32',
            ],
            'location_url' => [
                'nullable',
                'url',
                'max:512',
            ],
            'settings' => [
                'nullable',
                'array',
            ],
            'order' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'is_enabled' => [
                'sometimes',
                'boolean',
            ],
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
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
            'type.required' => 'A block type is required.',
            'location_url.url' => 'The URL must be a valid URL.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Default is_enabled to true if not provided
        if (! $this->has('is_enabled')) {
            $this->merge(['is_enabled' => true]);
        }
    }
}
