<?php

namespace App\Http\Requests\Chat;

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
            'content' => ['required', 'string', 'min:1', 'max:4000'],
            'context' => ['nullable', 'array'],
            'context.chapter_id' => ['nullable', 'uuid'],
            'context.client_request_id' => ['nullable', 'string', 'max:100'],
            'context.request_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
