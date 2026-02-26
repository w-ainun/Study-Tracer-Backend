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
            'pertanyaan' => ['required', 'string'],
            'opsi' => ['nullable', 'array'],
            'opsi.*' => ['string'],
            'tipe_pertanyaan' => ['sometimes', 'string', 'in:pilihan_tunggal,pilihan_ganda,teks_pendek,skala'],
            'status_pertanyaan' => ['sometimes', 'string', 'in:TERLIHAT,TERSEMBUNYI,DRAF'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'judul_bagian' => ['nullable', 'string', 'max:255'],
            'urutan' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'pertanyaan.required' => 'Pertanyaan wajib diisi.',
            'tipe_pertanyaan.in' => 'Tipe pertanyaan harus: pilihan_tunggal, pilihan_ganda, teks_pendek, atau skala.',
            'status_pertanyaan.in' => 'Status pertanyaan harus: TERLIHAT, TERSEMBUNYI, atau DRAF.',
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
