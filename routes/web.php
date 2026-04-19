<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MenuCategoryItemController;
use App\Http\Controllers\MenuRecipeCategoryController;
use App\Http\Controllers\MenuRecipeController;
use App\Http\Controllers\NordicRecipeController;

// ── Home (Landing Page) ──────────────────────────────────────
Route::get('/', function () {
    return view('frontend.ultrafood');
})->name('home');

Route::get('/product', function () {
    return view('frontend.standalone-product-landing');
})->name('product.landing');

// ── User-facing page (DB-driven content) ─────────────────────
Route::get('/user-dashboard', [FrontendController::class, 'userDashboard'])->name('user.dashboard');

// ── Brand pages ───────────────────────────────────────────────
Route::get('/brands/menu', [FrontendController::class, 'menuBrand'])->name('menu');
Route::get('/brands/menu/products/category/{category}', [FrontendController::class, 'menuCategoryLanding'])->name('menu.products.category');
Route::get('/brands/menu/products/{category}/{item}', [FrontendController::class, 'menuCategoryItemDetails'])->name('menu.products.item');
Route::get('/brands/menu/products/{product}', [FrontendController::class, 'menuProductDetails'])->name('menu.products.details');
Route::get('/brands/menu/recipelist', fn () => redirect()->route('menu'))->name('menu.recipelist');
Route::get('/brands/menu/recipes/{category}', [FrontendController::class, 'menuRecipeCategory'])->name('menu.recipes.category');
Route::get('/brands/menu/recipes/{category}/{recipe}', [FrontendController::class, 'menuRecipeDetail'])->name('menu.recipes.detail');
Route::get('/brands/menu/recipes/{category}/{recipe}/pdf', [FrontendController::class, 'menuRecipeDetailsPdf'])->name('menu.recipes.pdf');
Route::get('/brands/menu/category/{slug}', [FrontendController::class, 'menuCategory'])->name('menu.category');
Route::get('/brands/nordic', [FrontendController::class, 'nordicBrand'])->name('nordic');
Route::get('/brands/nordic/products', [FrontendController::class, 'nordicProducts'])->name('nordic.products');
Route::get('/brands/nordic/products/{product}', [FrontendController::class, 'nordicProductDetails'])->name('nordic.products.details');
Route::get('/brands/nordic/recipes', [FrontendController::class, 'nordicRecipes'])->name('nordic.recipes');
Route::get('/brands/nordic/recipes/{recipe}', [FrontendController::class, 'nordicRecipeDetails'])->name('nordic.recipes.details');
Route::get('/brands/nordic/recipes/{recipe}/pdf', [FrontendController::class, 'nordicRecipeDetailsPdf'])->name('nordic.recipes.pdf');

// ── Contact Form ──────────────────────────────────────────────
Route::post('/contact/send', [ContactController::class, 'send'])->name('contact.send');
Route::get('/contact/submissions/{submission}/pdf', [ContactController::class, 'submissionPdf'])
    ->middleware('signed')
    ->name('contact.submission.pdf');

// ── Admin Auth ───────────────────────────────────────────────
Route::get('/admin/login',  [AdminController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('admin.login.post');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

// ── Admin Panel (session-protected) ──────────────────────────
Route::middleware('admin.session')->prefix('admin')->name('admin.')->group(function () {

    // Default admin landing: Visual Editor
    Route::get('/', function () {
        return redirect()->route('admin.visual-editor');
    })->name('home');

    // Classic dashboard
    Route::get('/dashboard',  [AdminController::class, 'dashboard'])->name('dashboard');

    // Pages
    Route::get('/pages/{id}/edit',   [AdminController::class, 'editPage'])->name('pages.edit');
    Route::put('/pages/{id}',        [AdminController::class, 'updatePage'])->name('pages.update');
    Route::post('/pages/{id}/activate', [AdminController::class, 'setActivePage'])->name('pages.activate');

    // Media uploads
    Route::post('/media/upload',  [MediaController::class, 'store'])->name('media.upload');
    Route::put('/media/{id}',     [MediaController::class, 'update'])->name('media.update');
    Route::patch('/media/{id}',   [MediaController::class, 'setActive'])->name('media.setActive');
    Route::delete('/media/{id}',  [MediaController::class, 'destroy'])->name('media.destroy');

    // Text / product / recipe content
    Route::post('/content/save',         [ContentController::class, 'save'])->name('content.save');
    Route::post('/content/update-field', [ContentController::class, 'updateField'])->name('content.updateField');
    Route::post('/content/upload-video-field', [ContentController::class, 'uploadVideoField'])->name('content.uploadVideoField');
    Route::delete('/content/{id}',       [ContentController::class, 'destroy'])->name('content.destroy');
    Route::delete('/content-by-key',     [ContentController::class, 'destroyByKey'])->name('content.destroyByKey');

    // Contact info (phone, email, links)
    Route::post('/contact/save',    [ContactController::class, 'save'])->name('contact.save');
    Route::put('/contact/{id}',     [ContactController::class, 'update'])->name('contact.update');
    Route::delete('/contact/{id}',  [ContactController::class, 'destroy'])->name('contact.destroy');

    // Visual Editor
    Route::get('/visual-editor', [AdminController::class, 'visualEditor'])->name('visual-editor');
    Route::get('/visual-editor/contents', [AdminController::class, 'visualEditorContents'])->name('visual-editor.contents');

    Route::post('/menu-category-items', [MenuCategoryItemController::class, 'store'])->name('menu-category-items.store');
    Route::put('/menu-category-items/{id}', [MenuCategoryItemController::class, 'update'])->name('menu-category-items.update');
    Route::post('/menu-category-items/{id}/move', [MenuCategoryItemController::class, 'move'])->name('menu-category-items.move');
    Route::delete('/menu-category-items/{id}', [MenuCategoryItemController::class, 'destroy'])->name('menu-category-items.destroy');

    // Menu recipe categories + recipes (Visual Editor JSON CRUD)
    Route::post('/menu-recipe-categories', [MenuRecipeCategoryController::class, 'store'])->name('menu-recipe-categories.store');
    Route::put('/menu-recipe-categories/{id}', [MenuRecipeCategoryController::class, 'update'])->name('menu-recipe-categories.update');
    Route::delete('/menu-recipe-categories/{id}', [MenuRecipeCategoryController::class, 'destroy'])->name('menu-recipe-categories.destroy');

    Route::post('/menu-recipes', [MenuRecipeController::class, 'store'])->name('menu-recipes.store');
    Route::put('/menu-recipes/{id}', [MenuRecipeController::class, 'update'])->name('menu-recipes.update');
    Route::delete('/menu-recipes/{id}', [MenuRecipeController::class, 'destroy'])->name('menu-recipes.destroy');

    // Nordic recipes (Visual Editor JSON CRUD)
    Route::post('/nordic-recipes', [NordicRecipeController::class, 'store'])->name('nordic-recipes.store');
    Route::put('/nordic-recipes/{id}', [NordicRecipeController::class, 'update'])->name('nordic-recipes.update');
    Route::delete('/nordic-recipes/{id}', [NordicRecipeController::class, 'destroy'])->name('nordic-recipes.destroy');

    // Settings (account security)
    Route::get('/settings',           [AdminController::class, 'showSettings'])->name('settings');
    Route::post('/settings/password', [AdminController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/account',  [AdminController::class, 'updateAccountDetails'])->name('settings.account');
});
