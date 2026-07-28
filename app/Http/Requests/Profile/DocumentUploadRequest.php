<?php

namespace App\Http\Requests\Profile;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class DocumentUploadRequest extends FormRequest
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
            'type' => ['required', new Enum(DocumentType::class)],
            'document_number' => ['nullable', 'string', 'max:60'],
            'document' => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,webp,pdf',
                'max:8192', // 8 MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document.max' => 'The document must be 8 MB or smaller.',
            'document.mimes' => 'Upload a JPG, PNG, WEBP, or PDF file.',
        ];
    }
}
