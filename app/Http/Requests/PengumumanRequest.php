<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PengumumanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        $rules = [
            'judul'  => 'required|string|max:255',
            'konten' => 'required|string',
            'status' => 'sometimes|in:aktif,draft,berakhir',
            'foto'   => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'is_pinned' => 'sometimes|boolean', 
        ];

        // On update, make judul and konten optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['judul']  = 'sometimes|string|max:255';
            $rules['konten'] = 'sometimes|string';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'judul.required'  => 'Judul pengumuman wajib diisi.',
            'judul.max'       => 'Judul pengumuman maksimal 255 karakter.',
            'konten.required' => 'Konten pengumuman wajib diisi.',
            'status.in'       => 'Status harus berupa: aktif, draft, atau berakhir.',
            'foto.image'      => 'File harus berupa gambar.',
            'foto.mimes'      => 'Format gambar harus: jpeg, jpg, png, atau webp.',
            'foto.max'        => 'Ukuran gambar maksimal 2MB.',
        ];
    }
}
