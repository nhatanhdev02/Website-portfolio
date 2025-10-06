<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class AboutRequest extends FormRequest
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
            'content_vi' => 'required|string|max:5000',
            'content_en' => 'required|string|max:5000',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'projects_completed' => 'nullable|integer|min:0|max:1000',
            'image' => 'nullable|string|max:500'
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'content_vi.required' => 'Vietnamese content is required',
            'content_en.required' => 'English content is required',
            'content_vi.max' => 'Vietnamese content must not exceed 5000 characters',
            'content_en.max' => 'English content must not exceed 5000 characters',
            'skills.array' => 'Skills must be an array',
            'skills.*.string' => 'Each skill must be a string',
            'skills.*.max' => 'Each skill must not exceed 100 characters',
            'experience_years.integer' => 'Experience years must be an integer',
            'experience_years.min' => 'Experience years must be at least 0',
            'experience_years.max' => 'Experience years must not exceed 50',
            'projects_completed.integer' => 'Projects completed must be an integer',
            'projects_completed.min' => 'Projects completed must be at least 0',
            'projects_completed.max' => 'Projects completed must not exceed 1000',
            'image.max' => 'Image URL must not exceed 500 characters'
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
