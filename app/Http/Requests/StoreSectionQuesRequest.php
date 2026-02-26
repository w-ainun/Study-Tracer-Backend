<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectionQuesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Or implement proper authorization
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id_kuesioner' => 'required|exists:kuesioner,id_kuesioner',
            'judul_pertanyaan' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'id_kuesioner.required' => 'ID kuesioner wajib diisi',
            'id_kuesioner.exists' => 'Kuesioner tidak ditemukan',
            'judul_pertanyaan.required' => 'Judul pertanyaan wajib diisi',
            'judul_pertanyaan.string' => 'Judul pertanyaan harus berupa teks',
            'judul_pertanyaan.max' => 'Judul pertanyaan maksimal 255 karakter',
        ];
    }
}
