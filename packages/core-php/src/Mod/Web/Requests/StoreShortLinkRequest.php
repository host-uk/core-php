<?php

declare(strict_types=1);

namespace Core\Mod\Web\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation request for creating a new short link.
 */
class StoreShortLinkRequest extends FormRequest
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
            'url' => [
                'nullable',
                'string',
                'max:256',
                'regex:/^[a-z0-9\-_]+$/i',
            ],
            'destination_url' => [
                'required',
                'url',
                'max:2048',
            ],
            'project_id' => [
                'nullable',
                'integer',
                'exists:biolink_projects,id',
            ],
            'domain_id' => [
                'nullable',
                'integer',
                'exists:biolink_domains,id',
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
            'redirect_type' => [
                'sometimes',
                'string',
                Rule::in(['301', '302']),
            ],
            'password' => [
                'nullable',
                'string',
                'max:255',
            ],
            'cloaking' => [
                'sometimes',
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
            'url.regex' => 'The URL may only contain letters, numbers, hyphens, and underscores.',
            'destination_url.required' => 'A destination URL is required for short links.',
            'destination_url.url' => 'The destination URL must be a valid URL.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
            'redirect_type.in' => 'The redirect type must be 301 or 302.',
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
