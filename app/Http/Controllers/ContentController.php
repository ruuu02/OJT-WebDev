<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    private const MAX_VIDEO_SIZE_KB = 1048576;

    private array $tableMap = [
        'text_content'     => 'text_content',
        'product_details'  => 'product_details',
        'featured_recipes' => 'featured_recipes',
    ];

    public function save(Request $request)
    {
        $request->validate([
            'page_id' => 'required|exists:pages,id',
            'section' => 'required|in:text_content,product_details,featured_recipes',
            'key'     => 'required|string|max:255',
            'value'   => 'required|string',
        ]);

        $table = $this->tableMap[$request->section];

        $id = DB::table($table)->insertGetId([
            'page_id'    => $request->page_id,
            'key'        => strip_tags($request->key),
            'value'      => strip_tags($request->value),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'content' => [
                'id'      => $id,
                'page_id' => (int) $request->page_id,
                'section' => $request->section,
                'key'     => strip_tags($request->key),
                'value'   => strip_tags($request->value),
            ],
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $section = $request->query('section');
        $table   = $this->tableMap[$section] ?? null;

        if (!$table) {
            return response()->json(['success' => false, 'message' => 'Invalid section'], 422);
        }

        if (!DB::table($table)->where('id', $id)->exists()) {
            abort(404);
        }

        DB::table($table)->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Delete all content rows for a field (key, key_font, key_font_size, key_color) so they are removed together.
     */
    public function destroyByKey(Request $request)
    {
        $request->validate([
            'page_id' => 'required|exists:pages,id',
            'section' => 'required|in:text_content,product_details,featured_recipes',
            'key'     => 'required|string|max:255',
        ]);

        $table   = $this->tableMap[$request->section];
        $baseKey = strip_tags($request->key);
        $keys    = [$baseKey, $baseKey . '_font', $baseKey . '_font_size', $baseKey . '_color', $baseKey . '_icon'];

        DB::table($table)
            ->where('page_id', $request->page_id)
            ->whereIn('key', $keys)
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Update (or create) the main value and optional font/font_size for one field.
     */
    public function updateField(Request $request)
    {
        $request->validate([
            'page_id'   => 'required|exists:pages,id',
            'section'   => 'required|in:text_content,product_details,featured_recipes',
            'base_key'  => 'required|string|max:255',
            'value'     => 'nullable|string',
            'font'      => 'nullable|string|max:255',
            'font_size' => 'nullable|string|max:255',
            'color'     => 'nullable|string|max:255',
        ]);

        $table   = $this->tableMap[$request->section];
        $pageId  = (int) $request->page_id;
        $baseKey = strip_tags($request->base_key);
        $value   = $request->filled('value') ? strip_tags($request->value) : null;
        $font    = $request->filled('font') ? strip_tags($request->font) : null;
        $fontSize= $request->filled('font_size') ? strip_tags($request->font_size) : null;
        $color   = $request->filled('color') ? strip_tags($request->color) : null;

        $now = now();

        // Require at least one of value, font, font_size, or color
        if ($value === null && $font === null && $fontSize === null && $color === null) {
            return response()->json(['success' => false, 'message' => 'Provide at least one of: value, font, font size, or color.'], 422);
        }

        // Upsert main key only when value is provided
        if ($value !== null) {
            $existing = DB::table($table)->where('page_id', $pageId)->where('key', $baseKey)->first();
            if ($existing) {
                DB::table($table)->where('id', $existing->id)->update(['value' => $value, 'updated_at' => $now]);
            } else {
                DB::table($table)->insert([
                    'page_id' => $pageId, 'key' => $baseKey, 'value' => $value,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // table doesn't have 'section' column - it's per-table. So existing check is just page_id + key.
        // Font
        $fontKey = $baseKey . '_font';
        $rowFont = DB::table($table)->where('page_id', $pageId)->where('key', $fontKey)->first();
        if ($font !== null && $font !== '') {
            if ($rowFont) {
                DB::table($table)->where('id', $rowFont->id)->update(['value' => $font, 'updated_at' => $now]);
            } else {
                DB::table($table)->insert([
                    'page_id' => $pageId, 'key' => $fontKey, 'value' => $font,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        } elseif ($rowFont) {
            DB::table($table)->where('id', $rowFont->id)->delete();
        }

        // Font size
        $sizeKey = $baseKey . '_font_size';
        $rowSize = DB::table($table)->where('page_id', $pageId)->where('key', $sizeKey)->first();
        if ($fontSize !== null && $fontSize !== '') {
            if ($rowSize) {
                DB::table($table)->where('id', $rowSize->id)->update(['value' => $fontSize, 'updated_at' => $now]);
            } else {
                DB::table($table)->insert([
                    'page_id' => $pageId, 'key' => $sizeKey, 'value' => $fontSize,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        } elseif ($rowSize) {
            DB::table($table)->where('id', $rowSize->id)->delete();
        }

        // Color
        $colorKey = $baseKey . '_color';
        $rowColor = DB::table($table)->where('page_id', $pageId)->where('key', $colorKey)->first();
        if ($color !== null && $color !== '') {
            if ($rowColor) {
                DB::table($table)->where('id', $rowColor->id)->update(['value' => $color, 'updated_at' => $now]);
            } else {
                DB::table($table)->insert([
                    'page_id' => $pageId, 'key' => $colorKey, 'value' => $color,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        } elseif ($rowColor) {
            DB::table($table)->where('id', $rowColor->id)->delete();
        }

        return response()->json(['success' => true]);
    }

    public function uploadVideoField(Request $request)
    {
        $request->validate([
            'page_id'   => 'required|exists:pages,id',
            'base_key'  => 'required|string|max:255',
            'directory' => 'nullable|string|max:100',
            'video'     => 'required|file|mimetypes:video/mp4,video/webm,video/ogg,video/quicktime,video/x-m4v|max:' . self::MAX_VIDEO_SIZE_KB,
        ], [
            'video.mimetypes' => 'Please upload an MP4, WEBM, OGG, MOV, or M4V video.',
            'video.max' => 'The selected video is larger than the current upload limit.',
        ]);

        $pageId = (int) $request->page_id;
        $baseKey = strip_tags($request->base_key);
        $directory = trim((string) $request->input('directory', 'menu'));
        $directory = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $directory) ?: 'menu';

        $file = $request->file('video');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension === '') {
            $extension = match ($file->getMimeType()) {
                'video/webm' => 'webm',
                'video/ogg' => 'ogg',
                'video/quicktime' => 'mov',
                'video/x-m4v' => 'm4v',
                default => 'mp4',
            };
        }

        $filename = Str::uuid() . '.' . $extension;
        $relativeDir = 'videos/' . trim($directory, '/');
        $destination = public_path($relativeDir);
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $filename);

        $relativePath = trim($relativeDir . '/' . $filename, '/');
        $table = $this->tableMap['text_content'];
        $now = now();

        $existing = DB::table($table)->where('page_id', $pageId)->where('key', $baseKey)->first();
        $previousValue = trim((string) ($existing->value ?? ''));

        if ($existing) {
            DB::table($table)->where('id', $existing->id)->update([
                'value' => $relativePath,
                'updated_at' => $now,
            ]);
        } else {
            DB::table($table)->insert([
                'page_id' => $pageId,
                'key' => $baseKey,
                'value' => $relativePath,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (
            $previousValue !== ''
            && !preg_match('/^https?:\/\//i', $previousValue)
            && !str_starts_with($previousValue, '//')
            && str_starts_with(ltrim($previousValue, '/'), trim($relativeDir, '/') . '/')
        ) {
            $previousPath = public_path(ltrim($previousValue, '/'));
            if (is_file($previousPath)) {
                @unlink($previousPath);
            }
        }

        return response()->json([
            'success' => true,
            'path' => $relativePath,
            'url' => asset($relativePath),
            'content' => [
                'page_id' => $pageId,
                'section' => 'text_content',
                'key' => $baseKey,
                'value' => $relativePath,
            ],
        ]);
    }
}
