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
            'id_status' => ['required', 'exists:status,id_status'],
            'judul_kuesioner' => ['required', 'string', 'max:255'],
            'deskripsi_kuesioner' => ['nullable', 'string'],
            'status_kuesioner' => ['sometimes', 'in:hidden,aktif,draft'],
            'tanggal_publikasi' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_status.required' => 'Status karier wajib dipilih.',
            'id_status.exists' => 'Status karier tidak valid.',
            'judul_kuesioner.required' => 'Judul kuesioner wajib diisi.',
            'status_kuesioner.in' => 'Status kuesioner harus: hidden, aktif, atau draft.',
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
