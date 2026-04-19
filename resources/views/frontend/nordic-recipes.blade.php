<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  @php
    use App\Models\Page;
    use Illuminate\Support\Facades\DB;

    $sharedNordicPageId = Page::where('slug', 'nordic')->value('id');
    $nordicPageBgUrl = 'https://images.unsplash.com/photo-1517673400267-0251440c45dc?w=2200&q=80&auto=format&fit=crop';

    if ($sharedNordicPageId) {
      $mediaId = trim((string) DB::table('text_content')
        ->where('page_id', $sharedNordicPageId)
        ->where('key', 'media_id_nordic_page_bg')
        ->value('value'));

      if ($mediaId !== '') {
        $filename = DB::table('product_images')
          ->where('id', $mediaId)
          ->value('filename');

        if ($filename) {
          $nordicPageBgUrl = asset('images/banner/'.$filename);
        }
      }
    }
  @endphp
  <title>Korpala Nordic - Recipes</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo/Korpala-Nordic-Logo.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Farro:wght@700;800&family=Fira+Sans:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/nordic/nordic.css') }}?v={{ filemtime(public_path('css/nordic/nordic.css')) ?: 0 }}" />
  <link rel="stylesheet" href="{{ asset('css/nordic/nordic-recipes.css') }}?v={{ filemtime(public_path('css/nordic/nordic-recipes.css')) ?: 0 }}" />
  <style>
    :root {
      --nordic-page-bg: url("{{ $nordicPageBgUrl }}");
    }
  </style>
</head>
<body>
  @php
    $isVisualEditor = !empty($isVisualEditor);
    $recipes = $recipes ?? collect();
    if (is_array($recipes)) {
      $recipes = collect($recipes);
    }
    if (!($recipes instanceof \Illuminate\Support\Collection)) {
      $recipes = collect();
    }
    $hasRecipes = $recipes->isNotEmpty();
  @endphp

  @include('frontend.partials.nordic-header')

  <main class="nordic-recipes-page">
    <section class="recipes-breadcrumb-wrap" aria-label="Breadcrumb">
      <p class="recipes-breadcrumb">
        <a href="{{ route('nordic') }}">Home</a>
        <span aria-hidden="true">&gt;</span>
        <a href="{{ route('nordic.recipes') }}" class="is-current" aria-current="page">Nordic Recipes</a>
      </p>
    </section>

    <section class="recipes-grid-section" aria-label="Nordic recipe cards">
      <div class="recipes-grid" id="nordicRecipesGrid" data-is-visual="{{ $isVisualEditor ? '1' : '0' }}">
        @if($isVisualEditor && !$hasRecipes)
          <article class="recipe-card ve-nordic-add-recipe" role="button" tabindex="0" aria-label="Add recipe">
            <div class="recipe-card__link recipe-card__link--add">
              <div class="recipe-card__media">
                <div class="recipe-card__media-frame recipe-card__media-frame--add">
                  <span class="recipe-card__add-icon">+</span>
                </div>
              </div>
              <h2 class="recipe-card__name">Add recipe</h2>
            </div>
          </article>
        @endif

        @foreach ($recipes as $recipe)
          @php
            $rid = is_object($recipe) ? ($recipe->id ?? null) : ($recipe['id'] ?? null);
            $rname = is_object($recipe) ? ($recipe->name ?? '') : ($recipe['name'] ?? '');
            $rslug = is_object($recipe) ? ($recipe->slug ?? '') : ($recipe['slug'] ?? '');
            $rdesc = is_object($recipe) ? ($recipe->description ?? '') : ($recipe['description'] ?? '');
            $rdesc = trim((string) $rdesc);
            $img = is_object($recipe)
              ? ($recipe->image_filename ? asset('images/nordic/recipes/'.$recipe->image_filename) : null)
              : ($recipe['image'] ?? null);
            $href = $rslug ? route('nordic.recipes.details', ['recipe' => $rslug]) : route('nordic.recipes');
          @endphp
          <article
            class="recipe-card"
            style="--delay: {{ $loop->index }}"
            data-recipe-id="{{ $rid }}"
            data-recipe-slug="{{ $rslug }}"
          >
            @if($isVisualEditor)
              <button type="button" class="ve-nordic-edit-recipe" aria-label="Edit recipe">Edit</button>
              <button type="button" class="ve-nordic-del-recipe" aria-label="Delete recipe" data-id="{{ $rid }}">&times;</button>
            @endif
            <a class="recipe-card__link" href="{{ $href }}" aria-label="View {{ $rname }}">
              <div class="recipe-card__media">
                <div class="recipe-card__media-frame">
                  @if($img)
                    <img
                      src="{{ $img }}"
                      alt="{{ $rname }}"
                      loading="lazy"
                    />
                  @endif
                </div>
              </div>
              <h2 class="recipe-card__name">{{ $rname }}</h2>
              <span class="recipe-card__cta">Explore recipe <span aria-hidden="true">&rarr;</span></span>
            </a>
          </article>
        @endforeach

        @if($isVisualEditor && $hasRecipes)
          <article class="recipe-card ve-nordic-add-recipe" role="button" tabindex="0" aria-label="Add recipe">
            <div class="recipe-card__link recipe-card__link--add">
              <div class="recipe-card__media">
                <div class="recipe-card__media-frame recipe-card__media-frame--add">
                  <span class="recipe-card__add-icon">+</span>
                </div>
              </div>
              <h2 class="recipe-card__name">Add recipe</h2>
            </div>
          </article>
        @endif
      </div>
    </section>
  </main>

  @include('frontend.partials.nordic-footer')

  <button id="backToTop" aria-label="Back to top"
    style="
      --btt-bg: #e6ba40;
      --btt-fg: #223947;
      --btt-hover-bg: #f0cf71;
    ">
    &#8593;
  </button>

  <script src="{{ asset('js/nordic/nordic-recipes.js') }}"></script>
</body>
</html>
