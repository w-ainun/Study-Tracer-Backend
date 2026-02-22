<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AnswerKuesionerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jawaban' => ['required', 'array', 'min:1'],
            'jawaban.*.id_pertanyaan' => ['required', 'exists:pertanyaan_kuesioner,id_pertanyaanKuis'],
            'jawaban.*.id_opsiJawaban' => ['nullable', 'exists:opsi_jawaban,id_opsi'],
            'jawaban.*.jawaban' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'jawaban.required' => 'Jawaban wajib diisi.',
            'jawaban.min' => 'Minimal satu jawaban harus diisi.',
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
