<?php

namespace App\Http\Requests;

use App\Rules\HexColor;
use App\Rules\UniqueTagName;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tag = $this->route('tag');

        return [
            'name'  => ['sometimes', 'required', 'string', 'max:50', new UniqueTagName($this->user(), $tag->id)],
            'color' => ['nullable', 'string', new HexColor()],
        ];
    }
}
