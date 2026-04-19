<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoMenuContentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ($this->productItems() as $index => $item) {
            $existingId = DB::table('menu_category_line_items')
                ->where('category_slug', $item['category_slug'])
                ->where('title', $item['title'])
                ->value('id');

            $payload = [
                'category_slug' => $item['category_slug'],
                'title' => $item['title'],
                'flavor_type' => $item['flavor_type'] ?? null,
                'price' => $item['price'],
                'image_filename' => $item['image_filename'],
                'back_image_filename' => null,
                'sort_order' => $index + 1,
                'updated_at' => $now,
            ];

            if ($existingId) {
                DB::table('menu_category_line_items')->where('id', $existingId)->update($payload);
                continue;
            }

            DB::table('menu_category_line_items')->insert($payload + ['created_at' => $now]);
        }

        $categoryIds = DB::table('menu_recipe_categories')->pluck('id', 'slug');

        foreach ($this->recipes() as $index => $recipe) {
            $categoryId = $categoryIds[$recipe['category_slug']] ?? null;
            if (! $categoryId) {
                continue;
            }

            $slug = Str::slug($recipe['slug'] ?? $recipe['name']);

            DB::table('menu_recipes')->updateOrInsert(
                ['category_id' => $categoryId, 'slug' => $slug],
                [
                    'name' => $recipe['name'],
                    'image_filename' => $recipe['image_filename'],
                    'description' => $recipe['description'],
                    'servings' => $recipe['servings'],
                    'difficulty' => $recipe['difficulty'],
                    'calories' => $recipe['calories'],
                    'ingredients' => json_encode($recipe['ingredients']),
                    'procedures' => json_encode($recipe['procedures']),
                    'sort_order' => $index + 1,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    private function productItems(): array
    {
        return [
            ['category_slug' => 'jelly-mixes', 'title' => 'Mango Jelly Mix 90 g', 'flavor_type' => 'flavored', 'price' => 'Sample pack', 'image_filename' => 'UFDI-Welcome.png.png'],
            ['category_slug' => 'breading-mixes', 'title' => 'Crispy Breading Mix 200 g', 'price' => 'Sample pack', 'image_filename' => 'UFDI-Our-Brands-Banner.png'],
            ['category_slug' => 'powder-mixes', 'title' => 'All Purpose Powder Mix 250 g', 'price' => 'Sample pack', 'image_filename' => 'nordic-ve-product-1.png'],
            ['category_slug' => 'bouillon-cubes', 'title' => 'Chicken Broth Cubes 10 g', 'price' => 'Sample pack', 'image_filename' => 'nordic-ve-product-2.png'],
            ['category_slug' => 'noodles-and-pastas', 'title' => 'Mamma Mia Spaghetti 400 g', 'price' => 'Sample pack', 'image_filename' => 'nordic-ve-product-3.png'],
            ['category_slug' => 'powdered-drinks', 'title' => 'Iced Tea Powdered Drink 250 g', 'price' => 'Sample pack', 'image_filename' => 'UFDI-Accreditations-Banner.png'],
            ['category_slug' => 'professional-series', 'title' => 'Kitchen Series Mix 1 kg', 'price' => 'Foodservice pack', 'image_filename' => 'UFDI-Customer-and-Clients-Map.png'],
        ];
    }

    private function recipes(): array
    {
        return [
            [
                'category_slug' => 'appetizers',
                'name' => 'Crispy Party Bites',
                'slug' => 'crispy-party-bites',
                'image_filename' => 'Appetizers.png',
                'description' => 'Golden bite-sized appetizers made for quick sharing.',
                'servings' => '4 servings',
                'difficulty' => 'Easy',
                'calories' => '220 kcal',
                'ingredients' => ['1 pack breading mix', '300 g chicken strips', '1 egg', 'Cooking oil'],
                'procedures' => ['Coat chicken with egg.', 'Dredge in breading mix.', 'Fry until crisp and golden.', 'Serve with dip.'],
            ],
            [
                'category_slug' => 'soups',
                'name' => 'Comfort Broth Soup',
                'slug' => 'comfort-broth-soup',
                'image_filename' => 'Soups.png',
                'description' => 'A simple warm soup with savory broth and vegetables.',
                'servings' => '3 servings',
                'difficulty' => 'Easy',
                'calories' => '180 kcal',
                'ingredients' => ['2 broth cubes', '3 cups water', 'Mixed vegetables', 'Pepper'],
                'procedures' => ['Boil water.', 'Dissolve broth cubes.', 'Add vegetables and simmer.', 'Season and serve warm.'],
            ],
            [
                'category_slug' => 'main-dishes',
                'name' => 'Homestyle Saucy Chicken',
                'slug' => 'homestyle-saucy-chicken',
                'image_filename' => 'Main-Dishes.png',
                'description' => 'Tender chicken in a rich everyday sauce.',
                'servings' => '5 servings',
                'difficulty' => 'Medium',
                'calories' => '420 kcal',
                'ingredients' => ['500 g chicken', 'Powder mix', 'Garlic', 'Onion', 'Water'],
                'procedures' => ['Saute garlic and onion.', 'Brown chicken pieces.', 'Add sauce mixture.', 'Simmer until tender.'],
            ],
            [
                'category_slug' => 'noodles-and-pastas',
                'name' => 'Merienda Spaghetti',
                'slug' => 'merienda-spaghetti',
                'image_filename' => 'Noodles-and-Pastas.png',
                'description' => 'A sweet-savory pasta plate for family merienda.',
                'servings' => '4 servings',
                'difficulty' => 'Easy',
                'calories' => '390 kcal',
                'ingredients' => ['400 g spaghetti', 'Pasta sauce', 'Cheese', 'Ground meat'],
                'procedures' => ['Cook pasta until tender.', 'Prepare sauce with meat.', 'Toss pasta with sauce.', 'Top with cheese.'],
            ],
            [
                'category_slug' => 'drinks',
                'name' => 'Chilled Fruit Drink',
                'slug' => 'chilled-fruit-drink',
                'image_filename' => 'Drinks.png',
                'description' => 'A refreshing powdered drink for hot afternoons.',
                'servings' => '1 pitcher',
                'difficulty' => 'Easy',
                'calories' => '120 kcal',
                'ingredients' => ['Powdered drink mix', 'Cold water', 'Ice', 'Fruit slices'],
                'procedures' => ['Pour mix into cold water.', 'Stir until dissolved.', 'Add ice.', 'Garnish with fruit slices.'],
            ],
            [
                'category_slug' => 'desserts',
                'name' => 'Layered Jelly Cups',
                'slug' => 'layered-jelly-cups',
                'image_filename' => 'Desserts.png',
                'description' => 'Colorful jelly cups for simple party desserts.',
                'servings' => '6 cups',
                'difficulty' => 'Easy',
                'calories' => '160 kcal',
                'ingredients' => ['Jelly mix', 'Water', 'Milk layer', 'Fruit toppings'],
                'procedures' => ['Prepare jelly according to pack.', 'Pour first layer into cups.', 'Chill until set.', 'Add toppings before serving.'],
            ],
        ];
    }
}
