<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class BulkActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() instanceof Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'action' => 'required|in:mark_read,mark_unread,delete',
            'message_ids' => 'required|array|min:1',
            'message_ids.*' => 'integer|exists:contact_messages,id'
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Action is required',
            'action.in' => 'Action must be one of: mark_read, mark_unread, delete',
            'message_ids.required' => 'Message IDs are required',
            'message_ids.array' => 'Message IDs must be an array',
            'message_ids.min' => 'At least one message ID is required',
            'message_ids.*.integer' => 'Each message ID must be an integer',
            'message_ids.*.exists' => 'One or more message IDs do not exist'
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
