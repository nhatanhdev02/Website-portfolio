<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ReorderRequest extends FormRequest
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
            'order' => 'required|array|min:1',
            'order.*.id' => 'required|integer|exists:services,id',
            'order.*.position' => 'required|integer|min:0'
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'order.required' => 'Order data is required',
            'order.array' => 'Order must be an array',
            'order.min' => 'At least one item must be provided',
            'order.*.id.required' => 'Service ID is required for each item',
            'order.*.id.integer' => 'Service ID must be an integer',
            'order.*.id.exists' => 'Service ID does not exist',
            'order.*.position.required' => 'Position is required for each item',
            'order.*.position.integer' => 'Position must be an integer',
            'order.*.position.min' => 'Position must be at least 0'
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
