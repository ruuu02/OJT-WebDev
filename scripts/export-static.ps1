$ErrorActionPreference = 'Stop'

$env:APP_ENV = 'production'
$env:APP_DEBUG = 'false'
$env:APP_KEY = 'base64:WzR8GYepvG3ByTSXzUoSzZrzu/w9i4NX3/P/W6Ofjrs='
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = 'database/database.sqlite'
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
    '/brands/menu/products/category/powdered-drinks',
    '/brands/menu/products/category/professional-series',
    '/brands/menu/products/jelly-mixes/1',
    '/brands/menu/products/jelly-mixes/1-mango-jelly-mix-90-g',
    '/brands/menu/products/breading-mixes/2',
    '/brands/menu/products/breading-mixes/2-crispy-breading-mix-200-g',
    '/brands/menu/products/powder-mixes/3',
    '/brands/menu/products/powder-mixes/3-all-purpose-powder-mix-250-g',
    '/brands/menu/products/bouillon-cubes/4',
    '/brands/menu/products/bouillon-cubes/4-chicken-broth-cubes-10-g',
    '/brands/menu/products/noodles-and-pastas/5',
    '/brands/menu/products/noodles-and-pastas/5-mamma-mia-spaghetti-400-g',
    '/brands/menu/products/powdered-drinks/6',
    '/brands/menu/products/powdered-drinks/6-iced-tea-powdered-drink-250-g',
    '/brands/menu/products/professional-series/7',
    '/brands/menu/products/professional-series/7-kitchen-series-mix-1-kg',
    '/brands/menu/recipes/appetizers',
    '/brands/menu/recipes/soups',
    '/brands/menu/recipes/main-dishes',
    '/brands/menu/recipes/noodles-and-pastas',
    '/brands/menu/recipes/drinks',
    '/brands/menu/recipes/desserts',
    '/brands/menu/recipes/appetizers/crispy-party-bites',
    '/brands/menu/recipes/soups/comfort-broth-soup',
    '/brands/menu/recipes/main-dishes/homestyle-saucy-chicken',
    '/brands/menu/recipes/noodles-and-pastas/merienda-spaghetti',
    '/brands/menu/recipes/drinks/chilled-fruit-drink',
    '/brands/menu/recipes/desserts/layered-jelly-cups',
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
        Set-Content -LiteralPath $dest -Value $html -Encoding UTF8

        Write-Host "exported $route"
    }
}
finally {
    if ($server -and -not $server.HasExited) {
        Stop-Process -Id $server.Id -Force
    }
}
