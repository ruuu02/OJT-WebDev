<?php

namespace App\Http\Controllers;

use App\Models\NordicRecipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NordicRecipeController extends Controller
{
    private const MAX_IMAGE_SIZE_KB = 30720;
    private ?array $nordicRecipeColumns = null;

    private function ensureDir(): void
    {
        $dir = public_path('images/nordic/recipes');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:'.self::MAX_IMAGE_SIZE_KB,
            'description' => 'nullable|string',
            'servings' => 'nullable|string|max:255',
            'calories' => 'nullable|string|max:255',
            'ingredients' => 'nullable',
            'procedures' => 'nullable',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $slug = trim((string) ($data['slug'] ?? ''));
        $slug = $slug === '' ? Str::slug($data['name']) : Str::slug($slug);

        $base = $slug;
        $n = 2;
        while (NordicRecipe::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n;
            $n++;
        }

        $filename = null;
        if ($request->hasFile('image')) {
            $this->ensureDir();
            $filename = $request->file('image')->hashName();
            $request->file('image')->move(public_path('images/nordic/recipes'), $filename);
        }

        $maxOrder = (int) NordicRecipe::max('sort_order');

        $recipe = NordicRecipe::create($this->filterNordicRecipeColumns([
            'name' => $data['name'],
            'slug' => $slug,
            'image_filename' => $filename,
            'description' => $data['description'] ?? null,
            'servings' => $data['servings'] ?? null,
            'calories' => $data['calories'] ?? null,
            'ingredients' => $this->normalizeList($data['ingredients'] ?? null),
            'procedures' => $this->normalizeList($data['procedures'] ?? null),
            'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : ($maxOrder + 1),
        ]));

        return response()->json([
            'ok' => true,
            'recipe' => $this->asPayload($recipe),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $recipe = NordicRecipe::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|nullable|string|max:255',
            'image' => 'nullable|image|max:'.self::MAX_IMAGE_SIZE_KB,
            'description' => 'nullable|string',
            'servings' => 'nullable|string|max:255',
            'calories' => 'nullable|string|max:255',
            'ingredients' => 'nullable',
            'procedures' => 'nullable',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $updates = [];

        if (array_key_exists('name', $data)) {
            $updates['name'] = $data['name'];
        }
        if (array_key_exists('slug', $data)) {
            $raw = trim((string) ($data['slug'] ?? ''));
            $nextName = $updates['name'] ?? $recipe->name;
            $slug = $raw !== '' ? Str::slug($raw) : Str::slug($nextName);
            $base = $slug;
            $n = 2;
            while (
                NordicRecipe::where('slug', $slug)
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
            $this->ensureDir();
            if ($recipe->image_filename && is_file(public_path('images/nordic/recipes/'.$recipe->image_filename))) {
                @unlink(public_path('images/nordic/recipes/'.$recipe->image_filename));
            }
            $updates['image_filename'] = $request->file('image')->hashName();
            $request->file('image')->move(public_path('images/nordic/recipes'), $updates['image_filename']);
        }

        $recipe->fill($this->filterNordicRecipeColumns($updates));
        $recipe->save();

        return response()->json([
            'ok' => true,
            'recipe' => $this->asPayload($recipe),
        ]);
    }

    public function destroy(int $id)
    {
        $recipe = NordicRecipe::findOrFail($id);

        if ($recipe->image_filename && is_file(public_path('images/nordic/recipes/'.$recipe->image_filename))) {
            @unlink(public_path('images/nordic/recipes/'.$recipe->image_filename));
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
        return collect(preg_split("/\\r\\n|\\r|\\n/", $s))
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values()
            ->all();
    }

    private function asPayload(NordicRecipe $recipe): array
    {
        return [
            'id' => $recipe->id,
            'name' => $recipe->name,
            'slug' => $recipe->slug,
            'image_filename' => $recipe->image_filename,
            'image_url' => $recipe->imageUrl(),
            'description' => $recipe->description,
            'servings' => $recipe->servings,
            'calories' => $this->nordicRecipeHasColumn('calories') ? $recipe->calories : null,
            'ingredients' => $this->nordicRecipeHasColumn('ingredients') ? ($recipe->ingredients ?? []) : [],
            'procedures' => $this->nordicRecipeHasColumn('procedures') ? ($recipe->procedures ?? []) : [],
            'sort_order' => $recipe->sort_order,
        ];
    }

    private function filterNordicRecipeColumns(array $payload): array
    {
        $columns = $this->getNordicRecipeColumns();

        return array_filter(
            $payload,
            static fn ($value, $key) => in_array($key, $columns, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function nordicRecipeHasColumn(string $column): bool
    {
        return in_array($column, $this->getNordicRecipeColumns(), true);
    }

    private function getNordicRecipeColumns(): array
    {
        if (is_array($this->nordicRecipeColumns)) {
            return $this->nordicRecipeColumns;
        }

        $this->nordicRecipeColumns = Schema::getColumnListing('nordic_recipes');

        return $this->nordicRecipeColumns;
    }
}
