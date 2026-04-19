<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NordicRecipesSeeder extends Seeder
{
    public function run(): void
    {
        // Seed the existing hardcoded catalog as DB rows (idempotent by slug).
        // Images are not downloaded here; admins can upload/replace images via Visual Editor.
        $rows = $this->catalog();

        foreach ($rows as $i => $r) {
            $slug = Str::slug((string) ($r['slug'] ?? $r['name'] ?? 'recipe'));
            if ($slug === '') {
                continue;
            }

            DB::table('nordic_recipes')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => (string) ($r['name'] ?? ''),
                    'image_filename' => null,
                    'description' => $r['description'] ?? null,
                    'servings' => $r['servings'] ?? null,
                    'calories' => $r['calories'] ?? null,
                    'ingredients' => json_encode(array_values($r['ingredients'] ?? [])),
                    'procedures' => json_encode(array_values($r['procedures'] ?? [])),
                    'sort_order' => $i,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function catalog(): array
    {
        return [
            [
                'name' => 'Oat Congee',
                'slug' => 'oat-congee',
                'description' => 'Comforting rice-style oat porridge simmered until creamy with a mild, savory taste.',
                'servings' => '2 servings',
                'calories' => '310 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '3 cups water', '1 tsp minced ginger', '1 tbsp chopped spring onions', 'Salt and pepper to taste'],
                'procedures' => ['Bring water and ginger to a boil.', 'Add oats and cook on low heat for 10-12 minutes.', 'Season with salt and pepper.', 'Top with spring onions and serve hot.'],
            ],
            [
                'name' => 'Oat Soup',
                'slug' => 'oat-soup',
                'description' => 'A warm bowl of blended oats and vegetables for a smooth, hearty soup.',
                'servings' => '3 servings',
                'calories' => '260 kcal / serving',
                'ingredients' => ['3/4 cup Nordic oats', '1 tbsp olive oil', '1 small onion, diced', '2 cups vegetable broth', '1/2 cup milk'],
                'procedures' => ['Saute onion in olive oil until translucent.', 'Add oats and broth, then simmer for 12 minutes.', 'Blend until smooth and return to heat.', 'Stir in milk, season, and serve.'],
            ],
            [
                'name' => 'Oat Fried Rice',
                'slug' => 'oat-fried-rice',
                'description' => 'Savory stir-fried oats inspired by classic fried rice flavors.',
                'servings' => '2 servings',
                'calories' => '340 kcal / serving',
                'ingredients' => ['1 cup cooked Nordic oats', '1 egg, beaten', '1/2 cup mixed vegetables', '1 tbsp soy sauce', '1 clove garlic, minced'],
                'procedures' => ['Scramble egg in a hot pan and set aside.', 'Saute garlic and vegetables for 2-3 minutes.', 'Add cooked oats and soy sauce; stir-fry.', 'Mix in egg and cook for 1 minute more.'],
            ],
            [
                'name' => 'Basic Overnight Oats',
                'slug' => 'basic-overnight-oats',
                'description' => 'No-cook oats soaked overnight for a creamy and ready-to-eat breakfast.',
                'servings' => '1 serving',
                'calories' => '290 kcal / serving',
                'ingredients' => ['1/2 cup Nordic oats', '1/2 cup milk', '1/4 cup yogurt', '1 tsp chia seeds', '1 tsp honey'],
                'procedures' => ['Combine all ingredients in a jar.', 'Mix until fully incorporated.', 'Cover and refrigerate overnight.', 'Stir and serve chilled.'],
            ],
            [
                'name' => 'Banana Peanut Butter Oats',
                'slug' => 'banana-peanut-butter-oats',
                'description' => 'A creamy oat bowl with sweet banana and rich peanut butter.',
                'servings' => '2 servings',
                'calories' => '360 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '2 cups milk', '1 ripe banana, mashed', '1 tbsp peanut butter', '1 tsp honey'],
                'procedures' => ['Cook oats with milk over medium heat.', 'Stir in mashed banana.', 'Add peanut butter and mix until creamy.', 'Drizzle honey and serve warm.'],
            ],
            [
                'name' => 'Apple Cinnamon Oatmeal',
                'slug' => 'apple-cinnamon-oatmeal',
                'description' => 'Cozy oats with fresh apples and cinnamon spice.',
                'servings' => '2 servings',
                'calories' => '320 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '2 cups water or milk', '1 apple, diced', '1 tsp cinnamon', '1 tsp brown sugar'],
                'procedures' => ['Simmer oats with water or milk.', 'Add apple and cinnamon halfway through cooking.', 'Cook until apples soften.', 'Finish with brown sugar and serve.'],
            ],
            [
                'name' => 'Oatmeal Cookies',
                'slug' => 'oatmeal-cookies',
                'description' => 'Soft and chewy cookies made with hearty oats.',
                'servings' => '10 cookies',
                'calories' => '150 kcal / cookie',
                'ingredients' => ['1 cup Nordic oats', '1/2 cup flour', '1/3 cup butter', '1/3 cup sugar', '1 egg'],
                'procedures' => ['Preheat oven to 175C.', 'Mix wet ingredients, then fold in dry ingredients.', 'Scoop dough onto lined tray.', 'Bake 12-15 minutes until golden.'],
            ],
            [
                'name' => 'Oat Brownies',
                'slug' => 'oat-brownies',
                'description' => 'Fudgy brownies with oat texture and chocolate richness.',
                'servings' => '9 squares',
                'calories' => '210 kcal / square',
                'ingredients' => ['1 cup oat flour', '1/3 cup cocoa powder', '1/2 cup sugar', '2 eggs', '1/3 cup melted butter'],
                'procedures' => ['Preheat oven to 175C and line a pan.', 'Combine wet ingredients, then stir in dry ingredients.', 'Spread batter evenly into pan.', 'Bake 20-25 minutes, cool, then slice.'],
            ],
            [
                'name' => 'Oat Pancakes',
                'slug' => 'oat-pancakes',
                'description' => 'Fluffy pancakes with wholesome oat flavor.',
                'servings' => '8 pancakes',
                'calories' => '120 kcal / pancake',
                'ingredients' => ['1 cup oat flour', '1 tbsp sugar', '1 tsp baking powder', '1 egg', '3/4 cup milk'],
                'procedures' => ['Whisk dry ingredients together.', 'Add egg and milk to form batter.', 'Pour small rounds on a hot pan.', 'Flip when bubbles form and cook through.'],
            ],
            [
                'name' => 'Oat Waffles',
                'slug' => 'oat-waffles',
                'description' => 'Crisp-edged waffles with oat flour and light sweetness.',
                'servings' => '4 waffles',
                'calories' => '190 kcal / waffle',
                'ingredients' => ['1 cup oat flour', '1 tsp baking powder', '1 egg', '3/4 cup milk', '1 tbsp melted butter'],
                'procedures' => ['Preheat waffle maker.', 'Mix all ingredients until smooth.', 'Pour batter into waffle maker.', 'Cook until golden and crisp.'],
            ],
            [
                'name' => 'Garlic Mushroom Oats',
                'slug' => 'garlic-mushroom-oats',
                'description' => 'Savory oatmeal with sauteed mushroom and garlic aroma.',
                'servings' => '2 servings',
                'calories' => '300 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '2 cups broth', '1 cup mushrooms, sliced', '2 cloves garlic, minced', '1 tsp olive oil'],
                'procedures' => ['Saute garlic and mushrooms in olive oil.', 'Cook oats in broth until creamy.', 'Fold in sauteed mushroom mixture.', 'Season and serve warm.'],
            ],
            [
                'name' => 'Egg & Spinach Oats',
                'slug' => 'egg-spinach-oats',
                'description' => 'Protein-rich savory oats with egg and spinach.',
                'servings' => '2 servings',
                'calories' => '330 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '2 cups water', '2 eggs', '1 cup spinach', 'Salt and pepper'],
                'procedures' => ['Cook oats in water until thick.', 'Stir in spinach until wilted.', 'Top with poached or fried eggs.', 'Season to taste and serve.'],
            ],
            [
                'name' => 'Chicken Oat Porridge',
                'slug' => 'chicken-oat-porridge',
                'description' => 'A filling savory porridge with shredded chicken and soft oats.',
                'servings' => '3 servings',
                'calories' => '350 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '3 cups chicken broth', '1 cup shredded cooked chicken', '1 tsp garlic powder', 'Chopped scallions'],
                'procedures' => ['Boil broth and add oats.', 'Simmer until thickened.', 'Add shredded chicken and garlic powder.', 'Garnish with scallions and serve hot.'],
            ],
            [
                'name' => 'Tomato Basil Savory Oats',
                'slug' => 'tomato-basil-savory-oats',
                'description' => 'Tangy tomato and basil oats for a savory Mediterranean profile.',
                'servings' => '2 servings',
                'calories' => '290 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '2 cups vegetable broth', '1/2 cup diced tomatoes', '1 tbsp chopped basil', '1 tsp olive oil'],
                'procedures' => ['Heat olive oil and briefly cook tomatoes.', 'Add broth and oats; simmer until creamy.', 'Stir in chopped basil.', 'Adjust seasoning and serve.'],
            ],
            [
                'name' => 'Chocolate Cocoa Oats',
                'slug' => 'chocolate-cocoa-oats',
                'description' => 'Dessert-style breakfast oats with cocoa and chocolate flavor.',
                'servings' => '2 servings',
                'calories' => '340 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '2 cups milk', '1 tbsp cocoa powder', '1 tbsp dark chocolate chips', '1 tsp honey'],
                'procedures' => ['Cook oats with milk over medium heat.', 'Whisk in cocoa powder until smooth.', 'Stir in chocolate chips until melted.', 'Sweeten with honey and serve.'],
            ],
            [
                'name' => 'Mango Coconut Oats',
                'slug' => 'mango-coconut-oats',
                'description' => 'Tropical oats with ripe mango and creamy coconut notes.',
                'servings' => '2 servings',
                'calories' => '330 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '1 cup coconut milk', '1 cup water', '1/2 cup diced mango', '1 tbsp toasted coconut'],
                'procedures' => ['Cook oats with coconut milk and water.', 'Simmer until creamy.', 'Top with mango and toasted coconut.', 'Serve warm or chilled.'],
            ],
            [
                'name' => 'Strawberry Yogurt Oats',
                'slug' => 'strawberry-yogurt-oats',
                'description' => 'Refreshing oat bowl with strawberries and tangy yogurt.',
                'servings' => '2 servings',
                'calories' => '300 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '2 cups milk', '1/2 cup yogurt', '1/2 cup sliced strawberries', '1 tsp honey'],
                'procedures' => ['Cook oats with milk until soft.', 'Cool slightly and fold in yogurt.', 'Top with strawberries.', 'Drizzle honey and serve.'],
            ],
        ];
    }
}

