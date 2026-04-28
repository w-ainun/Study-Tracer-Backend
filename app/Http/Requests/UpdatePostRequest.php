<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content'          => 'sometimes|string|max:5000',
            'visibility'       => 'sometimes|in:connections,public',
            'images'           => 'sometimes|array|max:10',
            'images.*'         => 'image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'remove_images'    => 'sometimes|array',
            'remove_images.*'  => 'integer|exists:post_images,id_post_image',
        ];
    }

    public function messages(): array
    {
        return [
            'content.max'               => 'Konten postingan maksimal 5000 karakter.',
            'visibility.in'             => 'Visibility harus connections atau public.',
            'images.max'                => 'Maksimal 10 gambar per postingan.',
            'images.*.image'            => 'File harus berupa gambar.',
            'images.*.mimes'            => 'Format gambar harus jpeg, jpg, png, gif, atau webp.',
            'images.*.max'              => 'Ukuran gambar maksimal 5MB.',
            'remove_images.*.exists'    => 'Gambar yang ingin dihapus tidak ditemukan.',
        ];
    }
}
