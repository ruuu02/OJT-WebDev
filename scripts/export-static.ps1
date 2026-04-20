$ErrorActionPreference = 'Stop'

$env:APP_ENV = 'production'
$env:APP_DEBUG = 'false'
$env:APP_KEY = 'base64:WzR8GYepvG3ByTSXzUoSzZrzu/w9i4NX3/P/W6Ofjrs='
$env:CACHE_STORE = 'array'
$env:SESSION_DRIVER = 'array'
$env:QUEUE_CONNECTION = 'sync'

$root = Resolve-Path (Join-Path $PSScriptRoot '..')
$staticRoot = Join-Path $root 'public/static'

if (Test-Path -LiteralPath $staticRoot) {
    Remove-Item -LiteralPath $staticRoot -Recurse -Force
}
New-Item -ItemType Directory -Path $staticRoot | Out-Null

$routes = @(
    '/',
    '/brands/menu',
    '/brands/menu/products/category/jelly-mixes',
    '/brands/menu/products/category/breading-mixes',
    '/brands/menu/products/category/powder-mixes',
    '/brands/menu/products/category/bouillon-cubes',
    '/brands/menu/products/category/noodles-and-pastas',
    '/brands/menu/products/category/noodles-pastas-and-sauces',
    '/brands/menu/products/category/powdered-drinks',
    '/brands/menu/products/category/professional-series',
    '/brands/menu/products/bouillon-cubes/17-menu-chicken-broth-cubes',
    '/brands/menu/products/bouillon-cubes/73-menu-beef-broth-cubes',
    '/brands/menu/products/bouillon-cubes/84-menu-chicken-broth-cubes-10g',
    '/brands/menu/products/bouillon-cubes/90-menu-beef-broth-cube-10g',
    '/brands/menu/products/breading-mixes/15-menu-magic-breading-mix-100g-original',
    '/brands/menu/products/breading-mixes/44-menu-magic-breading-hot-spicy',
    '/brands/menu/products/breading-mixes/45-menu-magic-breading-mix-salted-egg',
    '/brands/menu/products/breading-mixes/92-menu-magic-breading-mix-35g',
    '/brands/menu/products/jelly-mixes/21-menu-magic-jelly-clear',
    '/brands/menu/products/jelly-mixes/25-menu-magic-jelly-black',
    '/brands/menu/products/jelly-mixes/23-menu-magic-jelly-red',
    '/brands/menu/products/jelly-mixes/24-menu-magic-jelly-green',
    '/brands/menu/products/jelly-mixes/22-menu-magic-jelly-yellow',
    '/brands/menu/products/jelly-mixes/36-menu-magic-jelly-leche-plan',
    '/brands/menu/products/jelly-mixes/42-menu-magic-jelly-coffee-series-americano',
    '/brands/menu/products/jelly-mixes/35-menu-magic-jelly-buko-pandan',
    '/brands/menu/products/jelly-mixes/37-menu-magic-jelly-ube',
    '/brands/menu/products/jelly-mixes/26-menu-magic-jelly-mango',
    '/brands/menu/products/jelly-mixes/27-menu-magic-jelly-melon',
    '/brands/menu/products/jelly-mixes/38-menu-magic-jelly-buko-strips',
    '/brands/menu/products/jelly-mixes/43-menu-magic-jelly-cucumber',
    '/brands/menu/products/jelly-mixes/77-menu-magic-jelly-lychee',
    '/brands/menu/products/jelly-mixes/39-menu-magic-jelly-four-season',
    '/brands/menu/products/jelly-mixes/40-menu-magic-jelly-wintermelon',
    '/brands/menu/products/jelly-mixes/41-menu-magic-jelly-passion-fruit',
    '/brands/menu/products/noodles-and-pastas/18-menu-mamma-mia-sweet-style-spaghetti',
    '/brands/menu/products/noodles-and-pastas/55-menu-lucky-dragon-longkou-vermicelli-sotanghon-noodles-40g',
    '/brands/menu/products/noodles-and-pastas/56-menu-lucky-dragon-longkou-vermicelli-sotanghon-noodles-80g',
    '/brands/menu/products/noodles-and-pastas/57-menu-lucky-dragon-longkou-vermicelli-sotanghon-noodles-200g',
    '/brands/menu/products/noodles-and-pastas/87-menu-lucky-dragon-longkou-vermicelli-sotanghon-noodles-400g',
    '/brands/menu/products/noodles-and-pastas/88-menu-lucky-dragon-longkou-vermicelli-sotanghon-noodles-800g',
    '/brands/menu/products/noodles-and-pastas/91-menu-mamma-mia-sweet-style-spaghetti-250g',
    '/brands/menu/products/powder-mixes/51-menu-chicken-powder-mix-250g',
    '/brands/menu/products/powder-mixes/52-menu-chicken-powder-mix-8g',
    '/brands/menu/products/powder-mixes/54-menu-beef-powder-mix-250g',
    '/brands/menu/products/powder-mixes/85-menu-magic-sinigang-mix-22g',
    '/brands/menu/products/powder-mixes/86-menu-magic-sinigang-mix-11g',
    '/brands/menu/products/powder-mixes/82-menu-magic-sinigang-sa-gabi-mix-22g',
    '/brands/menu/products/powder-mixes/81-menu-magic-sinigang-sa-gabi-mix-11g',
    '/brands/menu/products/powdered-drinks/19-menu-soya-milk-powder',
    '/brands/menu/products/professional-series/59-menu-chicken-powder-mix-1kg',
    '/brands/menu/products/professional-series/65-menu-beef-powder-mix-1kg',
    '/brands/menu/products/professional-series/68-menu-tamarind-powder-mix-800g',
    '/brands/menu/products/professional-series/69-menu-shrimp-powder-mix-1kg',
    '/brands/menu/products/professional-series/70-menu-salted-egg-powder-mix-500g',
    '/brands/menu/products/professional-series/71-menu-demi-glace-powder-mix-1kg',
    '/brands/menu/recipes/appetizers',
    '/brands/menu/recipes/soups',
    '/brands/menu/recipes/main-dishes',
    '/brands/menu/recipes/noodles-and-pastas',
    '/brands/menu/recipes/drinks',
    '/brands/menu/recipes/desserts',
    '/brands/menu/recipes/appetizers/spicy-eggplant-crisps',
    '/brands/menu/recipes/appetizers/cauliflower-popcorn',
    '/brands/menu/recipes/appetizers/crispy-garlic-potatoes',
    '/brands/menu/recipes/appetizers/crispy-mushroom-bites',
    '/brands/menu/recipes/appetizers/flavored-potato-chip',
    '/brands/menu/recipes/appetizers/shrimp-summer-rolls',
    '/brands/menu/recipes/appetizers/stir-fry-crab-clusters',
    '/brands/menu/recipes/soups/filipino-pork-sinigang-sa-gabi',
    '/brands/menu/recipes/soups/salmon-belly-sinigang',
    '/brands/menu/recipes/soups/egg-drop-soup',
    '/brands/menu/recipes/soups/chicken-dumpling-soup',
    '/brands/menu/recipes/soups/chicken-corn-soup',
    '/brands/menu/recipes/soups/beef-pho-inspired-noodle-soup',
    '/brands/menu/recipes/soups/shrimp-soup-with-coconut-milk',
    '/brands/menu/recipes/soups/fish-soup-with-tomato-and-onion-sinigang-na-isda-style',
    '/brands/menu/recipes/soups/steakhouse-mushroom-soup',
    '/brands/menu/recipes/main-dishes/bip-karekare',
    '/brands/menu/recipes/main-dishes/qweq',
    '/brands/menu/recipes/main-dishes/beef-and-eggplant-in-black-bean-sauce',
    '/brands/menu/recipes/main-dishes/breaded-pork-chop',
    '/brands/menu/recipes/main-dishes/broccoli-in-garlic-sauce',
    '/brands/menu/recipes/main-dishes/chicken-and-bell-pepper-saute',
    '/brands/menu/recipes/main-dishes/chicken-curry',
    '/brands/menu/recipes/main-dishes/crispy-pork-belly-with-chili',
    '/brands/menu/recipes/main-dishes/crispy-spicy-chicken-burger-patty',
    '/brands/menu/recipes/main-dishes/garlic-fried-rice',
    '/brands/menu/recipes/main-dishes/mixed-seafood-and-vegetables',
    '/brands/menu/recipes/main-dishes/omurice-japanese-omelette-rice-with-demi-glace-sauce',
    '/brands/menu/recipes/main-dishes/pineapple-pork-bites',
    '/brands/menu/recipes/main-dishes/pork-and-kimchi-stir-fry',
    '/brands/menu/recipes/main-dishes/roast-beef-with-demi-glace-sauce',
    '/brands/menu/recipes/main-dishes/shrimp-tempura-with-spicy-mayo-drizzle',
    '/brands/menu/recipes/main-dishes/shrimp-with-bell-peppers',
    '/brands/menu/recipes/noodles-and-pastas/chicken-alfredo-pasta',
    '/brands/menu/recipes/noodles-and-pastas/creamy-chicken-mushroom-pasta',
    '/brands/menu/recipes/noodles-and-pastas/garlic-parmesan-shrimp-pasta-shrimp-powder',
    '/brands/menu/recipes/noodles-and-pastas/salted-egg-pasta-with-shrimp',
    '/brands/menu/recipes/drinks/soya-milk-latte',
    '/brands/menu/recipes/drinks/banana-soya-smoothie',
    '/brands/menu/recipes/drinks/calamansi-honey-tea',
    '/brands/menu/recipes/drinks/buko-delight-refresher',
    '/brands/menu/recipes/drinks/grass-jelly-milk-tea',
    '/brands/menu/recipes/drinks/honey-lemon-juice',
    '/brands/menu/recipes/drinks/iced-lemon-tea-with-jelly',
    '/brands/menu/recipes/drinks/kiwi-refresher-with-jelly-sinkers',
    '/brands/menu/recipes/drinks/leche-flan-milkshake',
    '/brands/menu/recipes/drinks/melon-milk',
    '/brands/menu/recipes/drinks/peach-tea-with-passion-fruit-jellies',
    '/brands/menu/recipes/drinks/pink-lychee-bliss-shots',
    '/brands/menu/recipes/drinks/tropical-juice-jelly',
    '/brands/menu/recipes/drinks/watermelon-gulaman-cooler',
    '/brands/menu/recipes/drinks/wintermelon-milk-tea-sinkers',
    '/brands/menu/recipes/desserts/lychee-lush-slice',
    '/brands/menu/recipes/desserts/mango-jello-milk-pudding',
    '/brands/menu/recipes/desserts/mango-tapioca',
    '/brands/menu/recipes/desserts/mini-leche-flan-graham-cake',
    '/brands/menu/recipes/desserts/passion-fruit-slice-jelly-cake',
    '/brands/menu/recipes/desserts/tiramisu-jelly',
    '/brands/menu/recipes/desserts/ube-ice-cream-no-churn-jelly-style',
    '/brands/menu/recipes/desserts/classic-pinoy-buko-salad',
    '/brands/menu/recipes/desserts/classic-pinoy-buko-salad-2',
    '/brands/menu/recipes/desserts/lychee-rose-jelly-cups',
    '/brands/menu/recipes/desserts/ube-milk-jelly-taho',
    '/brands/nordic',
    '/brands/nordic/products',
    '/brands/nordic/products/whole-grain-oats',
    '/brands/nordic/products/instant-oats',
    '/brands/nordic/products/quick-cook-oats',
    '/brands/nordic/recipes',
    '/brands/nordic/recipes/oat-congee',
    '/brands/nordic/recipes/oat-soup',
    '/brands/nordic/recipes/oat-fried-rice',
    '/brands/nordic/recipes/basic-overnight-oats',
    '/brands/nordic/recipes/banana-peanut-butter-oats',
    '/brands/nordic/recipes/apple-cinnamon-oatmeal',
    '/brands/nordic/recipes/oatmeal-cookies',
    '/brands/nordic/recipes/oat-brownies',
    '/brands/nordic/recipes/oat-pancakes',
    '/brands/nordic/recipes/oat-waffles',
    '/brands/nordic/recipes/garlic-mushroom-oats',
    '/brands/nordic/recipes/egg-spinach-oats',
    '/brands/nordic/recipes/chicken-oat-porridge',
    '/brands/nordic/recipes/tomato-basil-savory-oats',
    '/brands/nordic/recipes/chocolate-cocoa-oats',
    '/brands/nordic/recipes/mango-coconut-oats',
    '/brands/nordic/recipes/strawberry-yogurt-oats'
)

