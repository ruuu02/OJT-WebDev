<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_category_line_items', function (Blueprint $table) {
            $table->string('back_image_filename', 255)->nullable()->after('image_filename');
        });
    }

    public function down(): void
    {
        Schema::table('menu_category_line_items', function (Blueprint $table) {
            $table->dropColumn('back_image_filename');
        });
    }
};
