<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_alumni' => ['sometimes', 'string', 'max:255'],
            'nis' => ['nullable', 'string', 'max:20'],
            'nisn' => ['nullable', 'string', 'max:20'],
            'jenis_kelamin' => ['sometimes', 'in:Laki-laki,Perempuan'],
            'tanggal_lahir' => ['nullable', 'date'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tahun_masuk' => ['nullable', 'integer'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'alamat' => ['nullable', 'string'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'id_jurusan' => ['sometimes', 'exists:jurusan,id_jurusan'],
            'tahun_lulus' => ['nullable', 'date'],

            'skills' => ['nullable', 'array'],
            'skills.*' => ['exists:skills,id_skills'],
            'social_media' => ['nullable', 'array'],
            'social_media.*.id_sosmed' => ['required_with:social_media', 'exists:social_media,id_sosmed'],
            'social_media.*.url' => ['required_with:social_media', 'string'],
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
