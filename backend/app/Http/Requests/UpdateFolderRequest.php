<?php

namespace App\Http\Requests;

use App\Rules\NoCyclicParent;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $folder = $this->route('folder');

        return [
            'title'     => ['sometimes', 'required', 'string', 'max:255'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:folders,id', new NoCyclicParent($folder)],
        ];
    }
}
