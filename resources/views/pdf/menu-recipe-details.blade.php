<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{{ $recipe->name }} PDF</title>
  <style>
    @page { margin: 28px; }

    body {
      font-family: DejaVu Sans, sans-serif;
      color: #223947;
      font-size: 12px;
      line-height: 1.55;
    }

    h1, h2 { margin: 0; font-weight: 700; }
    .page { width: 100%; }

    .header {
      padding: 10px 16px 11px;
      border-radius: 14px;
      background-color: #9b0000;
      color: #ffffff;
      margin-bottom: 14px;
      text-align: left;
    }

    .header-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .header-left {
      vertical-align: middle;
      padding-right: 10px;
    }

    .header-right {
      width: 176px;
      vertical-align: middle;
      text-align: right;
      padding-top: 0;
    }

    .header-logo {
      width: auto;
      max-width: 168px;
      height: 62px;
      display: block;
      object-fit: contain;
      object-position: right center;
      margin-left: auto;
    }

    .kicker {
      margin: 0 0 4px;
      font-size: 11px;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: #ffd9d9;
    }

    .title {
      font-size: 23px;
      line-height: 1.16;
      margin: 0 0 4px;
      white-space: normal;
      overflow: visible;
      text-overflow: clip;
      overflow-wrap: anywhere;
      word-break: break-word;
    }

    .category {
      margin: 0 0 4px;
      font-size: 12px;
      color: #ffe7e7;
    }

    .meta {
      display: flex;
      justify-content: flex-start;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 3px;
    }

    .meta-item {
      display: inline-block;
      padding: 0;
      margin: 0;
      background: transparent;
      color: #ffffff;
      font-size: 12px;
      font-weight: 700;
      border: 0;
      border-radius: 0;
      text-align: left;
    }

    .section {
      margin-top: 16px;
      padding: 16px 18px 18px;
      border: 1px solid rgba(130, 12, 12, 0.2);
      border-radius: 14px;
      background: #ffffff;
      page-break-inside: avoid;
    }

    .section-title {
      margin-bottom: 12px;
      font-size: 18px;
      color: #820c0c;
      border-bottom: 2px solid #e6ba40;
      padding-bottom: 7px;
    }

    ul, ol { margin: 0; padding-left: 20px; }
    li { margin-bottom: 8px; page-break-inside: avoid; }
    .empty-note { margin: 0; color: #5a6770; font-style: italic; }

    .procedures-section {
      page-break-before: always;
      margin-top: 0;
      page-break-inside: auto;
    }

    .procedures-section.same-page {
      page-break-before: auto;
      margin-top: 16px;
    }
  </style>
</head>
<body>
  @php
    $pdfRecipe = $recipe ?? null;
    $pdfCategory = $category ?? null;

    $pdfName = is_object($pdfRecipe) ? ($pdfRecipe->name ?? '') : ($pdfRecipe['name'] ?? '');
    $pdfDesc = is_object($pdfRecipe) ? ($pdfRecipe->description ?? '') : ($pdfRecipe['description'] ?? '');
    $pdfServ = is_object($pdfRecipe) ? ($pdfRecipe->servings ?? '') : ($pdfRecipe['servings'] ?? '');
    $pdfCal = is_object($pdfRecipe) ? ($pdfRecipe->calories ?? '') : ($pdfRecipe['calories'] ?? '');
    $pdfCategoryName = is_object($pdfCategory) ? ($pdfCategory->name ?? '') : ($pdfCategory['name'] ?? '');

    $pdfIngredients = is_object($pdfRecipe) ? ($pdfRecipe->ingredients ?? []) : ($pdfRecipe['ingredients'] ?? []);
    $pdfProcedures = is_object($pdfRecipe) ? ($pdfRecipe->procedures ?? []) : ($pdfRecipe['procedures'] ?? []);

    $normalizeMenuMentionText = static function (string $line): string {
      $line = preg_replace_callback(
        '/\[@([^\]]+)\]\(menu-product:\d+\)/',
        static function (array $matches): string {
          return trim((string) ($matches[1] ?? ''));
        },
        $line
      ) ?? $line;

      $line = preg_replace('/\s{2,}/', ' ', $line) ?? $line;
      return trim($line);
    };

    if (is_string($pdfIngredients)) {
      $pdfIngredients = preg_split('/\r\n|\r|\n/', $pdfIngredients) ?: [];
    }
    if (is_string($pdfProcedures)) {
      $pdfProcedures = preg_split('/\r\n|\r|\n/', $pdfProcedures) ?: [];
    }

    $pdfIngredients = is_array($pdfIngredients)
      ? array_values(array_filter(array_map(
          static fn ($line) => $normalizeMenuMentionText((string) $line),
          $pdfIngredients
        ), static fn ($line) => $line !== ''))
      : [];
    $pdfProcedures = is_array($pdfProcedures)
      ? array_values(array_filter(array_map(
          static fn ($line) => $normalizeMenuMentionText((string) $line),
          $pdfProcedures
        ), static fn ($line) => $line !== ''))
      : [];

    $menuLogoDataUri = '';
    $menuLogoPath = base_path('menu-logo-header.jpg');
    if (!is_file($menuLogoPath)) {
      $menuLogoPath = public_path('images/logo/e2288ce5-75d7-4fc3-a4b2-8fb3642a811a.png');
    }
    if (is_file($menuLogoPath)) {
      $menuLogoMime = function_exists('mime_content_type') ? (mime_content_type($menuLogoPath) ?: 'image/jpeg') : 'image/jpeg';
      $menuLogoDataUri = 'data:' . $menuLogoMime . ';base64,' . base64_encode((string) file_get_contents($menuLogoPath));
    }

    $procedureOnlyScore = strlen(implode(' ', $pdfProcedures));
    $isShortRecipe = count($pdfProcedures) <= 8 && $procedureOnlyScore <= 900;
  @endphp

  <div class="page">
    <section class="header">
      <table class="header-table" role="presentation" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td class="header-left">
            <p class="kicker">Menu Food Recipe</p>
            <h1 class="title">{{ $pdfName ?: 'Recipe' }}</h1>
            @if(!empty($pdfCategoryName))
              <p class="category">Category: {{ $pdfCategoryName }}</p>
            @endif

            <div class="meta">
              @if(!empty($pdfServ))
                <span class="meta-item">Servings: {{ $pdfServ }}</span>
              @endif
              @if(!empty($pdfCal))
                <span class="meta-item">Calories: {{ $pdfCal }}</span>
              @endif
              <span class="meta-item">Ingredients: {{ count($pdfIngredients) }}</span>
              <span class="meta-item">Steps: {{ count($pdfProcedures) }}</span>
            </div>
          </td>
          <td class="header-right">
            @if($menuLogoDataUri !== '')
              <img class="header-logo" src="{{ $menuLogoDataUri }}" alt="Menu Logo">
            @endif
          </td>
        </tr>
      </table>
    </section>

    <section class="section">
      <h2 class="section-title">Description</h2>
      <p>{{ $pdfDesc ?: 'No description available.' }}</p>
    </section>

    <section class="section">
      <h2 class="section-title">Ingredients</h2>
      @if (!empty($pdfIngredients))
        <ul>
          @foreach ($pdfIngredients as $ingredient)
            <li>{{ $ingredient }}</li>
          @endforeach
        </ul>
      @else
        <p class="empty-note">No ingredients listed yet.</p>
      @endif
    </section>

    <section class="section procedures-section{{ $isShortRecipe ? ' same-page' : '' }}">
      <h2 class="section-title">Procedures</h2>
      @if (!empty($pdfProcedures))
        <ol>
          @foreach ($pdfProcedures as $procedure)
            <li>{{ $procedure }}</li>
          @endforeach
        </ol>
      @else
        <p class="empty-note">No procedures available yet.</p>
      @endif
    </section>
  </div>
</body>
</html>
