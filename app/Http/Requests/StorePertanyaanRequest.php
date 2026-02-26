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
            'judul_bagian' => ['nullable', 'string', 'max:255'],
            'opsi' => ['nullable', 'array'],
            'opsi.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'isi_pertanyaan.required' => 'Pertanyaan wajib diisi.',
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
