<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            PageSeeder::class,
            MenuCategoryCalloutSeeder::class,
            ExistingResourcesSeeder::class,
            DemoMenuContentSeeder::class,
            NordicRecipesSeeder::class,
        ]);
    }
}
