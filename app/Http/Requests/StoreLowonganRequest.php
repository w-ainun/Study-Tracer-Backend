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

    public function prepareForValidation()
    {
        // Handle input fields
        $this->merge([
            'judul_lowongan' => $this->input('judul') ?? $this->input('judul_lowongan'),
            'nama_perusahaan' => $this->input('perusahaan') ?? $this->input('nama_perusahaan'),
            'lowongan_selesai' => $this->input('tanggal_berakhir') ?? $this->input('lowongan_selesai'),
        ]);

        // Handle file upload renaming (foto -> foto_lowongan)
        if ($this->hasFile('foto')) {
            $this->files->set('foto_lowongan', $this->file('foto'));
        }
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
            // Allow both 'foto' and 'foto_lowongan' keys during validation check, but prepareForValidation merges to foto_lowongan
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
