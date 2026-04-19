<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds callout title/subtitle for each menu_cat_* Visual Editor page so
 * the text modal and ALL_CONTENTS are never empty on first edit.
 */
class MenuCategoryCalloutSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'menu_cat_jelly_mixes'         => 'Jelly Mixes',
            'menu_cat_breading_mixes'      => 'Breading Mixes',
            'menu_cat_powder_mixes'        => 'Powder Mixes',
            'menu_cat_bouillon_cubes'      => 'Bouillon Cubes',
            'menu_cat_noodles_pastas'      => 'Noodles and Pastas',
            'menu_cat_powdered_drinks'     => 'Powdered Drinks',
            'menu_cat_professional_series' => 'Professional Series',
        ];

        foreach ($pages as $slug => $title) {
            $pageId = DB::table('pages')->where('slug', $slug)->value('id');
            if (! $pageId) {
                continue;
            }
            $now = now();
            $titleKey = $slug.'_callout_title';
            $titleRow = DB::table('text_content')->where('page_id', $pageId)->where('key', $titleKey)->first();
            if (! $titleRow || trim((string) $titleRow->value) === '') {
                DB::table('text_content')->updateOrInsert(
                    ['page_id' => $pageId, 'key' => $titleKey],
                    ['value' => $title, 'created_at' => $now, 'updated_at' => $now]
                );
            }
            DB::table('text_content')
                ->where('page_id', $pageId)
                ->where('key', $slug.'_callout_sub')
                ->delete();
        }
    }
}
