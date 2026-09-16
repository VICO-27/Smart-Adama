<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required_without:email', 'string'],
            'email' => ['required_without:identifier', 'string'],
            'credential' => ['required_without:password', 'string'],
            'password' => ['required_without:credential', 'string'],
        ];
    }
}
