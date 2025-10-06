<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use App\Rules\HexColor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class SettingsRequest extends FormRequest
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
            'site_name' => 'nullable|string|max:255',
            'site_description' => 'nullable|string|max:1000',
            'site_keywords' => 'nullable|string|max:500',
            'default_language' => 'nullable|in:vi,en',
            'available_languages' => 'nullable|array',
            'available_languages.*' => 'in:vi,en',
            'primary_color' => ['nullable', new HexColor],
            'secondary_color' => ['nullable', new HexColor],
            'accent_color' => ['nullable', new HexColor],
            'background_color' => ['nullable', new HexColor],
            'text_color' => ['nullable', new HexColor],
            'dark_mode' => 'nullable|boolean',
            'maintenance_mode' => 'nullable|boolean',
            'maintenance_message' => 'nullable|string|max:1000',
            'analytics_code' => 'nullable|string|max:500',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'social_image' => 'nullable|string|max:500',
            'contact_email' => 'nullable|email|max:255',
            'admin_email' => 'nullable|email|max:255',
            'items_per_page' => 'nullable|integer|min:5|max:100',
            'session_timeout' => 'nullable|integer|min:15|max:1440', // 15 minutes to 24 hours
            'file_upload_max_size' => 'nullable|integer|min:1|max:50', // 1MB to 50MB
            'allowed_file_types' => 'nullable|array',
            'allowed_file_types.*' => 'string|max:10'
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'site_name.max' => 'Site name must not exceed 255 characters',
            'site_description.max' => 'Site description must not exceed 1000 characters',
            'site_keywords.max' => 'Site keywords must not exceed 500 characters',
            'default_language.in' => 'Default language must be either vi or en',
            'available_languages.array' => 'Available languages must be an array',
            'available_languages.*.in' => 'Each language must be either vi or en',
            'primary_color.regex' => 'Primary color must be a valid hex color code (e.g., #FF6B6B)',
            'secondary_color.regex' => 'Secondary color must be a valid hex color code (e.g., #FF6B6B)',
            'accent_color.regex' => 'Accent color must be a valid hex color code (e.g., #FF6B6B)',
            'background_color.regex' => 'Background color must be a valid hex color code (e.g., #FF6B6B)',
            'text_color.regex' => 'Text color must be a valid hex color code (e.g., #FF6B6B)',
            'dark_mode.boolean' => 'Dark mode must be true or false',
            'maintenance_mode.boolean' => 'Maintenance mode must be true or false',
            'maintenance_message.max' => 'Maintenance message must not exceed 1000 characters',
            'analytics_code.max' => 'Analytics code must not exceed 500 characters',
            'seo_title.max' => 'SEO title must not exceed 255 characters',
            'seo_description.max' => 'SEO description must not exceed 500 characters',
            'social_image.max' => 'Social image URL must not exceed 500 characters',
            'contact_email.email' => 'Contact email must be a valid email address',
            'admin_email.email' => 'Admin email must be a valid email address',
            'items_per_page.integer' => 'Items per page must be an integer',
            'items_per_page.min' => 'Items per page must be at least 5',
            'items_per_page.max' => 'Items per page must not exceed 100',
            'session_timeout.integer' => 'Session timeout must be an integer',
            'session_timeout.min' => 'Session timeout must be at least 15 minutes',
            'session_timeout.max' => 'Session timeout must not exceed 1440 minutes (24 hours)',
            'file_upload_max_size.integer' => 'File upload max size must be an integer',
            'file_upload_max_size.min' => 'File upload max size must be at least 1MB',
            'file_upload_max_size.max' => 'File upload max size must not exceed 50MB',
            'allowed_file_types.array' => 'Allowed file types must be an array',
            'allowed_file_types.*.string' => 'Each file type must be a string',
            'allowed_file_types.*.max' => 'Each file type must not exceed 10 characters'
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
