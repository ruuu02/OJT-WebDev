# Ultrafood - Project Structure (Laravel)

Laravel 12 application (PHP 8.2) for Ultrafood brand sites + admin CMS/Visual Editor.

High-level notes:
- Backend: Laravel + Composer (`composer.json`), PDF generation via `barryvdh/laravel-dompdf`.
- Frontend: Blade templates + static CSS/JS served from `public/`.
- No front-end build tooling checked in: there is no `package.json` in this repo (even though `composer.json` contains scripts that mention `npm`).
- Content is database-driven (migrations + seeders under `database/`). Admin UI updates records via JSON endpoints.

---

## Directory Tree (curated)

This is the full project layout at a useful level of detail (large/generated folders are collapsed).

```
ultrafood/
|-- app/
|   |-- Http/
|   |   |-- Controllers/
|   |   |   |-- AdminController.php                 # Admin auth, dashboard, visual editor, settings
|   |   |   |-- ContactController.php               # Contact info CRUD + contact form submissions/email/PDF
|   |   |   |-- ContentController.php               # Text/product/recipe content CRUD + video upload fields
|   |   |   |-- FrontendController.php              # Public pages, menu/nordic views, PDFs
|   |   |   |-- MediaController.php                 # Image upload/update/delete (carousel/logo/product/recipe/menu)
|   |   |   |-- MenuCategoryItemController.php      # Menu category line items CRUD + ordering
|   |   |   |-- MenuRecipeCategoryController.php    # Menu recipe categories CRUD
|   |   |   |-- MenuRecipeController.php            # Menu recipes CRUD
|   |   |   |-- NordicRecipeController.php          # Nordic recipes CRUD
|   |   |   `-- Controller.php                      # Base controller
|   |   |-- Middleware/
|   |   |   `-- AdminSessionMiddleware.php          # Protects /admin routes + security headers
|   |   `-- Requests/
|   |       `-- UpdatePageRequest.php               # Form validation for page updates
|   |-- Models/
|   |   |-- AdminUser.php                           # Admin users (DB-backed)
|   |   |-- MenuCategoryLineItem.php                # Menu products per-category rows (DB-backed)
|   |   |-- MenuRecipe.php                          # Menu recipes (DB-backed)
|   |   |-- MenuRecipeCategory.php                  # Menu recipe categories (DB-backed)
|   |   |-- NordicRecipe.php                        # Nordic recipes (DB-backed)
|   |   `-- Page.php                                # CMS pages / slugs / active page
|   |-- Providers/
|   |   `-- AppServiceProvider.php
|   `-- Services/                                   # Currently empty (placeholder)
|
|-- bootstrap/
|   |-- app.php                                     # App bootstrap + middleware alias (admin.session)
|   |-- providers.php
|   `-- cache/                                      # Generated cache files (collapsed)
|
|-- config/
|   |-- app.php
|   |-- auth.php
|   |-- cache.php
|   |-- cms.php                                     # Visual Editor page/zones/fields config (large)
|   |-- database.php
|   |-- filesystems.php
|   |-- logging.php
|   |-- mail.php
|   |-- menu-images.php                             # Menu placeholder images / defaults
|   |-- queue.php
|   |-- services.php
|   `-- session.php
|
|-- database/
|   |-- factories/
|   |   `-- UserFactory.php
|   |-- migrations/
|   |   |-- 2026_02_24_000001_create_admin_users_table.php
|   |   |-- 2026_02_24_000002_create_pages_table.php
|   |   |-- 2026_02_24_000003_create_carousel_images_table.php
|   |   |-- 2026_02_24_000004_create_logos_table.php
|   |   |-- 2026_02_24_000005_create_product_images_table.php
|   |   |-- 2026_02_24_000006_create_recipe_images_table.php
|   |   |-- 2026_02_24_000007_create_text_content_table.php
|   |   |-- 2026_02_24_000008_create_product_details_table.php
|   |   |-- 2026_02_24_000009_create_featured_recipes_table.php
|   |   |-- 2026_02_24_000010_create_contact_numbers_table.php
|   |   |-- 2026_02_24_000011_create_email_addresses_table.php
|   |   |-- 2026_02_24_000012_create_links_table.php
|   |   |-- 2026_03_13_000001_ultrafood_footer_keys_split.php
|   |   |-- 2026_03_14_000001_create_menu_category_line_items_table.php
|   |   |-- 2026_03_17_000001_create_menu_images_table.php
|   |   |-- 2026_03_17_000010_create_menu_recipe_categories_table.php
|   |   |-- 2026_03_17_000011_create_menu_recipes_table.php
|   |   |-- 2026_03_18_000001_create_nordic_recipes_table.php
|   |   |-- 2026_03_19_000001_add_flavor_type_to_menu_category_line_items_table.php
|   |   |-- 2026_03_26_000001_add_back_image_filename_to_menu_category_line_items_table.php
|   |   |-- 2026_03_31_120000_add_difficulty_to_menu_recipes_table.php
|   |   |-- 2026_04_01_000001_create_contact_form_submissions_table.php
|   |   `-- 2026_04_03_000002_add_nutrition_fields_to_nordic_recipes_table.php
|   `-- seeders/
|       |-- AdminUserSeeder.php
|       |-- DatabaseSeeder.php
|       |-- ExistingResourcesSeeder.php
|       |-- MenuCategoryCalloutSeeder.php
|       |-- NordicRecipesSeeder.php
|       `-- PageSeeder.php
|
|-- docs/
|   `-- MENU_ITEM_DETAIL_KEYS.md
|
|-- public/
|   |-- css/
|   |   |-- admin/                                  # Admin UI CSS
|   |   |-- menu/                                   # MENU brand CSS
|   |   |-- nordic/                                 # NORDIC brand CSS
|   |   |-- shared/                                 # Shared components CSS
|   |   `-- ultrafood/                              # ULTRAFOOD brand CSS
|   |-- js/
|   |   |-- admin/                                  # Admin UI JS (dashboard + visual editor)
|   |   |-- nordic/                                 # Nordic page-specific JS
|   |   |-- app.js
|   |   |-- menu.js
|   |   |-- nordic.js
|   |   `-- ultrafood.js
|   |-- images/
|   |   |-- banner/                                 # Hero/carousel/product images
|   |   |-- favicon/
|   |   |-- icons/
|   |   |-- logo/
|   |   |-- menu/
|   |   |-- nordic/
|   |   `-- ultrafood/
|   |-- .htaccess
|   |-- .user.ini
|   |-- favicon.ico
|   |-- index.php                                   # Laravel front controller
|   `-- robots.txt
|
|-- reports/
|   `-- change-*.txt                                # Change history/summary snapshots
|
|-- resources/
|   |-- css/                                        # Optional "source" CSS (not built by tooling here)
|   |-- js/                                         # Optional "source" JS (not built by tooling here)
|   `-- views/
|       |-- admin/
|       |   |-- dashboard.blade.php
|       |   |-- edit_page.blade.php
|       |   |-- login.blade.php
|       |   |-- settings.blade.php
|       |   `-- visual-editor.blade.php
|       |-- frontend/
|       |   |-- ultrafood.blade.php
|       |   |-- menu.blade.php
|       |   |-- menu-product-catalog.blade.php
|       |   |-- menu-category-landing.blade.php
|       |   |-- menu-category-item-detail.blade.php
|       |   |-- menu-product-detail.blade.php
|       |   |-- menu-recipe-category.blade.php
|       |   |-- menu-recipe-detail.blade.php
|       |   |-- nordic.blade.php
|       |   |-- nordic-products.blade.php
|       |   |-- nordic-product-details.blade.php
|       |   |-- nordic-recipes.blade.php
|       |   |-- nordic-recipe-details.blade.php
|       |   |-- menu-header.blade.php
|       |   |-- menu-footer.blade.php
|       |   |-- menu-bg-image.blade.php
|       |   |-- contact-form-submission-email.blade.php
|       |   `-- partials/
|       |       |-- connect-with-us.blade.php
|       |       |-- connect-with-us-menu.blade.php
|       |       |-- nordic-header.blade.php
|       |       `-- nordic-footer.blade.php
|       |-- layouts/
|       |   `-- app.blade.php
|       `-- pdf/
|           |-- contact-inquiry.blade.php
|           |-- menu-recipe-details.blade.php
|           `-- nordic-recipe-details.blade.php
|
|-- routes/
|   |-- web.php                                     # All HTTP routes (public + admin)
|   `-- console.php
|
|-- storage/                                        # Laravel runtime (sessions/cache/logs + app/public)
|-- tests/
|   |-- Feature/ExampleTest.php
|   |-- Unit/ExampleTest.php
|   `-- TestCase.php
|
|-- vendor/                                         # Composer dependencies (large)
|
|-- .cursor/                                        # Editor tooling (Cursor) (collapsed)
|-- .git/                                           # Git metadata (collapsed)
|
|-- .env
|-- .env.example
|-- .editorconfig
|-- .gitattributes
|-- .gitignore
|-- .phpunit.result.cache
|-- artisan
|-- composer.json
|-- composer.lock
|-- phpunit.xml
|
|-- PROJECT_STRUCTURE.md
|-- VISUAL_EDITOR_PLAN.md
|-- VISUAL_IMPLEMENT.md
|-- render_email_preview.php                         # Generates a signed-link email preview HTML
|-- menu-logo-header.jpg
|-- menu-logo-watermark.jpg
|-- nordic-logo-header.jpg
|-- nordic-logo-watermark.jpg
|-- temp_test.txt
`-- contact-form-submission-email-preview.html       # Static HTML mock email preview
```

---

## Routes (`routes/web.php`)

### Public pages

| Method | URI | Action |
|---|---|---|
| GET | `/` | `view('frontend.ultrafood')` (home) |
| GET | `/user-dashboard` | `FrontendController@userDashboard` |
| GET | `/brands/menu` | `FrontendController@menuBrand` |
| GET | `/brands/menu/products/category/{category}` | `FrontendController@menuCategoryLanding` |
| GET | `/brands/menu/products/{category}/{item}` | `FrontendController@menuCategoryItemDetails` |
| GET | `/brands/menu/products/{product}` | `FrontendController@menuProductDetails` |
| GET | `/brands/menu/recipes/{category}` | `FrontendController@menuRecipeCategory` |
| GET | `/brands/menu/recipes/{category}/{recipe}` | `FrontendController@menuRecipeDetail` |
| GET | `/brands/menu/recipes/{category}/{recipe}/pdf` | `FrontendController@menuRecipeDetailsPdf` |
| GET | `/brands/nordic` | `FrontendController@nordicBrand` |
| GET | `/brands/nordic/products` | `FrontendController@nordicProducts` |
| GET | `/brands/nordic/products/{product}` | `FrontendController@nordicProductDetails` |
| GET | `/brands/nordic/recipes` | `FrontendController@nordicRecipes` |
| GET | `/brands/nordic/recipes/{recipe}` | `FrontendController@nordicRecipeDetails` |
| GET | `/brands/nordic/recipes/{recipe}/pdf` | `FrontendController@nordicRecipeDetailsPdf` |
| POST | `/contact/send` | `ContactController@send` |
| GET | `/contact/submissions/{submission}/pdf` | `ContactController@submissionPdf` (middleware: `signed`) |

### Admin auth + admin panel

| Method | URI | Middleware | Action |
|---|---|---|---|
| GET | `/admin/login` | - | `AdminController@showLogin` |
| POST | `/admin/login` | `throttle:5,1` | `AdminController@login` |
| POST | `/admin/logout` | - | `AdminController@logout` |

Admin routes are grouped under `Route::middleware('admin.session')->prefix('admin')->name('admin.')`:
- `GET /admin` redirects to `admin.visual-editor`
- Classic dashboard + page activation/edit/update
- Media upload/update/delete + "set active"
- Content + contact info JSON endpoints
- Visual Editor data endpoints
- Menu/Nordic JSON CRUD endpoints (items, recipes, categories)
- Settings + password change

---

## Key conventions / data flow

- Admin protection is session-based via the `admin.session` middleware alias in `bootstrap/app.php`.
- The Visual Editor configuration lives in `config/cms.php` (page registry + zones + CSS selectors + default values).
- Admin writes content directly to DB tables (not Eloquent in many places) via controller endpoints in `app/Http/Controllers/`.
- Uploaded images land in `public/images/<section>/...` (actual subdir mapping is defined in `MediaController`).
- PDFs are served from `resources/views/pdf/*.blade.php` and rendered via `barryvdh/laravel-dompdf`.

---

## Utility files

- `render_email_preview.php` boots Laravel and writes an HTML preview to `storage/app/public/contact-email-preview.html`.
- `contact-form-submission-email-preview.html` is a static HTML reference layout for the inquiry email.

---

## Notes (current repo state)

- `GET /product` in `routes/web.php` renders `frontend.standalone-product-landing`, but `resources/views/frontend/standalone-product-landing.blade.php` is not present.
- `AdminController` references `App\\Mail\\SecurityAlertMail`, but there is no `app/Mail/` directory in this repo.
