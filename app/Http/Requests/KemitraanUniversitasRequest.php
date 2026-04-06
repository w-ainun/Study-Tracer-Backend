<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KemitraanUniversitasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama'  => 'required|string|max:255',
            'jalan' => 'nullable|string|max:500',
            'logo'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama universitas wajib diisi.',
            'nama.max'      => 'Nama universitas maksimal 255 karakter.',
            'jalan.max'     => 'Alamat maksimal 500 karakter.',
            'logo.image'    => 'Logo harus berupa file gambar.',
            'logo.mimes'    => 'Logo harus berformat JPG, JPEG, PNG, atau WebP.',
            'logo.max'      => 'Ukuran logo maksimal 2MB.',
        ];
    }
}
