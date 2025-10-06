<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use App\Rules\TechnologyArray;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ProjectRequest extends FormRequest
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
            'description_vi' => 'required|string|max:2000',
            'description_en' => 'required|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max for new uploads
            'image_url' => 'nullable|string|max:500', // For existing image URLs
            'link' => 'nullable|url|max:500',
            'technologies' => ['required', new TechnologyArray],
            'category' => 'required|string|max:100',
            'featured' => 'nullable|boolean',
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
            'image.image' => 'File must be an image',
            'image.mimes' => 'Image must be a file of type: jpeg, jpg, png, gif, webp',
            'image.max' => 'Image size must not exceed 5MB',
            'link.url' => 'Link must be a valid URL',
            'technologies.required' => 'At least one technology is required',
            'technologies.array' => 'Technologies must be an array',
            'technologies.min' => 'At least one technology is required',
            'technologies.*.string' => 'Each technology must be a string',
            'technologies.*.max' => 'Each technology must not exceed 50 characters',
            'category.required' => 'Category is required',
            'featured.boolean' => 'Featured must be true or false',
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
