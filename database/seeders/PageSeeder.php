<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $pages = [
            [
                'slug'         => 'ultrafood',
                'title'        => 'ULTRAFOOD',
                'hero_heading' => 'Welcome to UltraFood',
                'hero_subtext' => 'Fresh ingredients, bold flavors.',
                'is_active'    => false,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                // Canonical slug used by routes, blades, and Visual Editor
                'slug'         => 'menu',
                'title'        => 'MENU FOOD',
                'hero_heading' => 'Taste the Difference',
                'hero_subtext' => 'Quality you can feel in every bite.',
                'is_active'    => false,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                // Canonical slug used by routes, blades, and Visual Editor
                'slug'         => 'nordic',
                'title'        => 'KORPALA NORDIC',
                'hero_heading' => 'Food Made with Love',
                'hero_subtext' => 'From our kitchen to your table.',
                'is_active'    => false,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            // Visual Editor: menu category landing (one row per category)
            ['slug' => 'menu_cat_jelly_mixes', 'title' => 'Menu Cat Jelly', 'hero_heading' => '', 'hero_subtext' => '', 'is_active' => false, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'menu_cat_breading_mixes', 'title' => 'Menu Cat Breading', 'hero_heading' => '', 'hero_subtext' => '', 'is_active' => false, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'menu_cat_powder_mixes', 'title' => 'Menu Cat Powder', 'hero_heading' => '', 'hero_subtext' => '', 'is_active' => false, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'menu_cat_bouillon_cubes', 'title' => 'Menu Cat Bouillon', 'hero_heading' => '', 'hero_subtext' => '', 'is_active' => false, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'menu_cat_noodles_pastas', 'title' => 'Menu Cat Noodles', 'hero_heading' => '', 'hero_subtext' => '', 'is_active' => false, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'menu_cat_powdered_drinks', 'title' => 'Menu Cat Drinks', 'hero_heading' => '', 'hero_subtext' => '', 'is_active' => false, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'menu_cat_professional_series', 'title' => 'Menu Cat Pro', 'hero_heading' => '', 'hero_subtext' => '', 'is_active' => false, 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($pages as $page) {
            DB::table('pages')->insertOrIgnore($page);
        }

        // Back-compat: older DBs may have these legacy slugs. Keep them around if present
        // so old foreign keys / references don’t break, but ensure the canonical slugs exist.
        DB::table('pages')->updateOrInsert(
            ['slug' => 'menu-food'],
            ['title' => 'MENU FOOD', 'hero_heading' => 'Taste the Difference', 'hero_subtext' => 'Quality you can feel in every bite.', 'is_active' => false, 'updated_at' => $now, 'created_at' => $now]
        );
        DB::table('pages')->updateOrInsert(
            ['slug' => 'korpala-nordic'],
            ['title' => 'KORPALA NORDIC', 'hero_heading' => 'Food Made with Love', 'hero_subtext' => 'From our kitchen to your table.', 'is_active' => false, 'updated_at' => $now, 'created_at' => $now]
        );
    }
}
