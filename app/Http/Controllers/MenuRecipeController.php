<?php

namespace App\Http\Controllers;

use App\Models\MenuRecipe;
use App\Models\MenuRecipeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MenuRecipeController extends Controller
{
    private const MAX_IMAGE_SIZE_KB = 30720;
    private ?array $menuRecipeColumns = null;

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:menu_recipe_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:'.self::MAX_IMAGE_SIZE_KB,
            'description' => 'nullable|string',
            'servings' => 'nullable|string|max:255',
            'difficulty' => 'nullable|string|max:255',
            'calories' => 'nullable|string|max:255',
            'ingredients' => 'nullable',
            'procedures' => 'nullable',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $categoryId = (int) $data['category_id'];
        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug($data['name']);
        } else {
            $slug = Str::slug($slug);
        }

        // Ensure uniqueness within category (soft-suffix strategy).
        $base = $slug;
        $n = 2;
        while (MenuRecipe::where('category_id', $categoryId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n;
            $n++;
        }

        $filename = null;
        if ($request->hasFile('image')) {
            $filename = $request->file('image')->hashName();
            $request->file('image')->move(public_path('images/menu'), $filename);
        }

        $maxOrder = (int) MenuRecipe::where('category_id', $categoryId)->max('sort_order');

        $ingredients = $this->normalizeList($data['ingredients'] ?? null);
        $procedures = $this->normalizeList($data['procedures'] ?? null);

        $recipe = MenuRecipe::create($this->filterMenuRecipeColumns([
            'category_id' => $categoryId,
            'name' => $data['name'],
            'slug' => $slug,
            'image_filename' => $filename,
            'description' => $data['description'] ?? null,
            'servings' => $data['servings'] ?? null,
            'difficulty' => $data['difficulty'] ?? null,
            'calories' => $data['calories'] ?? null,
            'ingredients' => $ingredients,
            'procedures' => $procedures,
            'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : ($maxOrder + 1),
        ]));

        return response()->json([
            'ok' => true,
            'recipe' => $this->asPayload($recipe),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $recipe = MenuRecipe::findOrFail($id);

        $data = $request->validate([
            'category_id' => 'sometimes|exists:menu_recipe_categories,id',
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|nullable|string|max:255',
            'image' => 'nullable|image|max:'.self::MAX_IMAGE_SIZE_KB,
            'description' => 'nullable|string',
            'servings' => 'nullable|string|max:255',
            'difficulty' => 'nullable|string|max:255',
            'calories' => 'nullable|string|max:255',
            'ingredients' => 'nullable',
            'procedures' => 'nullable',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $updates = [];

        if (array_key_exists('category_id', $data)) {
            $updates['category_id'] = (int) $data['category_id'];
        }
        if (array_key_exists('name', $data)) {
            $updates['name'] = $data['name'];
        }
        if (array_key_exists('slug', $data)) {
            $raw = trim((string) ($data['slug'] ?? ''));
            $nextName = $updates['name'] ?? $recipe->name;
            $slug = $raw !== '' ? Str::slug($raw) : Str::slug($nextName);
            // Enforce unique per category (if collision, suffix).
            $base = $slug;
            $n = 2;
            while (
                MenuRecipe::where('category_id', $updates['category_id'] ?? $recipe->category_id)
                    ->where('slug', $slug)
                    ->where('id', '!=', $recipe->id)
                    ->exists()
            ) {
                $slug = $base.'-'.$n;
                $n++;
            }
            $updates['slug'] = $slug;
        }
        if (array_key_exists('sort_order', $data)) {
            $updates['sort_order'] = (int) $data['sort_order'];
        }
        if (array_key_exists('description', $data)) {
            $updates['description'] = $data['description'];
        }
        if (array_key_exists('servings', $data)) {
            $updates['servings'] = $data['servings'];
        }
        if (array_key_exists('difficulty', $data)) {
            $updates['difficulty'] = $data['difficulty'];
        }
        if (array_key_exists('calories', $data)) {
            $updates['calories'] = $data['calories'];
        }
        if (array_key_exists('ingredients', $data)) {
            $updates['ingredients'] = $this->normalizeList($data['ingredients']);
        }
        if (array_key_exists('procedures', $data)) {
            $updates['procedures'] = $this->normalizeList($data['procedures']);
        }
        if ($request->hasFile('image')) {
            if ($recipe->image_filename && is_file(public_path('images/menu/'.$recipe->image_filename))) {
                @unlink(public_path('images/menu/'.$recipe->image_filename));
            }
            $updates['image_filename'] = $request->file('image')->hashName();
            $request->file('image')->move(public_path('images/menu'), $updates['image_filename']);
        }

        $recipe->fill($this->filterMenuRecipeColumns($updates));
        $recipe->save();

        return response()->json([
            'ok' => true,
            'recipe' => $this->asPayload($recipe),
        ]);
    }

    public function destroy(int $id)
    {
        $recipe = MenuRecipe::findOrFail($id);

        if ($recipe->image_filename && is_file(public_path('images/menu/'.$recipe->image_filename))) {
            @unlink(public_path('images/menu/'.$recipe->image_filename));
        }

        $recipe->delete();

        return response()->json(['ok' => true]);
    }

    private function normalizeList($raw): array
    {
        if (is_array($raw)) {
            return collect($raw)->map(fn ($v) => trim((string) $v))->filter()->values()->all();
        }
        $s = trim((string) ($raw ?? ''));
        if ($s === '') {
            return [];
        }
        // Allow textarea input: one item per line.
        return collect(preg_split("/\\r\\n|\\r|\\n/", $s))
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values()
            ->all();
    }

    private function asPayload(MenuRecipe $recipe): array
    {
        return [
            'id' => $recipe->id,
            'category_id' => $recipe->category_id,
            'name' => $recipe->name,
            'slug' => $recipe->slug,
            'image_filename' => $recipe->image_filename,
            'image_url' => $recipe->imageUrl(),
            'description' => $recipe->description,
            'servings' => $recipe->servings,
            'difficulty' => $this->menuRecipeHasColumn('difficulty') ? $recipe->difficulty : null,
            'calories' => $this->menuRecipeHasColumn('calories') ? $recipe->calories : null,
            'ingredients' => $this->menuRecipeHasColumn('ingredients') ? ($recipe->ingredients ?? []) : [],
            'procedures' => $this->menuRecipeHasColumn('procedures') ? ($recipe->procedures ?? []) : [],
            'sort_order' => $recipe->sort_order,
        ];
    }

    private function filterMenuRecipeColumns(array $payload): array
    {
        $columns = $this->getMenuRecipeColumns();

        return array_filter(
            $payload,
            static fn ($value, $key) => in_array($key, $columns, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function menuRecipeHasColumn(string $column): bool
    {
        return in_array($column, $this->getMenuRecipeColumns(), true);
    }

    private function getMenuRecipeColumns(): array
    {
        if (is_array($this->menuRecipeColumns)) {
            return $this->menuRecipeColumns;
        }

        $this->menuRecipeColumns = Schema::getColumnListing('menu_recipes');
        return $this->menuRecipeColumns;
    }
}
