<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWirausahaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'nama_usaha'  => ($isUpdate ? 'sometimes' : 'required') . '|string|max:255',
            'id_bidang'   => ($isUpdate ? 'sometimes' : 'required') . '|exists:bidang_usaha,id_bidang',
            'alamat'      => 'nullable|string|max:500',
            'id_kota'     => 'nullable|exists:kota,id_kota',
            'id_riwayat'  => ($isUpdate ? 'sometimes' : 'required') . '|exists:riwayat_status,id_riwayat',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_usaha.required'  => 'Nama usaha wajib diisi.',
            'nama_usaha.max'       => 'Nama usaha maksimal 255 karakter.',
            'id_bidang.required'   => 'Bidang usaha wajib dipilih.',
            'id_bidang.exists'     => 'Bidang usaha tidak valid.',
            'id_kota.exists'       => 'Kota tidak valid.',
            'id_riwayat.required'  => 'Data riwayat status wajib dipilih.',
            'id_riwayat.exists'    => 'Data riwayat status tidak valid.',
            'latitude.between'     => 'Latitude harus antara -90 dan 90.',
            'longitude.between'    => 'Longitude harus antara -180 dan 180.',
        ];
    }
}
