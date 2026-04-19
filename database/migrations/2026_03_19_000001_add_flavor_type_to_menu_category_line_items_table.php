<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_category_line_items', function (Blueprint $table) {
            $table->string('flavor_type', 32)->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('menu_category_line_items', function (Blueprint $table) {
            $table->dropColumn('flavor_type');
        });
    }
};
