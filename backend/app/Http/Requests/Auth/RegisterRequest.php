<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'min:2', 'max:100'],
            'email'        => ['nullable', 'required_without:phone_number', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'required_without:email', 'string', 'max:50'],
            
            // For alternative email flow
            'password'     => ['nullable', 'required_without:phone_number', 'string', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/'],
            
            // For primary phone flow
            'pin'          => ['nullable', 'required_with:phone_number', 'digits:6', 'confirmed', 'not_in:000000,111111,222222,333333,444444,555555,666666,777777,888888,999999,123456,654321,987654'],
            'pin_confirmation' => ['nullable', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'The password must contain at least one letter and one number.',
            'pin.digits'     => 'The PIN must be exactly 6 digits.',
            'pin.confirmed'  => 'The PIN confirmation does not match.',
            'pin.not_in'     => 'This PIN is too common. Please choose a more secure PIN.',
        ];
    }
}
