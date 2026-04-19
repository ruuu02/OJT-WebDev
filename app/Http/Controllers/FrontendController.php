<?php

namespace App\Http\Controllers;

use App\Models\MenuCategoryLineItem;
use App\Models\MenuRecipe;
use App\Models\MenuRecipeCategory;
use App\Models\NordicRecipe;
use App\Models\Page;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FrontendController extends Controller
{
    private const DEFAULT_MENU_CATEGORY_LABELS = [
        'jelly-mixes' => 'Jelly Mixes',
        'breading-mixes' => 'Breading Mixes',
        'powder-mixes' => 'Powder Mixes',
        'bouillon-cubes' => 'Bouillon Cubes',
        'noodles-and-pastas' => 'Noodles and Pastas',
        'powdered-drinks' => 'Powdered Drinks',
        'professional-series' => 'Professional Series',
    ];

    private function normalizeMenuCategorySlug(string $slug): string
    {
        $normalized = Str::slug($slug);

        return match ($normalized) {
            'noodles-pastas-and-sauces' => 'noodles-and-pastas',
            default => $normalized,
        };
    }

    // Serves whichever homepage variant is currently marked active
    public function index()
    {
        $page = Page::where('is_active', true)->firstOrFail();
        return view("frontend.{$page->slug}", compact('page'));
    }

    // Serves any page by slug (for future expansion)
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)->firstOrFail();
        return view("frontend.{$slug}", compact('page'));
    }

    // ── Brand pages ──
    public function menuBrand()
    {
        return view('frontend.menu');
    }

    public function menuProducts()
    {
        $products = collect($this->menuProductCatalog())
            ->map(function (array $product, string $slug) {
                return $product + [
                    'slug' => $slug,
                    'url' => route('menu.products.category', ['category' => $slug]),
                ];
            })
            ->values()
            ->all();

        return view('frontend.menu-product-catalog', ['products' => $products]);
    }

    private function menuCategoryCmsSlug(string $categorySlug): string
    {
        $slug = $this->normalizeMenuCategorySlug($categorySlug);

        if ($slug === 'noodles-and-pastas') {
            return 'menu_cat_noodles_pastas';
        }

        return 'menu_cat_' . str_replace('-', '_', $slug);
    }

    private function extractMenuCategorySlugFromUrl(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $path = parse_url(trim($value), PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        $prefix = '/brands/menu/products/category/';
        if (! str_starts_with($path, $prefix)) {
            return null;
        }

        $slug = trim(substr($path, strlen($prefix)), '/');

        return $slug !== '' ? $this->normalizeMenuCategorySlug(rawurldecode($slug)) : null;
    }

    private function discoverMenuCategoryLabels(): array
    {
        $labels = self::DEFAULT_MENU_CATEGORY_LABELS;

        $menuPageId = DB::table('pages')->where('slug', 'menu')->value('id');
        $navRows = DB::table('text_content')
            ->when($menuPageId, fn ($query) => $query->where('page_id', (int) $menuPageId))
            ->where('key', 'like', 'menu_nav_products_item_%')
            ->get(['key', 'value']);

        $dynamicRows = [];
        foreach ($navRows as $row) {
            $key = (string) ($row->key ?? '');
            if (preg_match('/^menu_nav_products_item_(\d+)_(label|url)$/', $key, $matches) !== 1) {
                continue;
            }

            $index = (int) $matches[1];
            $field = $matches[2];
            $dynamicRows[$index][$field] = (string) ($row->value ?? '');
        }

        foreach ($dynamicRows as $row) {
            $slug = $this->extractMenuCategorySlugFromUrl($row['url'] ?? null);
            if (! $slug) {
                $slug = $this->normalizeMenuCategorySlug((string) ($row['label'] ?? ''));
            }
            if (! $slug) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $labels[$slug] = $label !== '' ? $label : (self::DEFAULT_MENU_CATEGORY_LABELS[$slug] ?? Str::headline(str_replace('-', ' ', $slug)));
        }

        $dbSlugs = MenuCategoryLineItem::query()
            ->select('category_slug')
            ->distinct()
            ->pluck('category_slug')
            ->map(fn ($slug) => $this->normalizeMenuCategorySlug((string) $slug))
            ->filter()
            ->values();

        foreach ($dbSlugs as $slug) {
            $labels[$slug] = $labels[$slug] ?? (self::DEFAULT_MENU_CATEGORY_LABELS[$slug] ?? Str::headline(str_replace('-', ' ', $slug)));
        }

        return $labels;
    }

    private function availableMenuCategorySlugs(): array
    {
        $slugs = array_keys($this->discoverMenuCategoryLabels());
        $catalogSlugs = array_map(fn ($slug) => Str::slug((string) $slug), array_keys($this->menuProductCatalog()));

        return array_values(array_unique(array_filter(array_merge($slugs, $catalogSlugs))));
    }

    private function matchesMenuCategoryItem(MenuCategoryLineItem $item, string $itemToken): bool
    {
        $itemToken = trim($itemToken);
        if ($itemToken === '') {
            return false;
        }

        if (preg_match('/^(\d+)(?:-|$)/', $itemToken, $matches)) {
            return (int) $item->id === (int) ($matches[1] ?? 0);
        }

        return Str::slug((string) $item->title) === Str::slug($itemToken);
    }

    public function menuCategoryLanding(string $category)
    {
        $category = $this->normalizeMenuCategorySlug($category);

        if (! in_array($category, $this->availableMenuCategorySlugs(), true)) {
            abort(404);
        }

        $lineItems = MenuCategoryLineItem::where('category_slug', $category)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $cmsPageSlug = $this->menuCategoryCmsSlug($category);
        $pageId = DB::table('pages')->where('slug', $cmsPageSlug)->value('id');
        $menuPageId = DB::table('pages')->where('slug', 'menu')->value('id');
        $allContents = collect();
        if ($pageId) {
            $allContents = $allContents->merge(
                DB::table('text_content')
                    ->where('page_id', $pageId)
                    ->get()
                    ->map(fn ($r) => ['key' => $r->key, 'value' => $r->value, 'section' => 'text_content'])
            );
        }
        if ($menuPageId && (int) $menuPageId !== (int) $pageId) {
            $allContents = $allContents->merge(
                DB::table('text_content')
                    ->where('page_id', $menuPageId)
                    ->get()
                    ->map(fn ($r) => ['key' => $r->key, 'value' => $r->value, 'section' => 'text_content'])
            );
        }

        $catalog = $this->menuProductCatalog();
        $labels = $this->discoverMenuCategoryLabels();
        $categoryTitle = $labels[$category]
            ?? ($catalog[$category]['title'] ?? null)
            ?? Str::headline(str_replace('-', ' ', $category));

        return view('frontend.menu-category-landing', [
            'categorySlug'   => $category,
            'cmsPageSlug'    => $cmsPageSlug,
            'lineItems'      => $lineItems,
            'isVisualEditor' => false,
            'allContents'    => $allContents,
            'categoryTitle'  => $categoryTitle,
        ]);
    }

    public function menuProductDetails(string $product)
    {
        // Product-detail pages were removed in favor of:
        // - category landing: /brands/menu/products/category/{category}
        // - item details:     /brands/menu/products/{category}/{item}
        $product = $this->normalizeMenuCategorySlug($product);

        if (in_array($product, $this->availableMenuCategorySlugs(), true)) {
            return redirect()->route('menu.products.category', ['category' => $product]);
        }

        abort(404);
    }

    public function menuCategoryItemDetails(string $category, string $item)
    {
        $category = $this->normalizeMenuCategorySlug($category);

        if (! in_array($category, $this->availableMenuCategorySlugs(), true)) {
            abort(404);
        }

        $lineItems = MenuCategoryLineItem::where('category_slug', $category)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $selectedItem = $lineItems->first(fn (MenuCategoryLineItem $it) => $this->matchesMenuCategoryItem($it, $item));
        abort_unless($selectedItem, 404);

        $catalog = $this->menuProductCatalog();
        $labels = $this->discoverMenuCategoryLabels();
        $categoryTitle = $labels[$category]
            ?? ($catalog[$category]['title'] ?? null)
            ?? Str::headline(str_replace('-', ' ', $category));

        return view('frontend.menu-category-item-detail', [
            'categorySlug' => $category,
            'categoryTitle' => $categoryTitle,
            'item' => $selectedItem,
        ]);
    }

    public function menuRecipelist()
    {
        return redirect()->route('menu');
    }

    public function menuCategory(string $slug)
    {
        // Back-compat: legacy route now redirects to the DB-driven category route.
        $cat = MenuRecipeCategory::where('slug', $slug)->first();
        abort_unless($cat, 404);
        return redirect()->route('menu.recipes.category', ['category' => $cat->slug]);
    }

    public function menuRecipeCategory(string $category)
    {
        $cat = MenuRecipeCategory::where('slug', $category)->first();
        abort_unless($cat, 404);

        $recipes = MenuRecipe::where('category_id', $cat->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('frontend.menu-recipe-category', [
            'category' => $cat,
            'recipes' => $recipes,
            'isVisualEditor' => false,
        ]);
    }

    public function menuRecipeDetail(string $category, string $recipe)
    {
        $cat = MenuRecipeCategory::where('slug', $category)->first();
        abort_unless($cat, 404);

        $rec = MenuRecipe::where('category_id', $cat->id)->where('slug', $recipe)->first();
        abort_unless($rec, 404);

        $menuProductLinkMap = MenuCategoryLineItem::query()
            ->orderBy('category_slug')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'category_slug', 'title', 'price', 'image_filename'])
            ->mapWithKeys(function (MenuCategoryLineItem $item) {
                $id = (int) $item->id;
                return [$id => [
                    'id' => $id,
                    'category_slug' => Str::slug((string) $item->category_slug),
                    'title' => trim((string) $item->title),
                    'price' => trim((string) ($item->price ?? '')),
                    'image' => $item->imageUrl(),
                ]];
            })
            ->all();

        return view('frontend.menu-recipe-detail', [
            'category' => $cat,
            'recipe' => $rec,
            'menuProductLinkMap' => $menuProductLinkMap,
            'isVisualEditor' => false,
        ]);
    }

    public function menuRecipeDetailsPdf(string $category, string $recipe)
    {
        $cat = MenuRecipeCategory::where('slug', $category)->first();
        abort_unless($cat, 404);

        $rec = MenuRecipe::where('category_id', $cat->id)->where('slug', $recipe)->first();
        abort_unless($rec, 404);

        $safeName = Str::slug($rec->name ?: 'menu-recipe');
        $facadeClass = \Barryvdh\DomPDF\Facade\Pdf::class;

        if (class_exists($facadeClass)) {
            try {
                $pdf = $facadeClass::loadView('pdf.menu-recipe-details', [
                    'recipe' => $rec,
                    'category' => $cat,
                ])->setPaper('a4', 'portrait');

                return $pdf->download($safeName . '.pdf');
            } catch (\Throwable) {
                // Fall through to lightweight PDF fallback.
            }
        }

        $fallbackPdf = $this->buildSimpleRecipePdf($rec, 'Menu Food Recipe');

        return response($fallbackPdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $safeName . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function nordicBrand()
    {
        return view('frontend.nordic');
    }

    public function nordicProducts()
    {
        return view('frontend.nordic-products');
    }

    public function nordicProductDetails(string $product)
    {
        return view('frontend.nordic-product-details', ['product' => $product]);
    }

    public function nordicRecipes()
    {
        $recipes = NordicRecipe::orderBy('sort_order')->orderBy('id')->get();
        return view('frontend.nordic-recipes', compact('recipes'));
    }

    public function nordicRecipeDetails(string $recipe)
    {
        $rec = NordicRecipe::where('slug', $recipe)->first();
        abort_unless($rec, 404);
        return view('frontend.nordic-recipe-details', ['recipe' => $rec]);
    }

    public function nordicRecipeDetailsPdf(string $recipe)
    {
        $rec = NordicRecipe::where('slug', $recipe)->first();
        abort_unless($rec, 404);

        $safeName = Str::slug($rec->name ?: 'nordic-recipe');
        $facadeClass = \Barryvdh\DomPDF\Facade\Pdf::class;

        if (class_exists($facadeClass)) {
            try {
                $pdf = $facadeClass::loadView('pdf.nordic-recipe-details', [
                    'recipe' => $rec,
                ])->setPaper('a4', 'portrait');

                return $pdf->download($safeName . '.pdf');
            } catch (\Throwable) {
                // Fall through to lightweight PDF fallback.
            }
        }

        $fallbackPdf = $this->buildSimpleRecipePdf($rec);

        return response($fallbackPdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $safeName . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function buildSimpleRecipePdf(object $recipe, string $kicker = 'Korpala Nordic Recipe'): string
    {
        $ingredients = $this->normalizeRecipeList($recipe->ingredients ?? []);
        $procedures = $this->normalizeRecipeList($recipe->procedures ?? []);
        $desc = trim((string) ($recipe->description ?? 'No description available.'));
        $servings = trim((string) ($recipe->servings ?? 'N/A'));
        $calories = trim((string) ($recipe->calories ?? 'N/A'));

        $name = trim((string) ($recipe->name ?? 'Recipe Details'));
        $content = '';
        $y = 806;

        $writeLine = function (string $text, int $fontSize, string $font = 'F1') use (&$content, &$y): void {
            if ($text === '') {
                $y -= max(9, $fontSize);
                return;
            }

            $escaped = $this->escapePdfText($text);
            $content .= "BT\n";
            $content .= "/{$font} {$fontSize} Tf\n";
            $content .= "1 0 0 1 42 {$y} Tm\n";
            $content .= "({$escaped}) Tj\n";
            $content .= "ET\n";
            $y -= max(14, $fontSize + 5);
        };

        $writeSectionTitle = function (string $text) use ($writeLine, &$content, &$y): void {
            $writeLine($text, 13, 'F2');
            $lineY = $y + 6;
            $content .= "0.13 0.22 0.28 RG\n";
            $content .= "0.6 w\n";
            $content .= "42 {$lineY} m 553 {$lineY} l S\n";
            $y -= 2;
        };

        $writeLine($kicker, 10, 'F2');
        $writeLine($name !== '' ? $name : 'Recipe Details', 20, 'F2');
        $writeLine('', 11);
        $writeLine("Servings: {$servings}   |   Calories: {$calories}", 11, 'F2');
        $writeLine('', 11);

        $writeSectionTitle('Description');
        foreach ($this->wrapPdfText($desc, 92) as $line) {
            $writeLine($line, 11, 'F1');
        }
        $writeLine('', 10);

        $writeSectionTitle('Ingredients');
        if (!empty($ingredients)) {
            foreach ($ingredients as $ingredient) {
                foreach ($this->wrapPdfText('- ' . $ingredient, 90) as $line) {
                    $writeLine($line, 11, 'F1');
                }
            }
        } else {
            $writeLine('- No ingredients listed yet.', 11, 'F1');
        }
        $writeLine('', 10);

        $writeSectionTitle('Procedures');
        if (!empty($procedures)) {
            foreach ($procedures as $index => $procedure) {
                $step = ($index + 1) . '. ' . $procedure;
                foreach ($this->wrapPdfText($step, 90) as $line) {
                    $writeLine($line, 11, 'F1');
                }
            }
        } else {
            $writeLine('No procedures available yet.', 11, 'F1');
        }

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
        $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";

        return $this->buildPdfDocument($objects);
    }

    private function buildPdfDocument(array $objects): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $objectNumber = $index + 1;
            $offsets[] = strlen($pdf);
            $pdf .= $objectNumber . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= '0 ' . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n";
        $pdf .= '<< /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n";
        $pdf .= $xrefOffset . "\n";
        $pdf .= "%%EOF";

        return $pdf;
    }

    private function normalizeRecipeList(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\r\n|\r|\n/', $value) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($line) => $this->normalizeRecipeExportLine((string) $line),
            $value
        ), static fn ($line) => $line !== ''));
    }

    private function normalizeRecipeExportLine(string $line): string
    {
        $line = preg_replace_callback(
            '/\[@([^\]]+)\]\((?:menu-product:\d+|nordic-product:[a-z0-9\-]+)\)/i',
            static fn (array $matches): string => trim((string) ($matches[1] ?? '')),
            $line
        ) ?? $line;

        $line = preg_replace('/\s{2,}/', ' ', $line) ?? $line;
        return trim($line);
    }

    private function wrapPdfText(string $text, int $lineLength): array
    {
        $text = trim($text);
        if ($text === '') {
            return [''];
        }

        return preg_split("/\r\n|\r|\n/", wordwrap($text, $lineLength, "\n", true)) ?: [$text];
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $text
        );
    }

    private function nordicRecipeCatalog(): array
    {
        return [
            [
                'name' => 'Oat Congee',
                'slug' => 'oat-congee',
                'image' => 'https://source.unsplash.com/1728x2160/?oat,porridge,asian',
                'description' => 'Comforting rice-style oat porridge simmered until creamy with a mild, savory taste.',
                'servings' => '2 servings',
                'calories' => '310 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '3 cups water', '1 tsp minced ginger', '1 tbsp chopped spring onions', 'Salt and pepper to taste'],
                'procedures' => ['Bring water and ginger to a boil.', 'Add oats and cook on low heat for 10-12 minutes.', 'Season with salt and pepper.', 'Top with spring onions and serve hot.'],
            ],
            [
                'name' => 'Oat Soup',
                'slug' => 'oat-soup',
                'image' => 'https://source.unsplash.com/1728x2160/?oat,soup',
                'description' => 'A warm bowl of blended oats and vegetables for a smooth, hearty soup.',
                'servings' => '3 servings',
                'calories' => '260 kcal / serving',
                'ingredients' => ['3/4 cup Nordic oats', '1 tbsp olive oil', '1 small onion, diced', '2 cups vegetable broth', '1/2 cup milk'],
                'procedures' => ['Saute onion in olive oil until translucent.', 'Add oats and broth, then simmer for 12 minutes.', 'Blend until smooth and return to heat.', 'Stir in milk, season, and serve.'],
            ],
            [
                'name' => 'Oat Fried Rice',
                'slug' => 'oat-fried-rice',
                'image' => 'https://source.unsplash.com/1728x2160/?fried,rice,breakfast',
                'description' => 'Savory stir-fried oats inspired by classic fried rice flavors.',
                'servings' => '2 servings',
                'calories' => '340 kcal / serving',
                'ingredients' => ['1 cup cooked Nordic oats', '1 egg, beaten', '1/2 cup mixed vegetables', '1 tbsp soy sauce', '1 clove garlic, minced'],
                'procedures' => ['Scramble egg in a hot pan and set aside.', 'Saute garlic and vegetables for 2-3 minutes.', 'Add cooked oats and soy sauce; stir-fry.', 'Mix in egg and cook for 1 minute more.'],
            ],
            [
                'name' => 'Basic Overnight Oats',
                'slug' => 'basic-overnight-oats',
                'image' => 'https://source.unsplash.com/1728x2160/?overnight,oats,breakfast,bowl',
                'description' => 'No-cook oats soaked overnight for a creamy and ready-to-eat breakfast.',
                'servings' => '1 serving',
                'calories' => '290 kcal / serving',
                'ingredients' => ['1/2 cup Nordic oats', '1/2 cup milk', '1/4 cup yogurt', '1 tsp chia seeds', '1 tsp honey'],
                'procedures' => ['Combine all ingredients in a jar.', 'Mix until fully incorporated.', 'Cover and refrigerate overnight.', 'Stir and serve chilled.'],
            ],
            [
                'name' => 'Banana Peanut Butter Oats',
                'slug' => 'banana-peanut-butter-oats',
                'image' => 'https://source.unsplash.com/1728x2160/?banana,oats,peanut,butter,breakfast',
                'description' => 'A creamy oat bowl with sweet banana and rich peanut butter.',
                'servings' => '2 servings',
                'calories' => '360 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '2 cups milk', '1 ripe banana, mashed', '1 tbsp peanut butter', '1 tsp honey'],
                'procedures' => ['Cook oats with milk over medium heat.', 'Stir in mashed banana.', 'Add peanut butter and mix until creamy.', 'Drizzle honey and serve warm.'],
            ],
            [
                'name' => 'Apple Cinnamon Oatmeal',
                'slug' => 'apple-cinnamon-oatmeal',
                'image' => 'https://source.unsplash.com/1728x2160/?oats,apple,cinnamon',
                'description' => 'Cozy oats with fresh apples and cinnamon spice.',
                'servings' => '2 servings',
                'calories' => '320 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '2 cups water or milk', '1 apple, diced', '1 tsp cinnamon', '1 tsp brown sugar'],
                'procedures' => ['Simmer oats with water or milk.', 'Add apple and cinnamon halfway through cooking.', 'Cook until apples soften.', 'Finish with brown sugar and serve.'],
            ],
            [
                'name' => 'Oatmeal Cookies',
                'slug' => 'oatmeal-cookies',
                'image' => 'https://source.unsplash.com/1728x2160/?oatmeal,cookies',
                'description' => 'Soft and chewy cookies made with hearty oats.',
                'servings' => '10 cookies',
                'calories' => '150 kcal / cookie',
                'ingredients' => ['1 cup Nordic oats', '1/2 cup flour', '1/3 cup butter', '1/3 cup sugar', '1 egg'],
                'procedures' => ['Preheat oven to 175C.', 'Mix wet ingredients, then fold in dry ingredients.', 'Scoop dough onto lined tray.', 'Bake 12-15 minutes until golden.'],
            ],
            [
                'name' => 'Oat Brownies',
                'slug' => 'oat-brownies',
                'image' => 'https://source.unsplash.com/1728x2160/?oat,brownies',
                'description' => 'Fudgy brownies with oat texture and chocolate richness.',
                'servings' => '9 squares',
                'calories' => '210 kcal / square',
                'ingredients' => ['1 cup oat flour', '1/3 cup cocoa powder', '1/2 cup sugar', '2 eggs', '1/3 cup melted butter'],
                'procedures' => ['Preheat oven to 175C and line a pan.', 'Combine wet ingredients, then stir in dry ingredients.', 'Spread batter evenly into pan.', 'Bake 20-25 minutes, cool, then slice.'],
            ],
            [
                'name' => 'Oat Pancakes',
                'slug' => 'oat-pancakes',
                'image' => 'https://source.unsplash.com/1728x2160/?oatmeal,pancakes',
                'description' => 'Fluffy pancakes with wholesome oat flavor.',
                'servings' => '8 pancakes',
                'calories' => '120 kcal / pancake',
                'ingredients' => ['1 cup oat flour', '1 tbsp sugar', '1 tsp baking powder', '1 egg', '3/4 cup milk'],
                'procedures' => ['Whisk dry ingredients together.', 'Add egg and milk to form batter.', 'Pour small rounds on a hot pan.', 'Flip when bubbles form and cook through.'],
            ],
            [
                'name' => 'Oat Waffles',
                'slug' => 'oat-waffles',
                'image' => 'https://source.unsplash.com/1728x2160/?waffles,breakfast',
                'description' => 'Crisp-edged waffles with oat flour and light sweetness.',
                'servings' => '4 waffles',
                'calories' => '190 kcal / waffle',
                'ingredients' => ['1 cup oat flour', '1 tsp baking powder', '1 egg', '3/4 cup milk', '1 tbsp melted butter'],
                'procedures' => ['Preheat waffle maker.', 'Mix all ingredients until smooth.', 'Pour batter into waffle maker.', 'Cook until golden and crisp.'],
            ],
            [
                'name' => 'Garlic Mushroom Oats',
                'slug' => 'garlic-mushroom-oats',
                'image' => 'https://source.unsplash.com/1728x2160/?garlic,mushroom,oats',
                'description' => 'Savory oatmeal with sauteed mushroom and garlic aroma.',
                'servings' => '2 servings',
                'calories' => '300 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '2 cups broth', '1 cup mushrooms, sliced', '2 cloves garlic, minced', '1 tsp olive oil'],
                'procedures' => ['Saute garlic and mushrooms in olive oil.', 'Cook oats in broth until creamy.', 'Fold in sauteed mushroom mixture.', 'Season and serve warm.'],
            ],
            [
                'name' => 'Egg & Spinach Oats',
                'slug' => 'egg-spinach-oats',
                'image' => 'https://source.unsplash.com/1728x2160/?egg,spinach,breakfast,bowl',
                'description' => 'Protein-rich savory oats with egg and spinach.',
                'servings' => '2 servings',
                'calories' => '330 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '2 cups water', '2 eggs', '1 cup spinach', 'Salt and pepper'],
                'procedures' => ['Cook oats in water until thick.', 'Stir in spinach until wilted.', 'Top with poached or fried eggs.', 'Season to taste and serve.'],
            ],
            [
                'name' => 'Chicken Oat Porridge',
                'slug' => 'chicken-oat-porridge',
                'image' => 'https://source.unsplash.com/1728x2160/?chicken,porridge,bowl',
                'description' => 'A filling savory porridge with shredded chicken and soft oats.',
                'servings' => '3 servings',
                'calories' => '350 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '3 cups chicken broth', '1 cup shredded cooked chicken', '1 tsp garlic powder', 'Chopped scallions'],
                'procedures' => ['Boil broth and add oats.', 'Simmer until thickened.', 'Add shredded chicken and garlic powder.', 'Garnish with scallions and serve hot.'],
            ],
            [
                'name' => 'Tomato Basil Savory Oats',
                'slug' => 'tomato-basil-savory-oats',
                'image' => 'https://source.unsplash.com/1728x2160/?tomato,basil,oats,savory',
                'description' => 'Tangy tomato and basil oats for a savory Mediterranean profile.',
                'servings' => '2 servings',
                'calories' => '290 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '2 cups vegetable broth', '1/2 cup diced tomatoes', '1 tbsp chopped basil', '1 tsp olive oil'],
                'procedures' => ['Heat olive oil and briefly cook tomatoes.', 'Add broth and oats; simmer until creamy.', 'Stir in chopped basil.', 'Adjust seasoning and serve.'],
            ],
            [
                'name' => 'Chocolate Cocoa Oats',
                'slug' => 'chocolate-cocoa-oats',
                'image' => 'https://source.unsplash.com/1728x2160/?oats,cocoa,chocolate,breakfast',
                'description' => 'Dessert-style breakfast oats with cocoa and chocolate flavor.',
                'servings' => '2 servings',
                'calories' => '340 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '2 cups milk', '1 tbsp cocoa powder', '1 tbsp dark chocolate chips', '1 tsp honey'],
                'procedures' => ['Cook oats with milk over medium heat.', 'Whisk in cocoa powder until smooth.', 'Stir in chocolate chips until melted.', 'Sweeten with honey and serve.'],
            ],
            [
                'name' => 'Mango Coconut Oats',
                'slug' => 'mango-coconut-oats',
                'image' => 'https://source.unsplash.com/1728x2160/?mango,oats,coconut',
                'description' => 'Tropical oats with ripe mango and creamy coconut notes.',
                'servings' => '2 servings',
                'calories' => '330 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '1 cup coconut milk', '1 cup water', '1/2 cup diced mango', '1 tbsp toasted coconut'],
                'procedures' => ['Cook oats with coconut milk and water.', 'Simmer until creamy.', 'Top with mango and toasted coconut.', 'Serve warm or chilled.'],
            ],
            [
                'name' => 'Strawberry Yogurt Oats',
                'slug' => 'strawberry-yogurt-oats',
                'image' => 'https://source.unsplash.com/1728x2160/?strawberry,yogurt,oatmeal',
                'description' => 'Refreshing oat bowl with strawberries and tangy yogurt.',
                'servings' => '2 servings',
                'calories' => '300 kcal / serving',
                'ingredients' => ['1 cup Nordic oats', '2 cups milk', '1/2 cup yogurt', '1/2 cup sliced strawberries', '1 tsp honey'],
                'procedures' => ['Cook oats with milk until soft.', 'Cool slightly and fold in yogurt.', 'Top with strawberries.', 'Drizzle honey and serve.'],
            ],
        ];
    }

    // ── User-facing dashboard (content driven by DB) ──────────
    private function menuProductCatalog(): array
    {
        $productImages = config('menu-images.products', []);

        $makeProduct = function (
            string $slug,
            string $title,
            string $description,
            array $highlights,
            array $sizes
        ) use ($productImages): array {
            $images = array_values($productImages[$slug] ?? []);

            return [
                'title' => $title,
                'category' => 'Products',
                'description' => $description,
                'images' => $images,
                'type_media' => [],
                'highlights' => $highlights,
                'sizes' => $sizes,
                'variant_options' => $sizes,
                'classifications' => [],
                'showcase_slides' => collect($images)
                    ->values()
                    ->map(fn(string $image, int $index) => [
                        'type' => $title . ' ' . ($index + 1),
                        'image' => $image,
                        'alt' => $title . ' showcase image ' . ($index + 1),
                    ])
                    ->all(),
            ];
        };

        $catalog = [
            'jelly-mixes' => $makeProduct(
                'jelly-mixes',
                'Jelly Mixes',
                'Bright, fun dessert essentials designed for easy preparation, repeat purchase, and everyday menu variety.',
                ['Vibrant dessert option', 'Simple preparation flow', 'Great for homes and food stalls'],
                ['Regular Pack', 'Family Pack']
            ),
            'breading-mixes' => $makeProduct(
                'breading-mixes',
                'Breading Mixes',
                'Coating blends that help deliver crisp texture, strong flavor pickup, and more consistent frying results.',
                ['Crunch-focused finish', 'Reliable kitchen performance', 'Good for quick-service menus'],
                ['200 g', '500 g']
            ),
            'powder-mixes' => $makeProduct(
                'powder-mixes',
                'Powder Mixes',
                'Flexible pantry staples for savory applications, built for speed, convenience, and dependable flavor delivery.',
                ['Versatile kitchen base', 'Fast prep support', 'Useful for daily operations'],
                ['Regular Pack', 'Value Pack']
            ),
            'bouillon-cubes' => $makeProduct(
                'bouillon-cubes',
                'Bouillon Cubes',
                'Compact seasoning cubes that bring rich taste and convenience to soups, sauces, and everyday dishes.',
                ['Easy flavor boost', 'Shelf-friendly format', 'Suitable for household and trade'],
                ['Single Sleeve', 'Bulk Pack']
            ),
            'noodles-and-pastas' => $makeProduct(
                'noodles-and-pastas',
                'Noodles and Pastas',
                'Quick-cook favorites for comforting meals, efficient prep, and broad appeal across home and business settings.',
                ['Comfort-food staple', 'Quick cooking time', 'Works across multiple recipes'],
                ['Standard Pack', 'Multipack']
            ),
            'powdered-drinks' => $makeProduct(
                'powdered-drinks',
                'Powdered Drinks',
                'Refreshment mixes with convenient storage, easy portioning, and crowd-friendly flavor options.',
                ['Easy to mix', 'Good for events and resale', 'Convenient long shelf life'],
                ['Sachet', 'Pitcher Pack']
            ),
            'professional-series' => $makeProduct(
                'professional-series',
                'Professional Series',
                'Business-ready product solutions made for higher-volume kitchens that need consistency and operational ease.',
                ['Designed for foodservice', 'Volume-oriented packs', 'Built for repeat orders'],
                ['Kitchen Pack', 'Commercial Pack']
            ),
        ];

        $catalog['jelly-mixes']['classifications'] = [
            [
                'type' => 'Flavored',
                'items' => ['Mango', 'Coffee', 'Cucumber', 'Four Season', 'Passion Fruit', 'Buko Strips', 'Pandan', 'Ube', 'Leche Flan'],
            ],
            [
                'type' => 'Unflavored',
                'items' => ['Clear', 'Red', 'Green', 'Yellow', 'Black'],
            ],
        ];
        $catalog['jelly-mixes']['showcase_slides'] = [
            [
                'type' => 'Flavored',
                'image' => 'https://images.unsplash.com/photo-1488477304112-4944851de03d?w=1200&q=80&auto=format&fit=crop',
                'alt' => 'Flavored jelly showcase',
            ],
            [
                'type' => 'Unflavored',
                'image' => 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=1200&q=80&auto=format&fit=crop',
                'alt' => 'Unflavored jelly showcase',
            ],
        ];
        $catalog['jelly-mixes']['variant_options'] = ['Assorted Flavored', 'Assorted Unflavored'];
        $catalog['jelly-mixes']['type_media'] = [
            'unflavored' => [
                'images' => [
                    'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=1200&q=80&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1517673132405-a56a62b18caf?w=900&q=80&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1515543904379-3d757afe72e3?w=900&q=80&auto=format&fit=crop',
                ],
                'variant' => 'Assorted Unflavored',
            ],
            'flavored' => [
                'images' => [
                    'https://images.unsplash.com/photo-1488477304112-4944851de03d?w=1200&q=80&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1464306076886-da185f6a9d05?w=900&q=80&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1464195244916-405fa0a82545?w=900&q=80&auto=format&fit=crop',
                ],
                'variant' => 'Assorted Flavored',
            ],
        ];

        $catalog['breading-mixes']['classifications'] = [
            [
                'type' => 'Variants',
                'items' => ['Spicy', 'Salted Egg', 'Original'],
            ],
        ];
        $catalog['breading-mixes']['variant_options'] = ['Spicy', 'Salted Egg', 'Original'];

        return $catalog;
    }

    public function userDashboard()
    {
        // Priority: dedicated slug → active page → first page in table
        $page = Page::where('slug', 'user-dashboard')->first()
            ?? Page::where('is_active', true)->first()
            ?? Page::orderBy('id')->first();

        // If no pages exist at all yet, return an empty view gracefully
        if (!$page) {
            return view('frontend.user-dashboard', [
                'page' => null, 'carouselImages' => collect(), 'headerLogo' => null,
                'logos' => collect(), 'productImages' => collect(), 'recipeImages' => collect(),
                'tc' => collect(), 'strengthCards' => collect(), 'brandCards' => collect(),
                'histItems' => collect(), 'phones' => collect(), 'emails' => collect(),
                'links' => collect(), 'fontsToLoad' => collect(),
                'text'  => fn(string $k) => '',
                'style' => fn(string $k) => '',
            ]);
        }

        $pid = $page->id;

        // ── Media ──────────────────────────────────────────────
        $carouselImages = DB::table('carousel_images')
            ->where('page_id', $pid)->where('is_active', true)->orderBy('id')->get();

        $logos = DB::table('logos')
            ->where('page_id', $pid)->where('is_active', true)->orderBy('id')->get();

        $productImages = DB::table('product_images')
            ->where('page_id', $pid)->where('is_active', true)->orderBy('id')->get();

        $recipeImages = DB::table('recipe_images')
            ->where('page_id', $pid)->where('is_active', true)->orderBy('id')->get();

        // ── Raw text content keyed by 'key' ───────────────────
        $tc = DB::table('text_content')->where('page_id', $pid)->get()->keyBy('key');

        // ── Strength cards — keys: strength_N_title / strength_N_desc ──
        $strengthCards = collect();
        for ($n = 1; $n <= 20; $n++) {
            $title = $tc->get("strength_{$n}_title");
            $desc  = $tc->get("strength_{$n}_desc");
            if (!$title && !$desc) break;
            $strengthCards->push(['title' => $title?->value ?? '', 'desc' => $desc?->value ?? '']);
        }

        // ── Header / footer logo — first logo in the table ────
        $headerLogo = $logos->first();

        // ── Brand cards — logos after the first (index 1+) ────
        // keys: brand_N_name / brand_N_desc (N starts at 1)
        $brandCards = collect();
        foreach ($logos->skip(1)->values() as $i => $logo) {
            $n    = $i + 1;
            $name = $tc->get("brand_{$n}_name")?->value ?? '';
            $desc = $tc->get("brand_{$n}_desc")?->value ?? '';
            $brandCards->push(['logo' => $logo, 'name' => $name, 'desc' => $desc]);
        }

        // ── History items — keys: history_YEAR_title / history_YEAR_desc ──
        $histItems = $tc->keys()
            ->filter(fn($k) => preg_match('/^history_\d{4}_title$/', $k))
            ->map(function ($k) use ($tc) {
                $year = substr($k, 8, 4);
                return [
                    'year'  => $year,
                    'title' => $tc->get("history_{$year}_title")?->value ?? '',
                    'desc'  => $tc->get("history_{$year}_desc")?->value  ?? '',
                ];
            })
            ->sortBy('year')
            ->values();

        // ── Contact info ───────────────────────────────────────
        $phones = DB::table('contact_numbers')->where('page_id', $pid)->orderBy('id')->get();
        $emails = DB::table('email_addresses')->where('page_id', $pid)->orderBy('id')->get();
        $links  = DB::table('links')->where('page_id', $pid)->orderBy('id')->get();

        // ── Remaining text fields (simple key→value lookup) ───
        $text = fn(string $key) => $tc->get($key)?->value ?? '';

        // ── Inline style closure (font-family + font-size per key) ───
        $style = function (string $key) use ($tc): string {
            $parts = [];
            $font = $tc->get("{$key}_font")?->value;
            $size = $tc->get("{$key}_font_size")?->value;
            if ($font) $parts[] = "font-family:'{$font}',sans-serif";
            if ($size) $parts[] = "font-size:{$size}";
            return implode(';', $parts);
        };

        // ── Collect unique fonts to preload in the page <head> ───
        $fontsToLoad = $tc
            ->filter(fn($item) => str_ends_with($item->key, '_font'))
            ->pluck('value')
            ->unique()
            ->filter()
            ->values();

        return view('frontend.user-dashboard', compact(
            'page',
            'carouselImages', 'headerLogo', 'logos', 'productImages', 'recipeImages',
            'tc', 'strengthCards', 'brandCards', 'histItems',
            'phones', 'emails', 'links',
            'fontsToLoad'
        ) + ['text' => $text, 'style' => $style]);
    }
}
