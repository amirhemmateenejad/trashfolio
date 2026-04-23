<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\SnippetController;
use App\Http\Controllers\TagController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [OtpController::class, 'login']);
    Route::post('/verify', [OtpController::class, 'verify']);
});

Route::middleware('auth:sanctum')->group(function () {

    // Projects
    Route::apiResource('projects', ProjectController::class);

    // Folders
    Route::apiResource('folders', FolderController::class)->except(['index']);

    // Snippets
    Route::apiResource('snippets', SnippetController::class);

    // Tags
    Route::apiResource('tags', TagController::class)->only(['index', 'store']);

    // Snippet ↔ Tag attach/detach
    Route::post('snippets/{snippet}/tags/{tag}', [TagController::class, 'attach']);
    Route::delete('snippets/{snippet}/tags/{tag}', [TagController::class, 'detach']);
});
