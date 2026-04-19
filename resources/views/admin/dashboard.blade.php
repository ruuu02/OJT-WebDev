<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Content Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin/dashboard.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-100 min-h-screen">

{{-- ── TOP NAV ─────────────────────────────────────────────────────── --}}
<header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <span class="text-lg font-semibold text-gray-800">Admin</span>
    </div>

    <div class="flex items-center gap-3">
        <div class="dropdown">
            <button onclick="toggleDropdown()"
                    class="flex items-center gap-2 border border-gray-300 rounded px-3 py-1.5 text-sm bg-white hover:bg-gray-50 transition">
                <span class="text-gray-600">Account</span>
                <svg class="chevron w-3.5 h-3.5" id="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="{{ route('admin.settings') }}"
                   class="block w-full text-left px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-100">
                    Settings
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                            class="block w-full text-left px-3 py-1.5 text-sm text-red-600 hover:bg-red-50">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

{{-- ── PAGE TITLE BAR ───────────────────────────────────────────────── --}}
<div class="bg-gray-200 border-b border-gray-300 px-6 py-3 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <h1 class="text-xl font-semibold text-gray-800">Content Management</h1>
        @php $livePage = $pages->firstWhere('is_active', true); @endphp
        @if($livePage)
            <span class="text-xs text-gray-500 bg-white border border-gray-300 rounded px-2 py-1">
                Live page: <strong class="text-gray-800">{{ $livePage->title }}</strong>
            </span>
        @else
            <span class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1">
                No live page set
            </span>
        @endif
    </div>
    @if(session('success'))
        <span class="text-sm text-green-700 bg-green-100 border border-green-200 px-3 py-1 rounded-full">
            {{ session('success') }}
        </span>
    @endif
</div>

{{-- Pass page data to JS --}}
<script>
    const PAGES = @json($pages->map(fn($p) => ['id' => $p->id, 'slug' => $p->slug, 'title' => $p->title]));
    const ALL_MEDIA = @json($allMedia);
    const ALL_CONTENTS = @json($allContents);
    const ALL_CONTACTS = @json($allContacts);
    const ROUTES = {
        mediaUpload:  "{{ route('admin.media.upload') }}",
        mediaUpdate:  "{{ url('admin/media') }}",
        mediaDestroy: "{{ url('admin/media') }}",
        contentSave:        "{{ route('admin.content.save') }}",
        contentUpdateField: "{{ route('admin.content.updateField') }}",
        contentDestroy:     "{{ url('admin/content') }}",
        contentDestroyByKey: "{{ route('admin.content.destroyByKey') }}",
        contactSave:    "{{ route('admin.contact.save') }}",
        contactUpdate:  "{{ url('admin/contact') }}",
        contactDestroy: "{{ url('admin/contact') }}",
    };
</script>

{{-- ── MAIN LAYOUT ──────────────────────────────────────────────────── --}}
{{-- overflow-hidden on the wrapper locks the total height to the viewport.
     Each panel then scrolls independently via overflow-y-auto. --}}
