<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    /**
     * Return the authenticated user's profile.
     */
    #[OA\Get(
        path: '/user',
        summary: 'Get authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['User'],
        responses: [
            new OA\Response(response: 200, description: 'User profile'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function show(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Update the authenticated user's profile.
     */
    #[OA\Put(
        path: '/user',
        summary: 'Update authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['User'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [new OA\Property(property: 'name', type: 'string')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated user profile'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());

        return new UserResource($user);
    }
}
