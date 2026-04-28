<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason'      => 'required|string|in:spam,harassment,inappropriate,misinformation,other',
            'description' => 'sometimes|nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan laporan wajib diisi.',
            'reason.in'       => 'Alasan laporan tidak valid.',
            'description.max' => 'Deskripsi maksimal 1000 karakter.',
        ];
    }
}
