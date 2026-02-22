<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreKuesionerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul_kuesioner' => ['required', 'string', 'max:255'],
            'deskripsi_kuesioner' => ['required', 'string'],
            'status_kuesioner' => ['sometimes', 'in:draft,publish,close'],
            'tanggal_publikasi' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul_kuesioner.required' => 'Judul kuesioner wajib diisi.',
            'deskripsi_kuesioner.required' => 'Deskripsi kuesioner wajib diisi.',
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
