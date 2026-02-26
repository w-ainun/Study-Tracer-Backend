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
            'id_sectionques' => ['nullable', 'integer', 'exists:section_ques,id_sectionques'],
            'isi_pertanyaan' => ['required', 'string'],
            'status_pertanyaan' => ['sometimes', 'in:publish,draft,hidden'],
            'judul_bagian' => ['nullable', 'string', 'max:255'],
            'opsi' => ['nullable', 'array'],
            'opsi.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'isi_pertanyaan.required' => 'Pertanyaan wajib diisi.',
            'id_sectionques.exists' => 'Section yang dipilih tidak valid.',
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
