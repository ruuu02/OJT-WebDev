<?php

namespace App\Http\Controllers;

use App\Mail\SecurityAlertMail;
use App\Models\AdminSecurityEvent;
use App\Models\AdminUser;
use App\Models\MenuCategoryLineItem;
use App\Models\MenuRecipe;
use App\Models\MenuRecipeCategory;
use App\Models\NordicRecipe;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class AdminController extends Controller
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

    private function visualEditorCategoryPageSlug(string $categorySlug): string
    {
        $categorySlug = $this->normalizeMenuCategorySlug($categorySlug);

        if ($categorySlug === 'noodles-and-pastas') {
            return 'menu_cat_noodles_pastas';
        }

        return 'menu_cat_' . str_replace('-', '_', $categorySlug);
    }

    private function categorySlugFromVisualEditorPage(string $pageSlug): ?string
    {
        if (in_array($pageSlug, ['menu_cat_noodles_pastas', 'menu_cat_noodles_and_pastas', 'menu_cat_noodles_pastas_and_sauces'], true)) {
            return 'noodles-and-pastas';
        }

        if (! str_starts_with($pageSlug, 'menu_cat_')) {
            return null;
        }

        $suffix = substr($pageSlug, strlen('menu_cat_'));
        if (! is_string($suffix) || trim($suffix) === '') {
            return null;
        }

        return $this->normalizeMenuCategorySlug(str_replace('_', '-', $suffix));
    }

    private function normalizeVisualEditorPageSlug(string $pageSlug): string
    {
        if (in_array($pageSlug, ['menu_cat_noodles_and_pastas', 'menu_cat_noodles_pastas_and_sauces'], true)) {
            return 'menu_cat_noodles_pastas';
        }

        return $pageSlug;
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

    private function resolveVisualEditorContentPageId(string $activePage, int $fallbackPageId): int
    {
        $menuBackedPages = [
            'menu_item_details',
            'menu_recipelist',
            'menu_recipe_category',
            'menu_recipe_detail',
        ];

        if (in_array($activePage, $menuBackedPages, true)) {
            $menuPage = DB::table('pages')->where('slug', 'menu')->first();
            return $menuPage ? (int) $menuPage->id : $fallbackPageId;
        }

        if (str_starts_with($activePage, 'menu_cat_')) {
            $dedicatedPageId = DB::table('pages')->where('slug', $activePage)->value('id');
            if ($dedicatedPageId) {
                return (int) $dedicatedPageId;
            }

            $menuPageId = DB::table('pages')->where('slug', 'menu')->value('id');
            return $menuPageId ? (int) $menuPageId : $fallbackPageId;
        }

        return $fallbackPageId;
    }

    private function resolveVisualEditorSharedPageIds(string $activePage): array
    {
        if (str_starts_with($activePage, 'menu_cat_')) {
            $menuPageId = DB::table('pages')->where('slug', 'menu')->value('id');
            return $menuPageId ? [(int) $menuPageId] : [];
        }

        if (in_array($activePage, ['nordic_products', 'nordic_product_details', 'nordic_recipes', 'nordic_recipe_details'], true)) {
            $sharedPageIds = [];

            $nordicPageId = DB::table('pages')->where('slug', 'nordic')->value('id');
            if ($nordicPageId) {
                $sharedPageIds[] = (int) $nordicPageId;
            }

            if (in_array($activePage, ['nordic_product_details', 'nordic_recipes', 'nordic_recipe_details'], true)) {
                $nordicProductsPageId = DB::table('pages')->where('slug', 'nordic_products')->value('id');
                if ($nordicProductsPageId) {
                    $sharedPageIds[] = (int) $nordicProductsPageId;
                }
            }

            if (in_array($activePage, ['nordic_recipes', 'nordic_recipe_details'], true)) {
                $nordicProductDetailsPageId = DB::table('pages')->where('slug', 'nordic_product_details')->value('id');
                if ($nordicProductDetailsPageId) {
                    $sharedPageIds[] = (int) $nordicProductDetailsPageId;
                }
            }

            return array_values(array_unique($sharedPageIds));
        }

        return [];
    }

    // ── Login ────────────────────────────────────────────────

    public function showLogin()
    {
        if (session('admin_logged_in')) {
            return redirect()->route('admin.visual-editor');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $admin = AdminUser::where('email', $request->email)->first();

        if ($admin && Hash::check($request->password, $admin->password)) {
            $request->session()->regenerate();
            $serverPid = PHP_SAPI === 'cli-server' ? getmypid() : null;
            session([
                'admin_logged_in' => true,
                'admin_id'        => $admin->id,
                'admin_name'      => $admin->name,
                'admin_email'     => $admin->email,
                // For local "php artisan serve": force re-login after server restart.
                'admin_server_pid' => $serverPid,
            ]);

            $this->sendSecurityAlert('login_success', $admin, $request);
            $this->recordSecurityEvent($admin, 'login_success', 'Admin logged in successfully.', $request);

            return redirect()->route('admin.visual-editor');
        }

        // Only alert when the email matches a real account (avoid enumeration on failed login)
        if ($admin) {
            $this->sendSecurityAlert('login_failed', $admin, $request);
            $this->recordSecurityEvent($admin, 'login_failed', 'A failed login attempt was detected.', $request);
        }

        return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    // ── Dashboard ────────────────────────────────────────────

    public function dashboard()
    {
        $pages = Page::orderBy('id')->get();

        $allMedia = collect()
            ->merge(DB::table('carousel_images')->get()->map(fn($r) => array_merge((array) $r, ['section' => 'carousel'])))
            ->merge(DB::table('logos')->get()->map(fn($r) => array_merge((array) $r, ['section' => 'logo'])))
            ->merge(DB::table('product_images')->get()->map(fn($r) => array_merge((array) $r, ['section' => 'product'])))
            ->merge(DB::table('recipe_images')->get()->map(fn($r) => array_merge((array) $r, ['section' => 'recipe'])));

        $allContents = collect()
            ->merge(DB::table('text_content')->get()->map(fn($r) => array_merge((array) $r, ['section' => 'text_content'])))
            ->merge(DB::table('product_details')->get()->map(fn($r) => array_merge((array) $r, ['section' => 'product_details'])))
            ->merge(DB::table('featured_recipes')->get()->map(fn($r) => array_merge((array) $r, ['section' => 'featured_recipes'])));

        $allContacts = collect()
            ->merge(DB::table('contact_numbers')->get()->map(fn($r) => array_merge((array) $r, ['type' => 'phone'])))
            ->merge(DB::table('email_addresses')->get()->map(fn($r) => array_merge((array) $r, ['type' => 'email'])))
            ->merge(DB::table('links')->get()->map(fn($r) => array_merge((array) $r, ['type' => 'link'])));

        return view('admin.dashboard', compact('pages', 'allMedia', 'allContents', 'allContacts'));
    }

    // ── Edit Page ─────────────────────────────────────────────

    public function editPage(int $id)
    {
        $page = Page::findOrFail($id);
        return view('admin.edit_page', compact('page'));
    }

    public function updatePage(Request $request, int $id)
    {
        $page = Page::findOrFail($id);

        $request->validate([
            'title'        => 'required|string|max:255',
            'hero_heading' => 'nullable|string|max:255',
            'hero_subtext' => 'nullable|string',
        ]);

        $page->update([
            'title'        => $request->title,
            'hero_heading' => $request->hero_heading,
            'hero_subtext' => $request->hero_subtext,
        ]);

        return back()->with('success', 'Page updated successfully.');
    }

    public function setActivePage(int $id)
    {
        Page::query()->update(['is_active' => false]);
        Page::findOrFail($id)->update(['is_active' => true]);

        return back()->with('success', 'Homepage set as live.');
    }

    // ── Visual Editor ─────────────────────────────────────────

    public function visualEditor(Request $request)
    {
        $cmsSchema = config('cms');
        $dynamicMenuPages = collect($this->discoverMenuCategoryLabels())
            ->reject(fn ($label, $slug) => array_key_exists($this->visualEditorCategoryPageSlug($slug), $cmsSchema))
            ->map(function ($label, $slug) {
                return [
                    'slug' => $this->visualEditorCategoryPageSlug($slug),
                    'label' => (string) $label,
                    'group' => 'Menu',
                ];
            })
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $pages = collect($cmsSchema)->map(function ($page, $slug) {
            $group = 'Other';
            if ($slug === 'ultrafood') {
                $group = 'Ultrafood';
            } elseif (str_starts_with($slug, 'menu')) {
                $group = 'Menu';
            } elseif (str_starts_with($slug, 'nordic')) {
                $group = 'Nordic';
            }

            return [
                'slug'  => $slug,
                'label' => $page['label'],
                'group' => $group,
            ];
        })->values()->merge($dynamicMenuPages)->values();

        $groupOrder = ['Ultrafood', 'Menu', 'Nordic', 'Other'];
        $pageGroups = collect($groupOrder)->mapWithKeys(function ($group) use ($pages) {
            return [$group => $pages->where('group', $group)->values()];
        })->filter(fn($items) => $items->isNotEmpty());

        $activePage = $this->normalizeVisualEditorPageSlug(
            (string) $request->query('page', array_key_first($cmsSchema))
        );
        $isDynamicMenuCategoryPage = str_starts_with($activePage, 'menu_cat_') && ! array_key_exists($activePage, $cmsSchema);

        if (! array_key_exists($activePage, $cmsSchema) && ! $isDynamicMenuCategoryPage) {
            $activePage = array_key_first($cmsSchema);
        }

        $pageRecord = DB::table('pages')->where('slug', $activePage)->first();
        $pageId     = $pageRecord ? (int) $pageRecord->id : 1;

        $pageId = $this->resolveVisualEditorContentPageId($activePage, $pageId);

        $sharedPageIds = $this->resolveVisualEditorSharedPageIds($activePage);

        $allMedia = collect()
            ->merge(DB::table('carousel_images')->where('page_id', $pageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'carousel'])))
            ->merge(DB::table('logos')->where('page_id', $pageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'logo'])))
            ->merge(DB::table('product_images')->where('page_id', $pageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'product'])))
            ->merge(DB::table('recipe_images')->where('page_id', $pageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'recipe'])))
            ->merge(DB::table('menu_images')->where('page_id', $pageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'menu'])));

        $allContents = collect()
            ->merge(DB::table('text_content')->where('page_id', $pageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'text_content'])))
            ->merge(DB::table('product_details')->where('page_id', $pageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'product_details'])))
            ->merge(DB::table('featured_recipes')->where('page_id', $pageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'featured_recipes'])));

        foreach ($sharedPageIds as $sharedPageId) {
            $allMedia = $allMedia
                ->merge(DB::table('carousel_images')->where('page_id', $sharedPageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'carousel'])))
                ->merge(DB::table('logos')->where('page_id', $sharedPageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'logo'])))
                ->merge(DB::table('product_images')->where('page_id', $sharedPageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'product'])))
                ->merge(DB::table('recipe_images')->where('page_id', $sharedPageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'recipe'])))
                ->merge(DB::table('menu_images')->where('page_id', $sharedPageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'menu'])));

            $allContents = $allContents
                ->merge(DB::table('text_content')->where('page_id', $sharedPageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'text_content'])))
                ->merge(DB::table('product_details')->where('page_id', $sharedPageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'product_details'])))
                ->merge(DB::table('featured_recipes')->where('page_id', $sharedPageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'featured_recipes'])));
        }

        $allContacts = collect()
            ->merge(DB::table('contact_numbers')->where('page_id', $pageId)->get()->map(fn($r) => array_merge((array) $r, ['type' => 'phone'])))
            ->merge(DB::table('email_addresses')->where('page_id', $pageId)->get()->map(fn($r) => array_merge((array) $r, ['type' => 'email'])))
            ->merge(DB::table('links')->where('page_id', $pageId)->get()->map(fn($r) => array_merge((array) $r, ['type' => 'link'])));

        $categorySlug = null;
        $cmsPageSlug = null;
        $lineItems = collect();
        $isVisualEditorPage = false;
        $categoryTitle = '';
        $item = null;
        $isItemDetailsPage = $activePage === 'menu_item_details';
        $recipeCategories = collect();
        $activeRecipeCategory = null;
        $recipes = collect();
        $activeRecipe = null;
        $nordicRecipes = collect();
        $activeNordicRecipe = null;
        $menuProductReferences = collect();
        $menuProductLinkMap = [];

        if (str_starts_with($activePage, 'menu')) {
            $recipeCategories = MenuRecipeCategory::orderBy('sort_order')->orderBy('id')->get();
            $categoryLabels = $this->discoverMenuCategoryLabels();
            $menuProductRows = MenuCategoryLineItem::query()
                ->orderBy('category_slug')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $menuProductReferences = $menuProductRows
                ->map(function (MenuCategoryLineItem $item) use ($categoryLabels) {
                    $categorySlug = Str::slug((string) $item->category_slug);
                    $categoryLabel = trim((string) ($categoryLabels[$categorySlug] ?? Str::headline(str_replace('-', ' ', $categorySlug))));

                    return [
                        'id' => (int) $item->id,
                        'title' => trim((string) $item->title),
                        'category_slug' => $categorySlug,
                        'category_label' => $categoryLabel,
                    ];
                })
                ->values();

            $menuProductLinkMap = $menuProductRows
                ->mapWithKeys(function (MenuCategoryLineItem $item) {
                    $id = (int) $item->id;
                    return [$id => [
                        'id' => $id,
                        'category_slug' => Str::slug((string) $item->category_slug),
                        'title' => trim((string) $item->title),
                    ]];
                })
                ->all();
        }

        if (str_starts_with($activePage, 'menu_cat_')) {
            $isVisualEditorPage = true;
            $categorySlug = $this->categorySlugFromVisualEditorPage($activePage) ?? 'jelly-mixes';
            $cmsPageSlug = $activePage;
            $lineItems = MenuCategoryLineItem::where('category_slug', $categorySlug)->orderBy('sort_order')->orderBy('id')->get();
            $titles = $this->discoverMenuCategoryLabels();
            $categoryTitle = $titles[$categorySlug] ?? Str::headline(str_replace('-', ' ', $categorySlug));
        } elseif ($isItemDetailsPage) {
            // Item details editor: allow selecting a specific product via query params
            // (set by Visual Editor link interception).
            $reqCategory = (string) $request->query('category', 'jelly-mixes');
            $reqItemSlug = (string) $request->query('item', '');

            // Default category for item detail editing (and fallback when invalid).
            $categorySlug = $reqCategory ?: 'jelly-mixes';

            $lineItemsForCategory = MenuCategoryLineItem::where('category_slug', $categorySlug)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            // If category had no items, fall back to jelly-mixes.
            if ($lineItemsForCategory->isEmpty() && $categorySlug !== 'jelly-mixes') {
                $categorySlug = 'jelly-mixes';
                $lineItemsForCategory = MenuCategoryLineItem::where('category_slug', $categorySlug)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();
            }

            // Prefer matching the requested item slug; otherwise first item.
            if ($reqItemSlug !== '') {
                $item = $lineItemsForCategory->first(
                    fn (MenuCategoryLineItem $it) => $this->matchesMenuCategoryItem($it, $reqItemSlug)
                );
            }
            if (!$item) {
                $item = $lineItemsForCategory->first();
            }

            // Title for breadcrumbs/header.
            $titles = $this->discoverMenuCategoryLabels();
            $categoryTitle = $titles[$categorySlug] ?? Str::headline(str_replace('-', ' ', $categorySlug));
        } elseif (in_array($activePage, ['menu_recipelist', 'menu_recipe_category', 'menu_recipe_detail'], true)) {
            // Visual editor recipe management pages (DB-driven)
            $isVisualEditorPage = true;

            $reqCat = (string) $request->query('cat', '');
            if ($reqCat !== '') {
                $activeRecipeCategory = $recipeCategories->first(
                    fn (MenuRecipeCategory $c) => $c->slug === $reqCat
                );
            }
            if (!$activeRecipeCategory) {
                $activeRecipeCategory = $recipeCategories->first();
            }

            if ($activeRecipeCategory) {
                $recipes = MenuRecipe::where('category_id', $activeRecipeCategory->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();
            }

            if ($activePage === 'menu_recipe_detail') {
                $reqRecipe = (string) $request->query('recipe', '');
                if ($reqRecipe !== '' && $recipes->isNotEmpty()) {
                    $activeRecipe = $recipes->first(fn (MenuRecipe $r) => $r->slug === $reqRecipe);
                }
                if (!$activeRecipe && $recipes->isNotEmpty()) {
                    $activeRecipe = $recipes->first();
                }
            }
        } elseif (in_array($activePage, ['nordic_recipes', 'nordic_recipe_details'], true)) {
            $isVisualEditorPage = true;
            $nordicRecipes = NordicRecipe::orderBy('sort_order')->orderBy('id')->get();

            if ($activePage === 'nordic_recipe_details') {
                $reqRecipe = (string) $request->query('recipe', '');
                if ($reqRecipe !== '') {
                    $activeNordicRecipe = $nordicRecipes->first(fn (NordicRecipe $r) => $r->slug === $reqRecipe);
                }
                if (! $activeNordicRecipe) {
                    $activeNordicRecipe = $nordicRecipes->first();
                }
            }
        }

        return view('admin.visual-editor', compact(
            'cmsSchema',
            'pages',
            'pageGroups',
            'activePage',
            'allMedia',
            'allContents',
            'allContacts',
            'pageId',
            'categorySlug',
            'cmsPageSlug',
            'lineItems',
            'isVisualEditorPage',
            'categoryTitle',
            'item',
            'isItemDetailsPage',
            'recipeCategories',
            'activeRecipeCategory',
            'recipes',
            'activeRecipe',
            'menuProductReferences',
            'menuProductLinkMap',
            'nordicRecipes',
            'activeNordicRecipe'
        ));
    }

    /**
     * Fresh text/product/recipe rows for the active editor page (no HTML cache).
     * Used by refreshCanvas so undo/save always match DB without full reload.
     */
    public function visualEditorContents(Request $request)
    {
        $cmsSchema  = config('cms');
        $activePage = $this->normalizeVisualEditorPageSlug(
            (string) $request->query('page', array_key_first($cmsSchema))
        );
        if (! array_key_exists($activePage, $cmsSchema)) {
            $activePage = array_key_first($cmsSchema);
        }
        $pageRecord = DB::table('pages')->where('slug', $activePage)->first();
        $pageId     = $pageRecord ? (int) $pageRecord->id : 1;

        $pageId = $this->resolveVisualEditorContentPageId($activePage, $pageId);

        $sharedPageIds = $this->resolveVisualEditorSharedPageIds($activePage);

        $allContents = collect()
            ->merge(DB::table('text_content')->where('page_id', $pageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'text_content'])))
            ->merge(DB::table('product_details')->where('page_id', $pageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'product_details'])))
            ->merge(DB::table('featured_recipes')->where('page_id', $pageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'featured_recipes'])));

        foreach ($sharedPageIds as $sharedPageId) {
            $allContents = $allContents
                ->merge(DB::table('text_content')->where('page_id', $sharedPageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'text_content'])))
                ->merge(DB::table('product_details')->where('page_id', $sharedPageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'product_details'])))
                ->merge(DB::table('featured_recipes')->where('page_id', $sharedPageId)->get()->map(fn($r) => array_merge((array) $r, ['section' => 'featured_recipes'])));
        }

        $allContents = $allContents->values();

        return response()
            ->json($allContents)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    // ── Settings ──────────────────────────────────────────────

    public function showSettings()
    {
        $admin = AdminUser::findOrFail(session('admin_id'));

        $securityEvents = AdminSecurityEvent::query()
            ->where('admin_user_id', $admin->id)
            ->latest()
            ->limit(8)
            ->get();

        $failedLogins7d = AdminSecurityEvent::query()
            ->where('admin_user_id', $admin->id)
            ->where('event', 'login_failed')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $lastSuccessfulLogin = AdminSecurityEvent::query()
            ->where('admin_user_id', $admin->id)
            ->where('event', 'login_success')
            ->latest()
            ->first();

        $lastPasswordChange = AdminSecurityEvent::query()
            ->where('admin_user_id', $admin->id)
            ->where('event', 'password_changed')
            ->latest()
            ->first();

        return view('admin.settings', compact(
            'admin',
            'securityEvents',
            'failedLogins7d',
            'lastSuccessfulLogin',
            'lastPasswordChange'
        ));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => [
                'required',
                'confirmed',
                'min:12',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&^_\-]/',
            ],
        ], [
            'new_password.min'    => 'New password must be at least 12 characters.',
            'new_password.regex'  => 'New password must include uppercase, lowercase, a number, and a special character (@$!%*#?&^_-).',
        ]);

        $admin = AdminUser::findOrFail(session('admin_id'));

        if (!Hash::check($request->current_password, $admin->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $admin->update(['password' => Hash::make($request->new_password)]);

        $this->sendSecurityAlert('password_changed', $admin, $request);
        $this->recordSecurityEvent($admin, 'password_changed', 'Password was updated from the admin settings page.', $request);

        // Invalidate session immediately — force re-login after password change
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', 'Password changed successfully. Please log in with your new password.');
    }

    public function updateAccountDetails(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'name'             => 'required|string|max:255',
            'email'            => [
                'required',
                'email',
                'max:255',
                Rule::unique('admin_users', 'email')->ignore((int) session('admin_id')),
            ],
            'new_password'     => [
                'nullable',
                'confirmed',
                'min:12',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&^_\-]/',
            ],
        ], [
            'new_password.min'    => 'New password must be at least 12 characters.',
            'new_password.regex'  => 'New password must include uppercase, lowercase, a number, and a special character (@$!%*#?&^_-).',
        ]);

        $admin = AdminUser::findOrFail(session('admin_id'));

        if (!Hash::check($request->current_password, $admin->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $updates = [];
        $changes = [];

        $newName = trim((string) $request->name);
        $newEmail = trim((string) $request->email);

        if ($newName !== $admin->name) {
            $updates['name'] = $newName;
            $changes[] = [
                'event' => 'name_changed',
                'description' => 'Admin display name was updated from the settings page.',
                'metadata' => [
                    'old_name' => $admin->name,
                    'new_name' => $newName,
                ],
            ];
        }

        if (strcasecmp($newEmail, $admin->email) !== 0) {
            $updates['email'] = $newEmail;
            $changes[] = [
                'event' => 'email_changed',
                'description' => 'Admin email address was updated from the settings page.',
                'metadata' => [
                    'old_email' => $admin->email,
                    'new_email' => $newEmail,
                ],
            ];
        }

        if ($request->filled('new_password')) {
            $updates['password'] = Hash::make($request->new_password);
            $changes[] = [
                'event' => 'password_changed',
                'description' => 'Password was updated from the admin settings page.',
                'metadata' => [
                    'password_changed' => true,
                ],
            ];
        }

        if ($updates === []) {
            return back()->withErrors(['name' => 'Please change at least one field before saving.']);
        }

        $admin->update($updates);

        foreach ($changes as $change) {
            $this->sendSecurityAlert($change['event'], $admin, $request);
            $this->recordSecurityEvent($admin, $change['event'], $change['description'], $request, $change['metadata']);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', 'Account updated successfully. Please log in again.');
    }

    // ── Private helpers ───────────────────────────────────────

    private function sendSecurityAlert(string $event, AdminUser $admin, Request $request): void
    {
        try {
            Mail::to($admin->email)->send(new SecurityAlertMail($event, [
                'name'      => $admin->name,
                'ip'        => $request->ip(),
                'device'    => $this->parseDevice($request->userAgent() ?? ''),
                'time'      => now()->format('F j, Y \a\t g:i A (T)'),
                'login_url' => route('admin.login'),
            ]));
        } catch (\Throwable) {
            // Mail failure must never block the main flow
        }
    }

    private function recordSecurityEvent(AdminUser $admin, string $event, string $description, Request $request, array $metadata = []): void
    {
        try {
            AdminSecurityEvent::create([
                'admin_user_id' => $admin->id,
                'event'         => $event,
                'description'   => $description,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
                'metadata'      => $metadata ?: null,
            ]);
        } catch (\Throwable) {
            // Audit logging must never block login or password changes.
        }
    }

    private function parseDevice(string $ua): string
    {
        $browser = match (true) {
            str_contains($ua, 'Edg')                         => 'Edge',
            str_contains($ua, 'OPR') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome')                      => 'Chrome',
            str_contains($ua, 'Firefox')                     => 'Firefox',
            str_contains($ua, 'Safari')                      => 'Safari',
            default                                          => 'Unknown Browser',
        };

        $os = match (true) {
            str_contains($ua, 'iPhone')  => 'iPhone',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS')  => 'macOS',
            str_contains($ua, 'Linux')   => 'Linux',
            default                      => 'Unknown OS',
        };

        return "{$browser} on {$os}";
    }
}
