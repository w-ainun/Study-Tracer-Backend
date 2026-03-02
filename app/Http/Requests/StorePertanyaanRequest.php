<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePertanyaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'isi_pertanyaan' => ['required', 'string'],
            'status_pertanyaan' => ['sometimes', 'in:publish,draft,hidden'],
            'opsi' => ['nullable', 'array'],
            'opsi.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'isi_pertanyaan.required' => 'Pertanyaan wajib diisi.',
            'status_pertanyaan.in' => 'Status pertanyaan harus: publish, draft, atau hidden.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422));
    }
}
