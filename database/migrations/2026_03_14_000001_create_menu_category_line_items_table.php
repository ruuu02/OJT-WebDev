<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_category_line_items', function (Blueprint $table) {
            $table->id();
            $table->string('category_slug', 64)->index();
            $table->string('title');
            $table->string('price', 32)->nullable();
            $table->string('image_filename', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_category_line_items');
    }
};
