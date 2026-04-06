<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KemitraanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipe'  => 'required|in:universitas,perusahaan',
            'nama'  => 'required|string|max:255',
            'jalan' => 'nullable|string|max:500',
            'logo'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_logo'     => 'nullable|boolean',
            'id_universitas'  => 'nullable|integer|exists:universitas,id_universitas',
            'id_perusahaan'   => 'nullable|integer|exists:perusahaan,id_perusahaan',
        ];
    }

    public function messages(): array
    {
        return [
            'tipe.required'  => 'Tipe kemitraan wajib diisi.',
            'tipe.in'        => 'Tipe harus berupa "universitas" atau "perusahaan".',
            'nama.required'  => 'Nama mitra wajib diisi.',
            'nama.max'       => 'Nama mitra maksimal 255 karakter.',
            'jalan.max'      => 'Alamat maksimal 500 karakter.',
            'logo.image'     => 'Logo harus berupa file gambar.',
            'logo.mimes'     => 'Logo harus berformat JPG, JPEG, PNG, atau WebP.',
            'logo.max'       => 'Ukuran logo maksimal 2MB.',
            'id_universitas.exists' => 'Universitas yang dipilih tidak valid.',
            'id_perusahaan.exists'  => 'Perusahaan yang dipilih tidak valid.',
        ];
    }
}
