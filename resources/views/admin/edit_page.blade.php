<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit – {{ $page->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>* { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">

<nav class="bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center">
    <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:underline text-sm">← Back to Dashboard</a>
    <div class="flex items-center gap-4">
        <span class="text-sm text-gray-500">{{ session('admin_email') }}</span>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button class="text-sm text-red-500 hover:underline">Logout</button>
        </form>
    </div>
</nav>

<main class="max-w-3xl mx-auto px-6 py-10">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $page->title }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Slug: <code class="bg-gray-100 px-1 rounded">/{{ $page->slug }}</code>
                @if ($page->is_active)
                    <span class="ml-2 bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Live</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Text Content --}}
    <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
        <h3 class="font-semibold text-gray-700 mb-4">Page Content</h3>

        <form method="POST" action="{{ route('admin.pages.update', $page->id) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Page Title</label>
                <input type="text" name="title" value="{{ old('title', $page->title) }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hero Heading</label>
                <input type="text" name="hero_heading" value="{{ old('hero_heading', $page->hero_heading) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hero Subtext</label>
                <textarea name="hero_subtext" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >{{ old('hero_subtext', $page->hero_subtext) }}</textarea>
            </div>

            <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                Save Changes
            </button>
        </form>
    </section>

    {{-- Set as Live --}}
    @if (!$page->is_active)
    <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="font-semibold text-gray-700 mb-2">Set as Live Homepage</h3>
        <p class="text-sm text-gray-500 mb-4">
            This will deactivate all other variants and make <strong>{{ $page->title }}</strong> the public homepage.
        </p>
        <form method="POST" action="{{ route('admin.pages.activate', $page->id) }}">
            @csrf
            <button type="submit"
                    class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition">
                Set as Live
            </button>
        </form>
    </section>
    @endif

</main>

</body>
</html>
