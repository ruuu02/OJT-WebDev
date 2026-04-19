<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    private const MAX_IMAGE_SIZE_KB = 30720;

    private array $tableMap = [
        'carousel' => 'carousel_images',
        'logo'     => 'logos',
        'product'  => 'product_images',
        'recipe'   => 'recipe_images',
        'menu'     => 'menu_images',
    ];

    // Maps section key → actual public/images/ subdirectory name.
    // These MUST match the directories used by the seeded files and the
    // mediaUrl() helper in the front-end JS.
    private array $dirMap = [
        'carousel' => 'banner',
        'logo'     => 'logo',
        'product'  => 'banner',
        'recipe'   => 'icons',
        'menu'     => 'menu',
    ];

    public function store(Request $request)
    {
        $request->validate([
            'page_id' => 'required|exists:pages,id',
            'section' => 'required|in:carousel,logo,product,recipe,menu',
            'image'   => 'required|file|mimes:jpg,jpeg,png,webp,svg|max:'.self::MAX_IMAGE_SIZE_KB,
        ]);

        $section  = $request->section;
        $table    = $this->tableMap[$section];
        $dir      = $this->dirMap[$section];
        $file     = $request->file('image');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $destination = public_path('images/' . $dir);
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $filename);

        $id = DB::table($table)->insertGetId([
            'page_id'       => $request->page_id,
            'filename'      => $filename,
            'original_name' => $file->getClientOriginalName(),
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return response()->json([
            'success' => true,
            'media'   => [
                'id'           => $id,
                'url'          => asset('images/' . $dir . '/' . $filename),
                'filename'     => $filename,
                'original_name'=> $file->getClientOriginalName(),
            ],
        ]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'section' => 'required|in:carousel,logo,product,recipe,menu',
            'image'   => 'required|file|mimes:jpg,jpeg,png,webp,svg|max:'.self::MAX_IMAGE_SIZE_KB,
        ]);

        $section = $request->section;
        $table   = $this->tableMap[$section];
        $dir     = $this->dirMap[$section];

        $record = DB::table($table)->where('id', $id)->first();
        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Media not found'], 404);
        }

        $file     = $request->file('image');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $destination = public_path('images/' . $dir);
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        // Try both the mapped dir and the legacy section-named dir when deleting
        $oldPath = public_path('images/' . $dir . '/' . $record->filename);
        if (!file_exists($oldPath)) {
            $oldPath = public_path('images/' . $section . '/' . $record->filename);
        }
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }

        $file->move($destination, $filename);

        DB::table($table)->where('id', $id)->update([
            'filename'      => $filename,
            'original_name' => $file->getClientOriginalName(),
            'updated_at'    => now(),
        ]);

        return response()->json([
            'success' => true,
            'media'   => [
                'id'           => $id,
                'filename'     => $filename,
                'original_name'=> $file->getClientOriginalName(),
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

        $record = DB::table($table)->where('id', $id)->first();
        if (!$record) {
            abort(404);
        }

        $dir      = $this->dirMap[$section];
        $filePath = public_path('images/' . $dir . '/' . $record->filename);
        if (!file_exists($filePath)) {
            $filePath = public_path('images/' . $section . '/' . $record->filename);
        }
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        DB::table($table)->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    public function setActive(Request $request, int $id)
    {
        $request->validate([
            'section' => 'required|in:carousel,logo,product,recipe,menu',
        ]);

        $table = $this->tableMap[$request->section] ?? null;
        if (!$table) {
            return response()->json(['success' => false, 'message' => 'Invalid section'], 422);
        }
        if (!DB::table($table)->where('id', $id)->exists()) {
            abort(404);
        }

        DB::table($table)->where('id', $id)->update(['is_active' => true, 'updated_at' => now()]);

        return response()->json(['success' => true]);
    }
}
