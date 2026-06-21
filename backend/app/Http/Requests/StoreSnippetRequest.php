<?php

namespace App\Http\Requests;

use App\Rules\BelongsToProject;
use Illuminate\Foundation\Http\FormRequest;

class StoreSnippetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'  => ['required', 'integer', 'exists:projects,id'],
            'folder_id'   => ['nullable', 'integer', 'exists:folders,id', new BelongsToProject($this->integer('project_id'))],
            'title'       => ['required', 'string', 'max:255'],
            'content'     => ['required', 'string'],
            'language'    => ['nullable', 'string', 'max:50'],
            'tag_ids'     => ['nullable', 'array'],
            'tag_ids.*'   => ['integer', 'exists:tags,id'],
            'tag_names'   => ['nullable', 'array'],
            'tag_names.*' => ['string', 'max:50'],
        ];
    }
}
