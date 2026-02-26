<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePertanyaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_sectionques' => ['sometimes', 'integer', 'exists:section_ques,id_sectionques'],
            'isi_pertanyaan' => ['sometimes', 'string'],
            'status_pertanyaan' => ['sometimes', 'in:publish,draft,hidden'],
            'judul_bagian' => ['sometimes', 'string', 'max:255'],
            'opsi' => ['sometimes', 'array'],
            'opsi.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
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
