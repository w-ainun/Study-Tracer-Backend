<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CareerStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_status' => ['required', 'exists:status,id_status'],
            'tahun_mulai' => ['nullable', 'integer'],
            'tahun_selesai' => ['nullable', 'integer'],

            // If Bekerja
            'pekerjaan' => ['nullable', 'array'],
            'pekerjaan.posisi' => ['required_with:pekerjaan', 'string'],
            'pekerjaan.nama_perusahaan' => ['required_with:pekerjaan', 'string'],
            'pekerjaan.id_kota' => ['nullable', 'exists:kota,id_kota'],
            'pekerjaan.jalan' => ['nullable', 'string'],

            // If Kuliah (accept nama_universitas or id_universitas)
            'universitas' => ['nullable', 'array'],
            'universitas.nama_universitas' => ['nullable', 'string'],
            'universitas.id_universitas' => ['nullable', 'exists:universitas,id_universitas'],
            'universitas.alamat' => ['nullable', 'string', 'max:500'],
            'universitas.id_kota' => ['nullable', 'exists:kota,id_kota'],
            'universitas.id_jurusanKuliah' => ['nullable', 'exists:jurusan_kuliah,id_jurusanKuliah'],
            'universitas.jalur_masuk' => ['nullable', 'string'],
            'universitas.jenjang' => ['nullable', 'string'],

            // Legacy kuliah key (backward compat)
            'kuliah' => ['nullable', 'array'],
            'kuliah.id_universitas' => ['nullable', 'exists:universitas,id_universitas'],
            'kuliah.nama_universitas' => ['nullable', 'string'],
            'kuliah.alamat' => ['nullable', 'string', 'max:500'],
            'kuliah.id_kota' => ['nullable', 'exists:kota,id_kota'],
            'kuliah.id_jurusanKuliah' => ['nullable', 'exists:jurusan_kuliah,id_jurusanKuliah'],
            'kuliah.jalur_masuk' => ['nullable', 'string'],
            'kuliah.jenjang' => ['nullable', 'string'],

            // If Wirausaha
            'wirausaha' => ['nullable', 'array'],
            'wirausaha.id_bidang' => ['required_with:wirausaha', 'exists:bidang_usaha,id_bidang'],
            'wirausaha.nama_usaha' => ['required_with:wirausaha', 'string'],
            'wirausaha.alamat' => ['nullable', 'string', 'max:500'],
            'wirausaha.id_kota' => ['nullable', 'exists:kota,id_kota'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_status.required' => 'Status karir wajib dipilih.',
            'id_status.exists' => 'Status karir tidak valid.',
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