<div class="flex gap-0 h-[calc(100vh-105px)] overflow-hidden">

    {{-- LEFT PANEL — scrolls on its own --}}
    <aside class="w-80 min-w-[18rem] flex-shrink-0 bg-white border-r border-gray-200 p-4 overflow-y-auto">

        @php
            $svgPencil = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-1.414a2 2 0 01.586-1.414z"/></svg>';
            $imageSections = [
                'carousel' => ['label' => 'Carousel Banner Images',  'desc' => 'Hero slideshow photos shown at the top of the page'],
                'logo'     => ['label' => 'Logos, Favicon & Icons',  'desc' => 'Header & footer logo, browser tab favicon'],
                'product'  => ['label' => 'Product Images',          'desc' => 'Photo displayed in the About Us section'],
                'recipe'   => ['label' => 'Recipes Images',          'desc' => 'Images used in the Mission & Vision section'],
            ];
            $textSections = [
                'text_content'    => ['label' => 'Text Content',      'desc' => 'All headings, paragraphs & labels — About, History, Brands, Strengths, Contact, Footer…'],
                'product_details' => ['label' => 'Product Details',   'desc' => 'Individual product information cards'],
                'featured_recipes'=> ['label' => 'Featured Recipes',  'desc' => 'Recipe cards displayed on the page'],
            ];
            $contactSections = [
                'phone' => ['label' => 'Contact Numbers', 'desc' => 'Phone numbers shown in Contact section & Footer'],
                'email' => ['label' => 'Email Address',   'desc' => 'Email addresses shown in Contact section & Footer'],
                'link'  => ['label' => 'Links',           'desc' => 'External links shown in the Footer'],
            ];
        @endphp

        {{-- ── Live Page Selector ─────────────────────────────── --}}
        <div class="section-group mb-1">
            <div class="section-header">Live Page (User-Facing)</div>
            @foreach($pages as $page)
            <div class="flex items-center justify-between px-3 py-2 rounded-lg mb-1
                        {{ $page->is_active ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200' }}">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2 h-2 rounded-full flex-shrink-0
                                 {{ $page->is_active ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                    <span class="text-sm font-medium truncate
                                 {{ $page->is_active ? 'text-green-800' : 'text-gray-700' }}">
                        {{ $page->title }}
                    </span>
                </div>
                @if($page->is_active)
                    <span class="text-[0.6rem] font-bold uppercase tracking-wide text-green-700 bg-green-100 px-2 py-0.5 rounded-full flex-shrink-0">
                        Live
                    </span>
                @else
                    <form method="POST" action="{{ route('admin.pages.activate', $page->id) }}">
                        @csrf
                        <button type="submit"
                                class="text-[0.6rem] font-bold uppercase tracking-wide text-gray-500 bg-white border border-gray-300 px-2 py-0.5 rounded-full hover:bg-gray-800 hover:text-white hover:border-gray-800 transition flex-shrink-0">
                            Set Live
                        </button>
                    </form>
                @endif
            </div>
            @endforeach
        </div>

        <div class="border-t border-gray-200 my-3"></div>

        {{-- Image Sections --}}
        @foreach($imageSections as $sectionKey => $section)
        <div class="section-group">
            <div class="section-header">
                {{ $section['label'] }}
                <span class="block text-[0.6rem] font-normal text-gray-400 mt-0.5 leading-snug">{{ $section['desc'] }}</span>
            </div>
            @foreach($pages as $page)
            <div class="section-row {{ ($loop->first && $sectionKey === 'carousel') ? 'active' : '' }}"
                 onclick="selectRow(this, '{{ $sectionKey }}', {{ $page->id }}, '{{ $page->title }}')">
                <span class="flex items-center gap-1.5 min-w-0">
                    {{ $page->title }}
                    @if($page->is_active)
                        <span class="flex-shrink-0 text-[0.55rem] font-bold uppercase tracking-wide text-green-700 bg-green-100 border border-green-200 px-1.5 py-0.5 rounded-full leading-none">Live</span>
                    @endif
                </span>
                <button class="change-btn" onclick="openUploadModal(event, '{{ $sectionKey }}', {{ $page->id }}, '{{ $page->title }}')">
                    Change {!! $svgPencil !!}
                </button>
            </div>
            @endforeach
        </div>
        @endforeach

        {{-- Text Sections --}}
        @foreach($textSections as $sectionKey => $section)
        <div class="section-group">
            <div class="section-header">
                {{ $section['label'] }}
                <span class="block text-[0.6rem] font-normal text-gray-400 mt-0.5 leading-snug">{{ $section['desc'] }}</span>
            </div>
            @foreach($pages as $page)
            <div class="section-row"
                 onclick="selectRow(this, '{{ $sectionKey }}', {{ $page->id }}, '{{ $page->title }}')">
                <span class="flex items-center gap-1.5 min-w-0">
                    {{ $page->title }}
                    @if($page->is_active)
                        <span class="flex-shrink-0 text-[0.55rem] font-bold uppercase tracking-wide text-green-700 bg-green-100 border border-green-200 px-1.5 py-0.5 rounded-full leading-none">Live</span>
                    @endif
                </span>
                <button class="change-btn" onclick="selectRow(this.closest('.section-row'), '{{ $sectionKey }}', {{ $page->id }}, '{{ $page->title }}')">
                    Edit {!! $svgPencil !!}
                </button>
            </div>
            @endforeach
        </div>
        @endforeach

        {{-- Contact Sections --}}
        @foreach($contactSections as $sectionKey => $section)
        <div class="section-group">
            <div class="section-header">
                {{ $section['label'] }}
                <span class="block text-[0.6rem] font-normal text-gray-400 mt-0.5 leading-snug">{{ $section['desc'] }}</span>
            </div>
            @foreach($pages as $page)
            <div class="section-row"
                 onclick="selectRow(this, '{{ $sectionKey }}', {{ $page->id }}, '{{ $page->title }}')">
                <span class="flex items-center gap-1.5 min-w-0">
                    {{ $page->title }}
                    @if($page->is_active)
                        <span class="flex-shrink-0 text-[0.55rem] font-bold uppercase tracking-wide text-green-700 bg-green-100 border border-green-200 px-1.5 py-0.5 rounded-full leading-none">Live</span>
                    @endif
                </span>
                <button class="change-btn" onclick="selectRow(this.closest('.section-row'), '{{ $sectionKey }}', {{ $page->id }}, '{{ $page->title }}')">
                    Edit {!! $svgPencil !!}
                </button>
            </div>
            @endforeach
        </div>
        @endforeach

    </aside>

    {{-- RIGHT PANEL --}}
    {{-- RIGHT PANEL — scrolls on its own --}}
    <main class="flex-1 overflow-y-auto p-6 bg-gray-50" id="rightPanel">
        <div id="emptyState" class="flex flex-col items-center justify-center h-64 text-gray-400">
            <svg class="w-12 h-12 mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M4 6h16M4 12h16M4 18h7"/>
            </svg>
            <p class="text-sm">Select an item from the left panel</p>
        </div>

        {{-- Image section panel --}}
        <div id="imagePanel" class="hidden">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-mono" id="imgSectionLabel"></p>
                    <h2 class="text-lg font-semibold text-gray-800" id="imgPageTitle"></h2>
                </div>
            </div>

            {{-- Existing images --}}
            <div id="existingImages" class="grid grid-cols-3 gap-3 mb-6"></div>

            {{-- Upload new --}}
            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Upload New Image</h3>
                <div id="imgDropZone"
                     class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center transition hover:border-blue-400 hover:bg-blue-50 cursor-pointer"
                     onclick="document.getElementById('imgFileInput').click()"
                     ondragover="handleDragOver(event)"
                     ondrop="handleDrop(event)">
                    <svg class="w-10 h-10 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    <p class="text-sm text-gray-600 font-medium">Drop image here or click to browse</p>
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP — max 4MB</p>
                    <input type="file" id="imgFileInput" class="hidden" accept="image/*" onchange="previewFile(event)">
                </div>
                <div id="imgPreviewArea" class="hidden mt-3">
                    <img id="imgPreviewImg" src="" alt="Preview" class="w-full h-36 object-cover rounded-lg border border-gray-200">
                    <p class="text-xs text-gray-500 mt-1 font-mono" id="imgPreviewName"></p>
                </div>
                <div class="flex gap-2 justify-end mt-4">
                    <button onclick="submitUpload()"
                            class="px-5 py-2 text-sm bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                        Upload
                    </button>
                </div>
                <p id="uploadMsg" class="text-sm mt-2 hidden"></p>
            </div>
        </div>

        {{-- Text/content panel --}}
        <div id="textPanel" class="hidden">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-mono" id="txtSectionLabel"></p>
                    <h2 class="text-lg font-semibold text-gray-800" id="txtPageTitle"></h2>
                </div>
            </div>

            {{-- ── How-to guide ─────────────────────────────────────── --}}
            <div class="mb-4 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-800 leading-relaxed space-y-1.5">
                <p class="font-semibold text-blue-900 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
                    </svg>
                    How to edit text content
                </p>
                <ol class="list-decimal list-inside space-y-1 pl-0.5">
                    <li>Pick a <strong>Section</strong> from the dropdown below (e.g. <em>History</em>, <em>Contact</em>, <em>Footer</em>…)</li>
                    <li>Pick the <strong>Field</strong> you want to fill in — the Key is auto-filled for you.</li>
                    <li>Type your text in <strong>Content / Value</strong>, then click <strong>Save</strong>.</li>
                </ol>

                <div class="border-t border-blue-100 pt-1.5 space-y-1.5">
                    <p class="font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1">
                        ⚠ Always click the page marked <strong>Live</strong> (green badge) in the left panel.
                        That is the page your visitors actually see at <code>/user-dashboard</code>.
                        Content saved under any other page will <em>not</em> appear on the site.
                    </p>
                    <p>
                        <strong>History timeline:</strong> choose Section → <em>History</em>, then pick a year
                        (e.g. <em>1984</em>) to fill in both the Title and Description for that milestone.
                    </p>
                    <p>
                        <strong>Google Map:</strong> choose Section → <em>Contact</em>, Field → <em>Google Maps Embed URL</em>.
                        To get the embed URL:
                    </p>
                    <ol class="list-decimal list-inside space-y-0.5 pl-0.5">
                        <li>Open <a href="https://maps.google.com" target="_blank" class="underline hover:text-blue-900">Google Maps</a> and search for the location.</li>
                        <li>Click <strong>Share</strong> → <strong>Embed a map</strong> tab.</li>
                        <li>Click <strong>Copy HTML</strong> — you'll get an <code>&lt;iframe src="…"&gt;</code> snippet.</li>
                        <li>Paste <strong>only the URL inside <code>src="…"</code></strong>, not the whole <code>&lt;iframe&gt;</code>.</li>
                    </ol>
                    <p class="text-blue-600">
                        It should start with: <code class="bg-blue-100 px-1 rounded">https://www.google.com/maps/embed?pb=…</code>
                    </p>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-5">

                {{-- Existing entries --}}
                <div id="existingContents" class="mb-4 space-y-2"></div>

                <div class="border-t border-gray-100 pt-4 space-y-3">
                    <h4 class="text-sm font-semibold text-gray-700">Add / Update Content</h4>

                    {{-- ① Section → Field guided pickers --}}
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 font-medium mb-1">Section</label>
                            <select id="contentSection" onchange="onContentSectionChange()"
                                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                                <option value="">— pick section —</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 font-medium mb-1">Field</label>
                            <select id="contentField" onchange="onContentFieldChange()" disabled
                                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white disabled:opacity-50">
                                <option value="">— pick field —</option>
                            </select>
                        </div>
                    </div>

                    {{-- ② Key (auto-filled; can still edit manually) --}}
                    <div>
                        <label class="block text-xs text-gray-500 font-medium mb-1">
                            Key <span class="text-gray-400 font-normal">(auto-filled above, or type manually)</span>
                        </label>
                        <input type="text" id="contentKey" placeholder="e.g. about_heading"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>

                    {{-- ③ Value (hidden for history-year fields) --}}
                    <div id="singleValueWrap">
                        <label class="block text-xs text-gray-500 font-medium mb-1">Content / Value</label>
                        <textarea id="contentValue" rows="4" placeholder="Enter the text content here…"
                                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none"></textarea>
                    </div>

                    {{-- ③b History-year dual inputs (shown only when a year field is selected) --}}
                    <div id="historyYearInputs" class="hidden space-y-3">
                        <div>
                            <label class="block text-xs text-gray-500 font-medium mb-1">Title</label>
                            <input type="text" id="historyTitle" placeholder="e.g. Ultrafood was founded in Manila"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 font-medium mb-1">Description</label>
                            <textarea id="historyDesc" rows="3" placeholder="Describe what happened this year…"
                                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none"></textarea>
                        </div>
                    </div>

                    {{-- ④ Font options --}}
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 space-y-2">
                        <p class="text-xs font-semibold text-gray-600">
                            Font Options
                            <span class="font-normal text-gray-400 ml-1">— optional, applies to this field only</span>
                        </p>

                        <div class="flex gap-2 items-center">
                            {{-- Font family --}}
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">Family</label>
                                <select id="contentFont" onchange="onFontChange()"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                                    <option value="">— page default —</option>
                                </select>
                            </div>
                            {{-- Font size --}}
                            <div class="w-28">
                                <label class="block text-xs text-gray-500 mb-1">Size</label>
                                <select id="contentFontSize"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                                    <option value="">— default —</option>
                                    <option value="0.75rem">0.75 rem (xs)</option>
                                    <option value="0.875rem">0.875 rem (sm)</option>
                                    <option value="1rem">1 rem (base)</option>
                                    <option value="1.125rem">1.125 rem (lg)</option>
                                    <option value="1.25rem">1.25 rem (xl)</option>
                                    <option value="1.5rem">1.5 rem (2xl)</option>
                                    <option value="1.875rem">1.875 rem (3xl)</option>
                                    <option value="2.25rem">2.25 rem (4xl)</option>
                                    <option value="3rem">3 rem (5xl)</option>
                                    <option value="3.75rem">3.75 rem (6xl)</option>
                                </select>
                            </div>
                        </div>

                        {{-- Live font preview --}}
                        <div id="fontPreviewWrap" class="hidden">
                            <p class="text-xs text-gray-400 mb-1">Preview</p>
                            <div id="fontPreviewBox"
                                 class="p-3 bg-white border border-gray-200 rounded text-base leading-snug text-gray-700 break-words">
                                The quick brown fox jumps over the lazy dog.
                            </div>
                        </div>

                        {{-- Source note --}}
                        <p class="text-[0.65rem] text-gray-400">
                            Fonts loaded via
                            <a href="https://fonts.google.com" target="_blank" class="underline hover:text-gray-600">Google Fonts</a>
                            — free &amp; open source.
                        </p>
                    </div>

                    {{-- ⑤ Save / Update --}}
                    <div class="flex items-center gap-3 pt-1">
                        <p id="contentEditHint" class="text-sm text-blue-600 hidden"></p>
                        <button id="contentSaveBtn" onclick="saveContent()"
                                class="px-5 py-2 text-sm bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                            Save
                        </button>
                        <p id="contentMsg" class="text-sm hidden"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact panel --}}
        <div id="contactPanel" class="hidden">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-mono" id="contactSectionLabel"></p>
                    <h2 class="text-lg font-semibold text-gray-800" id="contactPageTitle"></h2>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <div id="existingContacts" class="mb-4 space-y-2"></div>

                <div class="border-t border-gray-100 pt-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Add / Edit Entry</h4>
                    <div class="flex gap-2 mb-2">
                        <input type="text" id="contactLabel" placeholder="Label (e.g. Main Office)"
                               class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <input type="text" id="contactValue" placeholder="Value (e.g. +63 912 345 6789)"
                               class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div class="flex items-center gap-2">
                        <p id="contactEditHint" class="text-sm text-blue-600 hidden">Editing entry</p>
                        <button id="contactSaveBtn" onclick="saveContact()"
                                class="px-5 py-2 text-sm bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                            Save
                        </button>
                        <p id="contactMsg" class="text-sm self-center hidden"></p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

{{-- ── UPLOAD / REPLACE MODAL (Change in left panel, or Replace on each image card) ──────── --}}
<div class="modal-overlay" id="uploadModal" onclick="closeModalOnOverlay(event)">
    <div class="modal-box">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="font-semibold text-gray-800" id="modalTitle">Upload Image</h3>
                <p class="text-sm text-gray-500 mt-0.5" id="modalLabel">—</p>
            </div>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
        </div>
        <div id="dropZone"
             class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center mb-4 transition hover:border-blue-400 hover:bg-blue-50 cursor-pointer"
             onclick="document.getElementById('fileInput').click()"
             ondragover="handleDragOver(event)"
             ondrop="handleDropModal(event)">
            <svg class="w-10 h-10 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            <p class="text-sm text-gray-600 font-medium">Drop image here or click to browse</p>
            <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP — max 4MB</p>
            <input type="file" id="fileInput" class="hidden" accept="image/*" onchange="previewModalFile(event)">
        </div>
        <div id="previewArea" class="hidden mb-4">
            <img id="previewImg" src="" alt="Preview" class="w-full h-36 object-cover rounded-lg border border-gray-200">
            <p class="text-xs text-gray-500 mt-1 font-mono" id="previewName"></p>
        </div>
        <p id="modalMsg" class="text-sm mb-3 hidden"></p>
        <div class="flex gap-2 justify-end">
            <button onclick="closeModal()"
                    class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                Cancel
            </button>
            <button id="modalSubmitBtn" onclick="submitModalUpload()"
                    class="px-4 py-2 text-sm bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                Upload
            </button>
        </div>
    </div>
</div>

<script src="{{ asset('js/admin/dashboard.js') }}"></script>
</body>
</html>
