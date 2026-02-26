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
            'pekerjaan.id_kota' => ['required_with:pekerjaan', 'exists:kota,id_kota'],
            'pekerjaan.jalan' => ['nullable', 'string'],

            // If Kuliah
            'kuliah' => ['nullable', 'array'],
            'kuliah.id_universitas' => ['required_with:kuliah', 'exists:universitas,id_universitas'],
            'kuliah.id_jurusanKuliah' => ['required_with:kuliah', 'exists:jurusan_kuliah,id_jurusanKuliah'],
            'kuliah.jalur_masuk' => ['required_with:kuliah', 'in:SNBP,SNBT,Mandiri,Beasiswa,lainnya'],
            'kuliah.jenjang' => ['required_with:kuliah', 'in:D3,D4,S1,S2,S3'],

            // If Wirausaha
            'wirausaha' => ['nullable', 'array'],
            'wirausaha.id_bidang' => ['required_with:wirausaha', 'exists:bidang_usaha,id_bidang'],
            'wirausaha.nama_usaha' => ['required_with:wirausaha', 'string'],
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
