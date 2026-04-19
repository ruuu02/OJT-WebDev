<?php

namespace App\Http\Controllers;

use App\Models\MenuCategoryLineItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class MenuCategoryItemController extends Controller
{
    private const JELLY_FLAVOR_TYPES = ['flavored', 'unflavored'];
    private const MAX_IMAGE_SIZE_KB = 30720;
    private ?array $menuCategoryItemColumns = null;

    private function ensureDir(): void
    {
        $dir = public_path('images/banner');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'title'         => 'required|string|max:255',
            'flavor_type'   => ['nullable', 'string', Rule::in(self::JELLY_FLAVOR_TYPES)],
            'image'         => 'nullable|image|max:'.self::MAX_IMAGE_SIZE_KB,
            'back_image'    => 'nullable|image|max:'.self::MAX_IMAGE_SIZE_KB,
        ]);

        $data['category_slug'] = Str::slug($data['category_slug']);

        $data['flavor_type'] = $this->normalizeFlavorType($data['category_slug'], $data['flavor_type'] ?? null);

        $maxOrder = (int) MenuCategoryLineItem::where('category_slug', $data['category_slug'])->max('sort_order');

        $filename = null;
        if ($request->hasFile('image')) {
            $this->ensureDir();
            $filename = $request->file('image')->hashName();
            $request->file('image')->move(public_path('images/banner'), $filename);
        }

        $backFilename = null;
        if ($request->hasFile('back_image')) {
            $this->ensureDir();
            $backFilename = $request->file('back_image')->hashName();
            $request->file('back_image')->move(public_path('images/banner'), $backFilename);
        }

        $item = MenuCategoryLineItem::create($this->filterMenuCategoryItemColumns([
            'category_slug'   => $data['category_slug'],
            'title'           => $data['title'],
            'flavor_type'     => $data['flavor_type'],
            'price'           => null,
            'image_filename'  => $filename,
            'back_image_filename' => $backFilename,
            'sort_order'      => $maxOrder + 1,
        ]));

        return response()->json([
            'ok'   => true,
            'item' => [
                'id'              => $item->id,
                'category_slug'   => $item->category_slug,
                'title'           => $item->title,
                'flavor_type'     => $item->flavor_type,
                'image_filename'  => $item->image_filename,
                'back_image_filename' => $item->back_image_filename,
                'image_url'       => $item->imageUrl(),
                'back_image_url'  => $item->backImageUrl(),
                'sort_order'      => $item->sort_order,
            ],
        ]);
    }

    public function update(Request $request, int $id)
    {
        $item = MenuCategoryLineItem::findOrFail($id);
        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'flavor_type' => ['nullable', 'string', Rule::in(self::JELLY_FLAVOR_TYPES)],
            'image'       => 'nullable|image|max:'.self::MAX_IMAGE_SIZE_KB,
            'back_image'  => 'nullable|image|max:'.self::MAX_IMAGE_SIZE_KB,
        ]);

        $updates = [];

        if (array_key_exists('title', $data)) {
            $updates['title'] = $data['title'];
        }
        if (array_key_exists('flavor_type', $data)) {
            $updates['flavor_type'] = $this->normalizeFlavorType($item->category_slug, $data['flavor_type']);
        }

        $oldImageFilename = null;
        $oldBackImageFilename = null;

        try {
            if ($request->hasFile('image')) {
                $this->ensureDir();
                $updates['image_filename'] = $request->file('image')->hashName();
                $request->file('image')->move(public_path('images/banner'), $updates['image_filename']);
                $oldImageFilename = $item->image_filename;
            }
            if ($request->hasFile('back_image')) {
                $this->ensureDir();
                $updates['back_image_filename'] = $request->file('back_image')->hashName();
                $request->file('back_image')->move(public_path('images/banner'), $updates['back_image_filename']);
                $oldBackImageFilename = $item->back_image_filename;
            }

            $item->fill($this->filterMenuCategoryItemColumns($updates));
            $item->save();
        } catch (Throwable $e) {
            if (! empty($updates['image_filename']) && is_file(public_path('images/banner/'.$updates['image_filename']))) {
                @unlink(public_path('images/banner/'.$updates['image_filename']));
            }
            if (! empty($updates['back_image_filename']) && is_file(public_path('images/banner/'.$updates['back_image_filename']))) {
                @unlink(public_path('images/banner/'.$updates['back_image_filename']));
            }

            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Could not save the product images/details.',
            ], 500);
        }

        if ($oldImageFilename && $oldImageFilename !== $item->image_filename && is_file(public_path('images/banner/'.$oldImageFilename))) {
            @unlink(public_path('images/banner/'.$oldImageFilename));
        }
        if ($oldBackImageFilename && $oldBackImageFilename !== $item->back_image_filename && is_file(public_path('images/banner/'.$oldBackImageFilename))) {
            @unlink(public_path('images/banner/'.$oldBackImageFilename));
        }

        return response()->json([
            'ok'   => true,
            'item' => [
                'id'             => $item->id,
                'title'          => $item->title,
                'flavor_type'    => $item->flavor_type,
                'image_filename' => $item->image_filename,
                'back_image_filename' => $item->back_image_filename,
                'image_url'      => $item->imageUrl(),
                'back_image_url' => $item->backImageUrl(),
            ],
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $item = MenuCategoryLineItem::findOrFail($id);
        if ($item->image_filename && is_file(public_path('images/banner/'.$item->image_filename))) {
            @unlink(public_path('images/banner/'.$item->image_filename));
        }
        if ($item->back_image_filename && is_file(public_path('images/banner/'.$item->back_image_filename))) {
            @unlink(public_path('images/banner/'.$item->back_image_filename));
        }
        $base = 'mcli_'.$id.'_name';
        $menuItemBase = 'menu_item_'.$id.'_';
        $keys = [
            $base, $base.'_font', $base.'_font_size', $base.'_color',
            $menuItemBase.'description',
            $menuItemBase.'lazada_url',
            $menuItemBase.'shopee_url',
            $menuItemBase.'shopee_mall_url',
            $menuItemBase.'tiktok_url',
        ];
        $q = DB::table('text_content')->whereIn('key', $keys);
        if ($request->filled('page_id')) {
            $pageIds = [(int) $request->page_id];
            $menuPageId = (int) (DB::table('pages')->where('slug', 'menu')->value('id') ?? 0);
            if ($menuPageId > 0) {
                $pageIds[] = $menuPageId;
            }
            $q->whereIn('page_id', array_values(array_unique(array_filter($pageIds))));
        }
        $q->delete();
        $item->delete();

        return response()->json(['ok' => true]);
    }

    public function move(Request $request, int $id)
    {
        $item = MenuCategoryLineItem::findOrFail($id);
        $data = $request->validate([
            'direction' => ['required', 'string', Rule::in(['up', 'down'])],
        ]);

        $siblings = MenuCategoryLineItem::where('category_slug', $item->category_slug)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $moveGroupKey = $this->moveGroupKey($item);
        if ($moveGroupKey !== null) {
            $siblings = $siblings
                ->filter(fn (MenuCategoryLineItem $sibling) => $this->moveGroupKey($sibling) === $moveGroupKey)
                ->values();
        }

        $currentIndex = $siblings->search(fn (MenuCategoryLineItem $sibling) => (int) $sibling->id === (int) $item->id);
        if ($currentIndex === false) {
            return response()->json(['ok' => false, 'message' => 'Product could not be reordered.'], 422);
        }

        $swapIndex = $data['direction'] === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        $swapItem = $siblings->get($swapIndex);

        if (! $swapItem) {
            return response()->json(['ok' => true, 'moved' => false]);
        }

        DB::transaction(function () use ($item, $swapItem) {
            $currentSort = (int) $item->sort_order;
            $swapSort = (int) $swapItem->sort_order;

            $item->sort_order = $swapSort;
            $item->save();

            MenuCategoryLineItem::whereKey($swapItem->id)->update([
                'sort_order' => $currentSort,
            ]);
        });

        return response()->json([
            'ok' => true,
            'moved' => true,
        ]);
    }

    private function normalizeFlavorType(string $categorySlug, ?string $flavorType): ?string
    {
        if ($categorySlug !== 'jelly-mixes') {
            return null;
        }

        $value = strtolower(trim((string) $flavorType));

        return in_array($value, self::JELLY_FLAVOR_TYPES, true) ? $value : 'unflavored';
    }

    private function moveGroupKey(MenuCategoryLineItem $item): ?string
    {
        if ($item->category_slug === 'jelly-mixes') {
            return 'jelly:' . $this->normalizeFlavorType($item->category_slug, $item->flavor_type);
        }

        if ($item->category_slug === 'noodles-and-pastas') {
            $title = strtolower(trim((string) $item->title));

            if (str_contains($title, 'mamma mia')) {
                return 'noodles:mamma-mia';
            }

            if (str_contains($title, 'vermicelli')) {
                return 'noodles:vermicelli';
            }
        }

        return null;
    }

    private function filterMenuCategoryItemColumns(array $payload): array
    {
        $columns = $this->getMenuCategoryItemColumns();

        return array_filter(
            $payload,
            static fn ($value, $key) => in_array($key, $columns, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function getMenuCategoryItemColumns(): array
    {
        if ($this->menuCategoryItemColumns === null) {
            $this->menuCategoryItemColumns = Schema::getColumnListing('menu_category_line_items');
        }

        return $this->menuCategoryItemColumns;
    }
}
