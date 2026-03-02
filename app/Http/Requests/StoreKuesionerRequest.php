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
            'id_status' => ['nullable', 'exists:status,id_status'],
            'title' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:hidden,aktif,draft'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'tanggal_publikasi' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_status.exists' => 'Status karier tidak valid.',
            'title.required' => 'Judul kuesioner wajib diisi.',
            'status.required' => 'Status kuesioner wajib dipilih.',
            'status.in' => 'Status kuesioner harus salah satu dari: hidden, aktif, atau draft.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
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
