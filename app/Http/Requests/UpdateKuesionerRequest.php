<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateKuesionerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul_kuesioner' => ['sometimes', 'string', 'max:255'],
            'deskripsi_kuesioner' => ['sometimes', 'string'],
            'status_kuesioner' => ['sometimes', 'in:draft,publish,close'],
            'tanggal_publikasi' => ['nullable', 'date'],
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
