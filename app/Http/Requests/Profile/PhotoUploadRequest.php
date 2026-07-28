<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class PhotoUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120', // 5 MB
                'dimensions:min_width=200,min_height=200,max_width=6000,max_height=6000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.max' => 'The photo must be 5 MB or smaller.',
            'photo.dimensions' => 'Please upload a photo at least 200×200 pixels.',
        ];
    }
}
