<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('slug')->after('name');
            $table->string('color', 7)->nullable()->after('slug');

            $table->dropUnique(['name']);
            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'slug']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'slug', 'color']);
            $table->unique('name');
        });
    }
};
