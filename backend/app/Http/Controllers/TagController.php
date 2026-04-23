<?php

namespace App\Http\Controllers;

use App\Models\Snippet;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Tag::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        request()->validate([
            'name' => 'required|string|max:50|unique:tags,name',
        ]);

        return Tag::create(request()->only('name'));
    }

    public function attach(Snippet $snippet, Tag $tag)
    {
        $snippet->tags()->syncWithoutDetaching([$tag->id]);
        return $snippet->load('tags');
    }

    public function detach(Snippet $snippet, Tag $tag)
    {
        $snippet->tags()->detach($tag->id);
        return $snippet->load('tags');
    }
}
