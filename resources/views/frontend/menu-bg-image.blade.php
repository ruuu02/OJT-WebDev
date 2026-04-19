@php
  use App\Models\Page;
  use Illuminate\Support\Facades\DB;

  $menuBgDefaultSrc = asset('images/menu/Menu-Background-New.png');
  $menuBgSrc = $menuBgDefaultSrc;

  $menuBgPageId = (int) (Page::where('slug', 'menu')->value('id') ?? 0);
  if ($menuBgPageId > 0) {
    $menuBgImageId = trim((string) DB::table('text_content')
      ->where('page_id', $menuBgPageId)
      ->where('key', 'media_id_menu_bg_image')
      ->value('value'));

    if ($menuBgImageId !== '') {
      $menuBgFilename = (string) (DB::table('menu_images')
        ->where('id', (int) $menuBgImageId)
        ->value('filename') ?? '');
      if ($menuBgFilename !== '') {
        $menuBgSrc = asset('images/menu/' . ltrim($menuBgFilename, '/'));
      }
    }
  }
@endphp

<img src="{{ $menuBgSrc }}" alt="Menu Background" class="menu-bg-image" />

