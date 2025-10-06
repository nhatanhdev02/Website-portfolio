<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use App\Rules\HexColor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ServiceRequest extends FormRequest
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
            'title_vi' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'description_vi' => 'required|string|max:1000',
            'description_en' => 'required|string|max:1000',
            'icon' => 'required|string|max:100',
            'color' => ['required', new HexColor],
            'bg_color' => ['required', new HexColor],
            'order' => 'nullable|integer|min:0'
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title_vi.required' => 'Vietnamese title is required',
            'title_en.required' => 'English title is required',
            'description_vi.required' => 'Vietnamese description is required',
            'description_en.required' => 'English description is required',
            'icon.required' => 'Icon is required',
            'color.required' => 'Color is required',
            'bg_color.required' => 'Background color is required',
            'color.regex' => 'Color must be a valid hex color code (e.g., #FF6B6B)',
            'bg_color.regex' => 'Background color must be a valid hex color code (e.g., #FFE5E5)',
            'order.integer' => 'Order must be an integer',
            'order.min' => 'Order must be at least 0',
            '*.max' => 'The :attribute field must not exceed :max characters'
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
