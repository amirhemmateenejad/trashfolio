<?php

namespace App\Http\Requests;

use App\Rules\BelongsToProject;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSnippetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $snippet = $this->route('snippet');

        return [
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'content'     => ['sometimes', 'required', 'string'],
            'language'    => ['nullable', 'string', 'max:50'],
            'folder_id'   => ['sometimes', 'nullable', 'integer', 'exists:folders,id', new BelongsToProject($snippet->project_id)],
            'tag_ids'     => ['nullable', 'array'],
            'tag_ids.*'   => ['integer', 'exists:tags,id'],
            'tag_names'   => ['nullable', 'array'],
            'tag_names.*' => ['string', 'max:50'],
        ];
    }
}