$port = 8114
$out = Join-Path $root 'storage/logs/static-export.out.log'
$err = Join-Path $root 'storage/logs/static-export.err.log'
$server = Start-Process -FilePath php -ArgumentList @('-S', "127.0.0.1:$port", '-t', 'public') -WorkingDirectory $root -RedirectStandardOutput $out -RedirectStandardError $err -PassThru

try {
    Start-Sleep -Seconds 3

    foreach ($route in $routes) {
        $relative = if ($route -eq '/') { 'index.html' } else { $route.TrimStart('/') + '/index.html' }
        $dest = Join-Path $staticRoot $relative
        $dir = Split-Path $dest -Parent

        if (-not (Test-Path -LiteralPath $dir)) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
        }

        $url = "http://127.0.0.1:$port$route"
        $status = & curl.exe -L -s -o $dest -w '%{http_code}' --max-time 30 $url
        if ($status -ne '200') {
            throw "Export failed with HTTP $status for $route"
        }

        $html = Get-Content -LiteralPath $dest -Raw
        $html = $html -replace "http://127\.0\.0\.1:$port", ''
        $html = $html -replace "https://127\.0\.0\.1:$port", ''
        $html = $html -replace "http:\\/\\/127\.0\.0\.1:$port", ''
        $html = $html -replace "https:\\/\\/127\.0\.0\.1:$port", ''
        Set-Content -LiteralPath $dest -Value $html -Encoding UTF8

        Write-Host "exported $route"
    }
}
finally {
    if ($server -and -not $server.HasExited) {
        Stop-Process -Id $server.Id -Force
    }
}
