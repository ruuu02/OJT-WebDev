<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('menu_recipe_categories')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('image_filename')->nullable();
            $table->text('description')->nullable();
            $table->string('servings')->nullable();
            $table->string('calories')->nullable();
            $table->json('ingredients')->nullable();
            $table->json('procedures')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['category_id', 'slug']);
            $table->index(['category_id', 'sort_order', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_recipes');
    }
};

