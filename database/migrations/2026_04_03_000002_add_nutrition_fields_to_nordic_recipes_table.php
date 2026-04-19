<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nordic_recipes', function (Blueprint $table) {
            if (!Schema::hasColumn('nordic_recipes', 'protein')) {
                $table->string('protein')->nullable()->after('calories');
            }
            if (!Schema::hasColumn('nordic_recipes', 'carbs')) {
                $table->string('carbs')->nullable()->after('protein');
            }
            if (!Schema::hasColumn('nordic_recipes', 'fat')) {
                $table->string('fat')->nullable()->after('carbs');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nordic_recipes', function (Blueprint $table) {
            $drops = [];
            foreach (['protein', 'carbs', 'fat'] as $column) {
                if (Schema::hasColumn('nordic_recipes', $column)) {
                    $drops[] = $column;
                }
            }
            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};
