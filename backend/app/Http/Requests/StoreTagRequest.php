<?php

namespace App\Http\Requests;

use App\Rules\HexColor;
use App\Rules\UniqueTagName;
use Illuminate\Foundation\Http\FormRequest;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:50', new UniqueTagName($this->user())],
            'color' => ['nullable', 'string', new HexColor()],
        ];
    }
}
