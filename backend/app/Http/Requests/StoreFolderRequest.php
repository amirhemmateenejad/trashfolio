<?php

namespace App\Http\Requests;

use App\Rules\BelongsToProject;
use Illuminate\Foundation\Http\FormRequest;

class StoreFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'parent_id'  => ['nullable', 'integer', 'exists:folders,id', new BelongsToProject($this->integer('project_id'))],
            'title'      => ['required', 'string', 'max:255'],
        ];
    }
}
