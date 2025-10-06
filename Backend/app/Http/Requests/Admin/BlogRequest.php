<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use App\Rules\MarkdownContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class BlogRequest extends FormRequest
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
            'content_vi' => ['required', 'string', 'max:50000', new MarkdownContent],
            'content_en' => ['required', 'string', 'max:50000', new MarkdownContent],
            'excerpt_vi' => 'nullable|string|max:500',
            'excerpt_en' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max for new uploads
            'thumbnail_url' => 'nullable|string|max:500', // For existing thumbnail URLs
            'status' => 'nullable|in:draft,published',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'published_at' => 'nullable|date'
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
            'content_vi.required' => 'Vietnamese content is required',
            'content_en.required' => 'English content is required',
            'content_vi.max' => 'Vietnamese content must not exceed 50,000 characters',
            'content_en.max' => 'English content must not exceed 50,000 characters',
            'excerpt_vi.max' => 'Vietnamese excerpt must not exceed 500 characters',
            'excerpt_en.max' => 'English excerpt must not exceed 500 characters',
            'thumbnail.image' => 'Thumbnail must be an image',
            'thumbnail.mimes' => 'Thumbnail must be a file of type: jpeg, jpg, png, gif, webp',
            'thumbnail.max' => 'Thumbnail size must not exceed 5MB',
            'status.in' => 'Status must be either draft or published',
            'tags.array' => 'Tags must be an array',
            'tags.*.string' => 'Each tag must be a string',
            'tags.*.max' => 'Each tag must not exceed 50 characters',
            'published_at.date' => 'Published date must be a valid date',
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
