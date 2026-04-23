<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateGroupConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_name'    => 'required|string|max:100',
            'participants'  => 'required|array|min:1',
            'participants.*'=> 'integer|exists:alumni,id_alumni',
            'avatar'        => 'nullable|image|max:2048', // 2MB max
        ];
    }

    public function messages(): array
    {
        return [
            'group_name.required'    => 'Nama grup harus diisi.',
            'group_name.max'         => 'Nama grup maksimal 100 karakter.',
            'participants.required'  => 'Minimal 1 peserta harus dipilih.',
            'participants.min'       => 'Minimal 1 peserta harus dipilih.',
            'participants.*.exists'  => 'Alumni yang dipilih tidak ditemukan.',
            'avatar.image'           => 'Avatar harus berupa gambar.',
            'avatar.max'             => 'Ukuran avatar maksimal 2MB.',
        ];
    }
}
