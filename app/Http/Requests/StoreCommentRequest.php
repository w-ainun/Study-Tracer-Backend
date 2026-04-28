<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content'           => 'required|string|max:2000',
            'id_parent_comment' => 'sometimes|nullable|integer|exists:post_comments,id_comment',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required'            => 'Komentar tidak boleh kosong.',
            'content.max'                 => 'Komentar maksimal 2000 karakter.',
            'id_parent_comment.exists'    => 'Komentar yang ingin di-reply tidak ditemukan.',
        ];
    }
}
