<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body'        => 'nullable|string|max:5000',
            'type'        => 'nullable|in:text,image,file,gif',
            'file'        => 'nullable|file|max:10240', // 10MB max
            'gif_url'     => 'nullable|url',
            'reply_to_id' => 'nullable|integer|exists:messages,id_message',
        ];
    }

    public function messages(): array
    {
        return [
            'body.max'          => 'Pesan tidak boleh lebih dari 5000 karakter.',
            'type.in'           => 'Tipe pesan tidak valid.',
            'file.file'         => 'File tidak valid.',
            'file.max'          => 'Ukuran file maksimal 10MB.',
            'gif_url.url'       => 'URL GIF tidak valid.',
            'reply_to_id.exists'=> 'Pesan yang di-reply tidak ditemukan.',
        ];
    }

    /**
     * Validate that at least one of body, file, or gif_url is present.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->body && !$this->file('file') && !$this->gif_url) {
                $validator->errors()->add('body', 'Pesan, file, atau GIF harus diisi.');
            }
        });
    }
}
