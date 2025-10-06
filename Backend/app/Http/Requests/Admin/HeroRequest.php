<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class HeroRequest extends FormRequest
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
            'greeting_vi' => 'required|string|max:500',
            'greeting_en' => 'required|string|max:500',
            'name' => 'required|string|max:255',
            'title_vi' => 'required|string|max:500',
            'title_en' => 'required|string|max:500',
            'subtitle_vi' => 'required|string|max:1000',
            'subtitle_en' => 'required|string|max:1000',
            'cta_text_vi' => 'required|string|max:100',
            'cta_text_en' => 'required|string|max:100',
            'cta_link' => 'required|url|max:255'
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'greeting_vi.required' => 'Vietnamese greeting is required',
            'greeting_en.required' => 'English greeting is required',
            'name.required' => 'Name is required',
            'title_vi.required' => 'Vietnamese title is required',
            'title_en.required' => 'English title is required',
            'subtitle_vi.required' => 'Vietnamese subtitle is required',
            'subtitle_en.required' => 'English subtitle is required',
            'cta_text_vi.required' => 'Vietnamese CTA text is required',
            'cta_text_en.required' => 'English CTA text is required',
            'cta_link.required' => 'CTA link is required',
            'cta_link.url' => 'CTA link must be a valid URL',
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
