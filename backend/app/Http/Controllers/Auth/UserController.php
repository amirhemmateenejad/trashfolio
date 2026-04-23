<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function show()
    {
        return auth()->user();
    }

    public function update()
    {
        request()->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $user->update(['name' => request('name')]);

        return $user;
    }
}
