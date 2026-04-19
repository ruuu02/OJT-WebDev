<?php

namespace App\Http\Controllers;

use App\Models\MenuRecipeCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class MenuRecipeCategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('menu_recipe_categories', 'slug')],
            'icon' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg|max:5120',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug($data['name']);
        }

        $filename = null;
        if ($request->hasFile('icon')) {
            $filename = $request->file('icon')->hashName();
            $request->file('icon')->move(public_path('images/menu'), $filename);
        }

        $maxOrder = (int) MenuRecipeCategory::max('sort_order');

        $cat = MenuRecipeCategory::create([
            'name' => $data['name'],
            'slug' => $slug,
            'icon_filename' => $filename,
            'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : ($maxOrder + 1),
        ]);

        return response()->json([
            'ok' => true,
            'category' => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'icon_filename' => $cat->icon_filename,
                'icon_url' => $cat->iconUrl(),
                'sort_order' => $cat->sort_order,
            ],
        ]);
    }

    public function update(Request $request, int $id)
    {
        $cat = MenuRecipeCategory::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('menu_recipe_categories', 'slug')->ignore($cat->id),
            ],
            'icon' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg|max:5120',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        if (array_key_exists('name', $data)) {
            $cat->name = $data['name'];
        }
        if (array_key_exists('slug', $data)) {
            $slug = trim((string) ($data['slug'] ?? ''));
            $cat->slug = $slug !== '' ? $slug : Str::slug($cat->name);
        }
        if (array_key_exists('sort_order', $data)) {
            $cat->sort_order = (int) $data['sort_order'];
        }
        if ($request->hasFile('icon')) {
            if ($cat->icon_filename && is_file(public_path('images/menu/'.$cat->icon_filename))) {
                @unlink(public_path('images/menu/'.$cat->icon_filename));
            }
            $cat->icon_filename = $request->file('icon')->hashName();
            $request->file('icon')->move(public_path('images/menu'), $cat->icon_filename);
        }

        $cat->save();

        return response()->json([
            'ok' => true,
            'category' => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'icon_filename' => $cat->icon_filename,
                'icon_url' => $cat->iconUrl(),
                'sort_order' => $cat->sort_order,
            ],
        ]);
    }

    public function destroy(int $id)
    {
        $cat = MenuRecipeCategory::findOrFail($id);

        if ($cat->icon_filename && is_file(public_path('images/menu/'.$cat->icon_filename))) {
            @unlink(public_path('images/menu/'.$cat->icon_filename));
        }

        $cat->delete();

        return response()->json(['ok' => true]);
    }
}
