<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ultrafood footer contact used the same keys as Menu (menu_footer_*), which is confusing
 * and caused shared expectations. Ultrafood now uses ultrafood_footer_* only on ultrafood page_id.
 * Copy existing ultrafood-page menu_footer contact rows into the new keys once.
 */
return new class extends Migration
{
    private const BASE_KEYS = [
        'menu_footer_contact_label',
        'menu_footer_contact_number',
        'menu_footer_customer_service_label',
        'menu_footer_customer_service_number',
        'menu_footer_email_label',
        'menu_footer_email',
        'menu_footer_customer_email_label',
        'menu_footer_customer_email',
    ];

    public function up(): void
    {
        $pageId = DB::table('pages')->where('slug', 'ultrafood')->value('id');
        if (! $pageId) {
            return;
        }

        foreach (self::BASE_KEYS as $oldBase) {
            $newBase = 'ultrafood_footer_' . substr($oldBase, strlen('menu_footer_'));
            $variants = [$oldBase, $oldBase . '_font', $oldBase . '_font_size', $oldBase . '_color'];

            foreach ($variants as $oldKey) {
                $row = DB::table('text_content')
                    ->where('page_id', $pageId)
                    ->where('key', $oldKey)
                    ->first();

                if (! $row) {
                    continue;
                }

                $newKey = str_replace($oldBase, $newBase, $oldKey);
                if (DB::table('text_content')->where('page_id', $pageId)->where('key', $newKey)->exists()) {
                    continue;
                }

                DB::table('text_content')->insert([
                    'page_id'    => $pageId,
                    'key'        => $newKey,
                    'value'      => $row->value,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $pageId = DB::table('pages')->where('slug', 'ultrafood')->value('id');
        if (! $pageId) {
            return;
        }

        foreach (self::BASE_KEYS as $oldBase) {
            $newBase = 'ultrafood_footer_' . substr($oldBase, strlen('menu_footer_'));
            $variants = [$newBase, $newBase . '_font', $newBase . '_font_size', $newBase . '_color'];
            foreach ($variants as $newKey) {
                DB::table('text_content')->where('page_id', $pageId)->where('key', $newKey)->delete();
            }
        }
    }
};
