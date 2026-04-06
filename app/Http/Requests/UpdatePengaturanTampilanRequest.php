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
        $landingBgRules = ['nullable'];

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

        if ($this->hasFile('landing_bg')) {
            $landingBgRules[] = 'image';
            $landingBgRules[] = 'mimes:jpg,jpeg,png';
            $landingBgRules[] = 'max:5120'; // Max 5MB
        } else {
            $landingBgRules[] = 'string';
        }

        return [
            // Identitas & Media
            'nama_sekolah'     => 'sometimes|string|max:255',
            'logo'             => $logoRules,
            'login_bg'         => $loginBgRules,
            'landing_bg'       => $landingBgRules,
            'remove_logo'      => 'sometimes|boolean',
            'remove_login_bg'  => 'sometimes|boolean',
            'remove_landing_bg'=> 'sometimes|boolean',

            // Palet Warna
            'primary_color'    => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color'  => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'third_color'      => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            // Konten Footer & Kontak
            'deskripsi_footer' => 'sometimes|nullable|string|max:1000',
            'email_kontak'     => 'sometimes|nullable|email|max:255',
            'web_kontak'       => 'sometimes|nullable|string|max:255',
            'telp_kontak'      => 'sometimes|nullable|string|max:50',

            // Teks Modal
            'teks_privasi'     => 'sometimes|nullable|string|max:50000',
            'teks_layanan'     => 'sometimes|nullable|string|max:50000',
            'teks_dukungan'    => 'sometimes|nullable|string|max:5000',

            // Konten Landing Page
            'landing_title'       => 'sometimes|nullable|string|max:500',
            'landing_description' => 'sometimes|nullable|string|max:2000',
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
            'landing_bg.image'       => 'Background landing harus berupa file gambar.',
            'landing_bg.mimes'       => 'Background landing harus berformat JPG atau PNG.',
            'landing_bg.max'         => 'Ukuran background landing maksimal 5MB.',
            'primary_color.regex'    => 'Format warna primary tidak valid (contoh: #3C5759).',
            'secondary_color.regex'  => 'Format warna secondary tidak valid (contoh: #F3F4F4).',
            'third_color.regex'      => 'Format warna third tidak valid (contoh: #9CA3AF).',
            'email_kontak.email'     => 'Format email kontak tidak valid.',
            'deskripsi_footer.max'   => 'Deskripsi footer maksimal 1000 karakter.',
            'teks_privasi.max'       => 'Teks privasi maksimal 50.000 karakter.',
            'teks_layanan.max'       => 'Teks layanan maksimal 50.000 karakter.',
            'teks_dukungan.max'      => 'Teks dukungan maksimal 5.000 karakter.',
            'landing_title.max'      => 'Judul landing page maksimal 500 karakter.',
            'landing_description.max'=> 'Deskripsi landing page maksimal 2.000 karakter.',
        ];
    }
}
