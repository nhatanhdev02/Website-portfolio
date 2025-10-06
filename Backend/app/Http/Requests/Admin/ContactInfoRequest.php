<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ContactInfoRequest extends FormRequest
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
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'linkedin' => 'nullable|url|max:255',
            'github' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'website' => 'nullable|url|max:255'
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'phone.max' => 'Phone number must not exceed 20 characters',
            'address.max' => 'Address must not exceed 500 characters',
            'linkedin.url' => 'LinkedIn must be a valid URL',
            'github.url' => 'GitHub must be a valid URL',
            'facebook.url' => 'Facebook must be a valid URL',
            'twitter.url' => 'Twitter must be a valid URL',
            'instagram.url' => 'Instagram must be a valid URL',
            'website.url' => 'Website must be a valid URL',
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
