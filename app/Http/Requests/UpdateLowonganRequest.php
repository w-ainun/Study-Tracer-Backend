<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLowonganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul_lowongan' => ['sometimes', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:draft,published,closed'],
            'lowongan_selesai' => ['nullable', 'date_format:H:i:s'],
            'id_pekerjaan' => ['nullable', 'exists:pekerjaan,id_pekerjaan'],
            'foto_lowongan' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'id_perusahaan' => ['sometimes', 'exists:perusahaan,id_perusahaan'],
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
