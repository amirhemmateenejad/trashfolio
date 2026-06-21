<?php

namespace App\Http\Requests\Auth;

use App\Rules\IranianMobile;
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
            'mobile' => ['required', 'string', 'max:11', new IranianMobile()],
        ];
    }
}
