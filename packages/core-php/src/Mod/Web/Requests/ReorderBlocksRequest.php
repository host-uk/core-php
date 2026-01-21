<?php

declare(strict_types=1);

namespace Core\Mod\Web\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation request for reordering blocks.
 */
class ReorderBlocksRequest extends FormRequest
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
            'order' => [
                'required',
                'array',
                'min:1',
            ],
            'order.*' => [
                'integer',
                'exists:biolink_blocks,id',
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
            'order.required' => 'The block order is required.',
            'order.array' => 'The order must be an array of block IDs.',
            'order.*.integer' => 'Each block ID must be an integer.',
            'order.*.exists' => 'One or more block IDs are invalid.',
        ];
    }
}
