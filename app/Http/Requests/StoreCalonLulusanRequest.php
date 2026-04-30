<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalonLulusanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nisn'       => ['required', 'string', 'max:20'],
            'nama'       => ['required', 'string', 'max:150'],
            'id_jurusan' => ['required', 'integer', 'exists:jurusan,id_jurusan'],
        ];
    }

    public function messages(): array
    {
        return [
            'nisn.required'       => 'NISN wajib diisi',
            'nisn.max'            => 'NISN maksimal 20 karakter',
            'nama.required'       => 'Nama siswa wajib diisi',
            'nama.max'            => 'Nama siswa maksimal 150 karakter',
            'id_jurusan.required' => 'Jurusan wajib dipilih',
            'id_jurusan.exists'   => 'Jurusan yang dipilih tidak valid',
        ];
    }
}
