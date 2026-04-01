<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePengaturanTampilanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by route middleware (role:admin)
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $logoRules = ['nullable'];
        $loginBgRules = ['nullable'];

        // If sent as file upload → validate as image
        if ($this->hasFile('logo')) {
            $logoRules[] = 'image';
            $logoRules[] = 'mimes:png';
            $logoRules[] = 'max:2048'; // Max 2MB
        } else {
            // If sent as base64 data URL string → validate as string
            $logoRules[] = 'string';
        }

        if ($this->hasFile('login_bg')) {
            $loginBgRules[] = 'image';
            $loginBgRules[] = 'mimes:jpg,jpeg,png';
            $loginBgRules[] = 'max:5120'; // Max 5MB
        } else {
            $loginBgRules[] = 'string';
        }

        return [
            'nama_sekolah'    => 'sometimes|string|max:255',
            'logo'            => $logoRules,
            'login_bg'        => $loginBgRules,
            'primary_color'   => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'third_color'     => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'remove_logo'     => 'sometimes|boolean',
            'remove_login_bg' => 'sometimes|boolean',
        ];
    }

    /**
     * Custom validation messages in Indonesian.
     */
    public function messages(): array
    {
        return [
            'nama_sekolah.max'       => 'Nama sekolah maksimal 255 karakter.',
            'logo.image'             => 'Logo harus berupa file gambar.',
            'logo.mimes'             => 'Logo harus berformat PNG.',
            'logo.max'               => 'Ukuran logo maksimal 2MB.',
            'login_bg.image'         => 'Background login harus berupa file gambar.',
            'login_bg.mimes'         => 'Background login harus berformat JPG atau PNG.',
            'login_bg.max'           => 'Ukuran background login maksimal 5MB.',
            'primary_color.regex'    => 'Format warna primary tidak valid (contoh: #3C5759).',
            'secondary_color.regex'  => 'Format warna secondary tidak valid (contoh: #F3F4F4).',
            'third_color.regex'      => 'Format warna third tidak valid (contoh: #9CA3AF).',
        ];
    }
}
