<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(title: 'Trashfolio API', version: '1.0.0', description: 'Personal developer snippet manager API')]
#[OA\SecurityScheme(securityScheme: 'bearerAuth', type: 'http', scheme: 'bearer', bearerFormat: 'JWT')]
#[OA\Server(url: '/api', description: 'API server')]
#[OA\Tag(name: 'Auth', description: 'Authentication via OTP')]
#[OA\Tag(name: 'User', description: 'Authenticated user profile')]
#[OA\Tag(name: 'Projects', description: 'Project management')]
#[OA\Tag(name: 'Folders', description: 'Folder management')]
#[OA\Tag(name: 'Snippets', description: 'Code snippet management')]
#[OA\Tag(name: 'Tags', description: 'Tag management')]
#[OA\Tag(name: 'Search', description: 'Full-text search')]
#[OA\Tag(name: 'Autocomplete', description: 'Type-ahead suggestions')]
#[OA\Tag(name: 'Trash', description: 'Soft-deleted item management')]
abstract class Controller
{
    //
}
