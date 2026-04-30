<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportKelulusanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,xlsx,xls,txt',
                'max:10240', // 10MB max
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File Excel/CSV wajib diunggah',
            'file.file'     => 'Upload harus berupa file yang valid',
            'file.mimes'    => 'Format file harus .csv, .xlsx, atau .xls',
            'file.max'      => 'Ukuran file maksimal 10MB',
        ];
    }
}
