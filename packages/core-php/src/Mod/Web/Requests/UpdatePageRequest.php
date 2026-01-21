<?php

declare(strict_types=1);

namespace Core\Mod\Web\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation request for updating an existing bio.
 */
class UpdatePageRequest extends FormRequest
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
                'sometimes',
                'string',
                'max:256',
                'regex:/^[a-z0-9\-_]+$/i',
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
            'location_url' => [
                'nullable',
                'url',
                'max:2048',
            ],
            'settings' => [
                'nullable',
                'array',
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
            'url.regex' => 'The URL may only contain letters, numbers, hyphens, and underscores.',
            'location_url.url' => 'The destination URL must be a valid URL.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}
