<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreLowonganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul_lowongan' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'tipe_pekerjaan' => ['nullable', 'string', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:draft,published,closed'],
            'lowongan_selesai' => ['nullable', 'date'],
            'id_pekerjaan' => ['nullable', 'exists:pekerjaan,id_pekerjaan'],
            'foto_lowongan' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            // Either id_perusahaan OR nama_perusahaan can be provided
            'id_perusahaan' => ['nullable', 'exists:perusahaan,id_perusahaan'],
            'nama_perusahaan' => ['nullable', 'string', 'max:255', 'required_without:id_perusahaan'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul_lowongan.required' => 'Judul lowongan wajib diisi.',
            'id_perusahaan.exists' => 'Perusahaan tidak valid.',
            'nama_perusahaan.required_without' => 'Nama perusahaan wajib diisi jika tidak memilih perusahaan.',
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
