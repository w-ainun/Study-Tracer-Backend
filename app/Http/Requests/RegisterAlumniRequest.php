<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Step 1: Account
            'email' => ['required', 'email', 'unique:users,email_users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // Step 2: Profile
            'nama_alumni' => ['required', 'string', 'max:255'],
            'nis' => ['nullable', 'string', 'max:20'],
            'nisn' => ['nullable', 'string', 'max:20'],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'tanggal_lahir' => ['nullable', 'date'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tahun_masuk' => ['nullable', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'alamat' => ['nullable', 'string'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'id_jurusan' => ['required', 'exists:jurusan,id_jurusan'],
            'tahun_lulus' => ['nullable', 'date'],

            // Skills & Social Media
            'skills' => ['nullable', 'array'],
            'skills.*' => ['exists:skills,id_skills'],
            'social_media' => ['nullable', 'array'],
            'social_media.*.id_sosmed' => ['required_with:social_media', 'exists:social_media,id_sosmed'],
            'social_media.*.url' => ['required_with:social_media', 'string', 'url'],

            // Step 3: Career Status
            'id_status' => ['nullable', 'exists:status,id_status'],
            'tahun_mulai' => ['nullable', 'integer'],
            'tahun_selesai' => ['nullable', 'integer'],

            // If Bekerja
            'pekerjaan' => ['nullable', 'array'],
            'pekerjaan.posisi' => ['required_with:pekerjaan', 'string'],
            'pekerjaan.nama_perusahaan' => ['required_with:pekerjaan', 'string'],
            'pekerjaan.id_kota' => ['required_with:pekerjaan', 'exists:kota,id_kota'],
            'pekerjaan.jalan' => ['nullable', 'string'],

            // If Kuliah
            'universitas' => ['nullable', 'array'],
            'universitas.nama_universitas' => ['required_with:universitas', 'string'],
            'universitas.id_jurusanKuliah' => ['required_with:universitas', 'exists:jurusan_kuliah,id_jurusanKuliah'],
            'universitas.jalur_masuk' => ['required_with:universitas', 'in:SNBP,SNBT,Mandiri,Beasiswa,lainnya'],
            'universitas.jenjang' => ['required_with:universitas', 'in:D3,D4,S1,S2,S3'],

            // If Wirausaha
            'wirausaha' => ['nullable', 'array'],
            'wirausaha.id_bidang' => ['required_with:wirausaha', 'exists:bidang_usaha,id_bidang'],
            'wirausaha.nama_usaha' => ['required_with:wirausaha', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'nama_alumni.required' => 'Nama alumni wajib diisi.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib diisi.',
            'id_jurusan.required' => 'Jurusan wajib dipilih.',
            'id_jurusan.exists' => 'Jurusan tidak valid.',
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
