<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Production snapshot seeder — mirrors the exact state of Laptop A's database
 * so that any machine running `php artisan db:seed` (or `migrate --seed`) gets
 * the same image assignments and text content without needing manual admin edits.
 *
 * Strategy:
 *  1. Wipe all image rows for the ultrafood page (removes duplicates from old runs).
 *  2. Re-insert only the active rows, using the UUID-named files that are committed
 *     to git under public/images/{banner,logo,icons}/.
 *  3. Capture each inserted ID and write the corresponding media_id_* key into
 *     text_content so the blade template and visual editor point to the right rows.
 *  4. Upsert all text content values.
 *
 * Safe to re-run at any time — image rows are wiped+rebuilt, text rows are upserted.
 */
class ExistingResourcesSeeder extends Seeder
{
    public function run(): void
    {
        $page = DB::table('pages')->where('slug', 'ultrafood')->first();
        if (! $page) {
            $this->command->warn('No page with slug "ultrafood" found. Run PageSeeder first.');
            return;
        }
        $pid = $page->id;
        $now = now();

        // ── 1. Clean slate: remove all image rows for this page ───────────────
        // This eliminates the duplicate rows that accumulate from repeated seeder runs.
        DB::table('carousel_images')->where('page_id', $pid)->delete();
        DB::table('logos')->where('page_id', $pid)->delete();
        DB::table('product_images')->where('page_id', $pid)->delete();
        DB::table('recipe_images')->where('page_id', $pid)->delete();

        // ── 2. Helper: insert one row and return its new ID ───────────────────
        $insert = fn(string $table, array $row): int =>
            DB::table($table)->insertGetId(array_merge($row, [
                'page_id'    => $pid,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));

        // ── 3. Helper: write / update a text_content key ─────────────────────
        $tc = function(string $key, string $value) use ($pid, $now): void {
            DB::table('text_content')->updateOrInsert(
                ['page_id' => $pid, 'key' => $key],
                ['value' => $value, 'created_at' => $now, 'updated_at' => $now]
            );
        };

        // ─────────────────────────────────────────────────────────────────────
        // CAROUSEL IMAGES  (hero slides — loaded positionally by $imgUrl())
        // ─────────────────────────────────────────────────────────────────────
        // Slide 0 — Welcome banner (admin replaced with UUID upload)
        $insert('carousel_images', [
            'filename'      => '099f0de1-2127-4f8c-ae59-b353ebe21450.png',
            'original_name' => 'UFDI-Welcome-Banner.png',
        ]);

        // Slide 1 — Our Brands
        $insert('carousel_images', [
            'filename'      => 'UFDI-Our-Brands-Banner.png',
            'original_name' => 'UFDI-Our-Brands-Banner.png',
        ]);

        // Slide 2 — Accreditations
        $insert('carousel_images', [
            'filename'      => 'UFDI-Accreditations-Banner.png',
            'original_name' => 'UFDI-Accreditations-Banner.png',
        ]);

        // ─────────────────────────────────────────────────────────────────────
        // HISTORY BACKGROUND IMAGES  (also stored in carousel_images)
        // IDs are written to text_content so the blade/editor can look them up
        // by stable key rather than fragile positional index.
        // ─────────────────────────────────────────────────────────────────────
        $histBgs = [
            // BG-1 was replaced by the admin with a UUID upload
            '0d92d472-0e40-4039-9e77-c5c8b55a8e13.png',
            'UFDI-History-Timeline-BG-2.png',
            'UFDI-History-Timeline-BG-3.png',
            'UFDI-History-Timeline-BG-4.png',
            'UFDI-History-Timeline-BG-5.png',
            'UFDI-History-Timeline-BG-6.png',
            'UFDI-History-Timeline-BG-7.png',
        ];

        $histOriginals = [
            'UFDI-History-Timeline-BG-1.png',
            'UFDI-History-Timeline-BG-2.png',
            'UFDI-History-Timeline-BG-3.png',
            'UFDI-History-Timeline-BG-4.png',
            'UFDI-History-Timeline-BG-5.png',
            'UFDI-History-Timeline-BG-6.png',
            'UFDI-History-Timeline-BG-7.png',
        ];

        foreach ($histBgs as $i => $fname) {
            $id = $insert('carousel_images', [
                'filename'      => $fname,
                'original_name' => $histOriginals[$i],
            ]);
            $tc('history_bg_id_' . $i,    (string) $id);
            $tc('media_id_history_bg_' . $i, (string) $id);
        }

        // ─────────────────────────────────────────────────────────────────────
        // LOGOS  (stored in public/images/logo/)
        // ─────────────────────────────────────────────────────────────────────
        // Menu brand logo — admin replaced with UUID upload
        $menuLogoId = $insert('logos', [
            'filename'      => 'e2288ce5-75d7-4fc3-a4b2-8fb3642a811a.png',
            'original_name' => 'Menu-Logo.png',
        ]);
        $tc('media_id_brand_menu_logo', (string) $menuLogoId);

        // Nordic brand logo — still original file
        $nordicLogoId = $insert('logos', [
            'filename'      => 'Korpala-Nordic-Logo.png',
            'original_name' => 'Korpala-Nordic-Logo.png',
        ]);
        $tc('media_id_brand_nordic_logo', (string) $nordicLogoId);

        // Header / footer logo — admin replaced with UUID upload
        $headerLogoId = $insert('logos', [
            'filename'      => '65480775-6edc-4e3e-b8c4-f989fe69c17c.png',
            'original_name' => 'Ultrafood-Distributors-Inc-Logo.png',
        ]);
        $tc('media_id_header_logo', (string) $headerLogoId);

        // ─────────────────────────────────────────────────────────────────────
        // PRODUCT IMAGES  (stored in public/images/banner/)
        // ─────────────────────────────────────────────────────────────────────
        // Clients map — admin replaced with UUID upload
        $clientsMapId = $insert('product_images', [
            'filename'      => '9d701c53-b80a-4afa-a270-3cad580defdb.png',
            'original_name' => 'UFDI-Customer-and-Clients-Map.png',
        ]);
        $tc('media_id_clients_map', (string) $clientsMapId);

        // Clients legend — still original file
        $clientsLegendId = $insert('product_images', [
            'filename'      => 'UFDI-Customer-and-Clients-Map-Legend.png',
            'original_name' => 'UFDI-Customer-and-Clients-Map-Legend.png',
        ]);
        $tc('media_id_clients_legend', (string) $clientsLegendId);

        // About section image — admin replaced with UUID upload
        $aboutImageId = $insert('product_images', [
            'filename'      => 'ec84a286-bc3e-4af9-b69e-a0e46ae226b2.png',
            'original_name' => 'UFDI-Intro-Image.png',
        ]);
        $tc('media_id_about_image', (string) $aboutImageId);

        // ─────────────────────────────────────────────────────────────────────
        // RECIPE IMAGES / ICONS  (stored in public/images/icons/)
        // ─────────────────────────────────────────────────────────────────────
        // Mission icon — admin replaced with UUID upload
        $missionId = $insert('recipe_images', [
            'filename'      => '947af148-a9a2-4d45-8eb9-5132c55741a9.png',
            'original_name' => 'Mission-Icon.png',
        ]);
        $tc('media_id_mission_icon', (string) $missionId);

        // Vision icon — admin replaced with UUID upload
        $visionId = $insert('recipe_images', [
            'filename'      => '9f97d525-cd26-4d90-9806-3eaf7de2f2e7.png',
            'original_name' => 'Vision-Icon.png',
        ]);
        $tc('media_id_vision_icon', (string) $visionId);

        // Wide Distribution icon — admin replaced with UUID upload
        $strength1Id = $insert('recipe_images', [
            'filename'      => '4b809e30-b1fb-4058-8d32-dd2a7797a364.png',
            'original_name' => 'Wide-Distribution-Icon.png',
        ]);
        $tc('media_id_strength_1_icon', (string) $strength1Id);

        // Product Diversity icon — still original file
        $strength2Id = $insert('recipe_images', [
            'filename'      => 'Product-Diversity-Icon.png',
            'original_name' => 'Product-Diversity-Icon.png',
        ]);
        $tc('media_id_strength_2_icon', (string) $strength2Id);

        // Innovative icon — still original file
        $strength3Id = $insert('recipe_images', [
            'filename'      => 'Innovative-Icon.png',
            'original_name' => 'Innovative-Icon.png',
        ]);
        $tc('media_id_strength_3_icon', (string) $strength3Id);

        // Logistic Efficiency icon — still original file
        $strength4Id = $insert('recipe_images', [
            'filename'      => 'Logistic-Efficiency-Icon.png',
            'original_name' => 'Logistic-Efficiency-Icon.png',
        ]);
        $tc('media_id_strength_4_icon', (string) $strength4Id);

        // ─────────────────────────────────────────────────────────────────────
        // TEXT CONTENT  (all editable text from ultrafood.blade.php)
        // ─────────────────────────────────────────────────────────────────────
        $textContent = [
            // Nav links
            'nav_about'     => 'About Us',
            'nav_mission'   => 'Purpose',
            'nav_strengths' => 'Strengths',
            'nav_brands'    => 'Brands',
            'nav_history'   => 'History',
            'nav_clients'   => 'Clients',
            'nav_contact'   => 'Contact',

            // About section
            'about_heading' => 'ULTRAFOOD DISTRIBUTORS INC.',
            'about_para1'   => 'Has steadily gained recognition as a professional and reputable organization in the Fast-Moving Consumer Goods (FMCG) sector. Our commitment to excellence, combined with our dedication to building strong and long-term partnerships, made us a trusted brand in this fast-paced industry.',
            'about_para2'   => 'We are driven by a desire to meet the needs of our customers, and this inspires us to continually strive for product creation and innovation while remaining dedicated to delivering high-quality food solutions.',

            // Mission & Vision
            'mission_description' => 'To provide high-quality food products that meet the diverse needs of our consumers. We are committed to innovation and providing our customers the greatest opportunity for success in their marketplace.',
            'vision_description'  => 'To be an industry leader in providing high-quality, innovative food products to our customers and to become a major player in the Philippine manufacturing and distribution business as a professional and reputable organization.',

            // Strengths
            'strengths_heading' => 'WHAT SET US APART',
            'strength_1_title'  => 'WIDE DISTRIBUTION',
            'strength_1_desc'   => 'Extensive distribution network reaching both rural and urban areas, modern trade, and traditional retail stores across the Philippines.',
            'strength_2_title'  => 'PRODUCT DIVERSITY',
            'strength_2_desc'   => 'Expanding product offerings to meet the diverse needs of our consumers.',
            'strength_3_title'  => 'INNOVATIVE',
            'strength_3_desc'   => 'Continuous product innovation and development maintaining market relevance and trends.',
            'strength_4_title'  => 'LOGISTIC EFFICIENCY',
            'strength_4_desc'   => 'Ensures our valued consumers access our products anywhere and anytime by optimizing product shipments timely and effectively.',

            // Brands
            'brands_heading'       => 'DISTINCT BRANDS, ONE VISION',
            'brand_menu_title'     => 'Menu Food Solutions',
            'brand_menu_tagline'   => 'Sarap-saya sa bawat bite!',
            'brand_menu_desc'      => 'Ginagawang masarap at unforgettable ang bawat recipe. Halina at tikman ang next big taste na siguradong magpapasaya at magpapabusog sa lahat!',
            'brand_nordic_title'   => 'Nordic Foods PH',
            'brand_nordic_tagline' => 'Finland straight to your taste buds.',
            'brand_nordic_desc'    => 'Wholesome goodness, premium ingredients — all thoughtfully combined to create a bowl experience that is both satisfying and nourishing.',

            // History
            'history_heading'    => 'OUR JOURNEY THROUGH TIME',
            'history_2005_desc'  => 'Founded in September 2005 as Appenzell Inc., ULTRAFOOD DISTRIBUTORS INC. specialized in the distribution of powdered and liquid condiment products catering to both the General Trade and HORECA. Following the initial launch, broth cubes were quickly developed and gained popularity in the market.',
            'history_2006_desc'  => 'Offering a wide variety of delicious jelly flavors, MENU Jelly Powder Mixes earned the trust of consumers across different markets with its consistency and taste. Following this, the MENU Soups and Mixes was officially launched, and the brand continued to expand, gaining recognition for delivering rich flavors, convenience, and quality that satisfy consumer needs.',
            'history_2010_desc'  => 'Secured partnerships with major supermarkets and grocery stores nationwide. Through consistent quality and consumer trust, the brand earned a strong presence in key retail accounts and became a trusted choice in the market.',
            'history_2011_desc'  => 'Participated in food expos, livelihood activities, and events to showcase varieties of products. These participations broadened the market reach and growing demand in the industry. ULTRAFOOD DISTRIBUTORS INC. has been a proud member of HRAP, which allows them to stay connected with industry developments, best practices, and new trends.',
            'history_2017_desc'  => 'Embraced digital transformation by introducing and selling our products through e-commerce platforms. This provides greater convenience for customers to access our products anytime and anywhere.',
            'history_2022_desc'  => 'Established a new office building and warehouse designed to improve operational efficiency and support the growing team. This enhances our services, streamlines processes, and allows us to respond quickly to the needs of our clients and partners.',
            'history_2024_desc'  => "Our company is proud to hold the HALAL Registration Certificate issued by the Islamic Da'wah Council of the Philippines (IDCP), reflecting our commitment to the highest standards of quality and ethical compliance. This affirms that our products comply with HALAL requirements, ensuring integrity and trust to our valued clients.",

            // Clients section
            'clients_title'        => 'CLIENTS MAP',
            'legend_title'         => 'LEGEND',
            'legend_exporters'     => 'Exporters',
            'legend_commissaries'  => 'Commissaries & Restaurants',
            'legend_institutional' => 'Institutional',
            'legend_franchising'   => 'Franchising',
            'legend_caterings'     => 'Caterings',
            'legend_supermarkets'  => 'Supermarkets',
            'legend_general_trade' => 'General Trade',
            'legend_distributors'  => 'Distributors',
            'legend_online_shops'  => 'Online Shops',

            // Contact
            'contact_heading'            => 'CONNECT WITH US!',
            'contact_subtext'            => "Hungry for answers? Let's cook something up together — send us a bite of your thoughts!",
            'contact_bg'                 => '#79ce1b',
            'contact_form_company_label' => 'Company Name',
            'contact_form_company_ph'    => 'e.g. Acme Corp',
            'contact_form_industry_label'=> 'Industry',
            'contact_form_industry_ph'   => 'e.g. Food & Beverage',
            'contact_form_name_label'    => 'Full Name',
            'contact_form_name_ph'       => 'Your name',
            'contact_form_email_label'   => 'Email Address',
            'contact_form_email_ph'      => 'you@company.com',
            'contact_form_message_label' => 'Message',
            'contact_form_message_ph'    => 'Tell us about your inquiry...',
            'contact_form_submit'        => 'Send Message',
            'contact_form_success'       => "Thank you! We'll be in touch soon.",

            // Back to top button
            'back_to_top_bg'       => '#4B7A08',
            'back_to_top_fg'       => '#ffffff',
            'back_to_top_hover_bg' => '#1a2e04',
        ];

        foreach ($textContent as $key => $value) {
            $tc($key, $value);
        }

        // Ultrafood footer contact (separate keys from Menu's menu_footer_*)
        foreach ([
            'ultrafood_footer_contact_us_title'          => 'Contact Us',
            'ultrafood_footer_contact_label'           => 'Contact Number:',
            'ultrafood_footer_contact_number'          => '+1 234 567 890',
            'ultrafood_footer_customer_service_label'  => 'Customer Service:',
            'ultrafood_footer_customer_service_number' => '+1 234 000 111',
            'ultrafood_footer_email_label'             => 'Email:',
            'ultrafood_footer_email'                   => 'ultrafood05@google.com',
            'ultrafood_footer_customer_email_label'    => 'Customer Service Email:',
            'ultrafood_footer_customer_email'          => 'customerservice@menufood.com',
        ] as $k => $v) {
            $tc($k, $v);
        }

        // ─────────────────────────────────────────────────────────────────────
        // CONTACT INFO  (seed defaults only if table is empty for this page)
        // ─────────────────────────────────────────────────────────────────────
        if (DB::table('contact_numbers')->where('page_id', $pid)->count() === 0) {
            DB::table('contact_numbers')->insert([
                'page_id'    => $pid,
                'label'      => 'Main',
                'value'      => '+1 234 567 890',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        if (DB::table('email_addresses')->where('page_id', $pid)->count() === 0) {
            DB::table('email_addresses')->insert([
                'page_id'    => $pid,
                'label'      => 'Email',
                'value'      => 'ultrafood05@google.com',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // MENU + NORDIC TEXT CONTENT (for visual editor prefilled fields)
        // ─────────────────────────────────────────────────────────────────────
        $seedPageText = function (string $slug, array $items) use ($now): void {
            $pageRow = DB::table('pages')->where('slug', $slug)->first();
            if (! $pageRow) {
                return;
            }
            foreach ($items as $key => $value) {
                DB::table('text_content')->updateOrInsert(
                    ['page_id' => $pageRow->id, 'key' => $key],
                    ['value' => $value, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        };

        $seedPageText('nordic', [
            'nordic_hero_kicker'         => 'Korpala Nordic',
            'nordic_hero_heading'        => 'Nordic Oats for Everyday Strength',
            'nordic_hero_subtext'        => 'Wholesome oats for breakfast, savory meals, and better daily nutrition.',
            'nordic_hero_btn_recipes'    => 'View Recipes',
            'nordic_hero_btn_products'   => 'See Products',
            'nordic_oats_benefits_heading' => 'Oats Benefits',
            'nordic_oats_benefits_body'    => 'Oats are rich in fiber, support heart health, and help keep you full longer. They are a good source of nutrients for a balanced daily meal.',
            'nordic_why_heading'         => 'Why Nordic Oats?',
            'nordic_product_copy_title'  => 'Whole Grain Oats',
            'nordic_product_copy_text'   => 'Made from whole oat groats for a hearty texture and naturally rich fiber, ideal for filling breakfasts and wholesome recipes.',
            'nordic_product_caption_1'   => 'Whole Grain Oats',
            'nordic_product_caption_2'   => 'Instant Oats',
            'nordic_product_caption_3'   => 'Quick Cook Oats',
        ]);

        $nordicPage = DB::table('pages')->where('slug', 'nordic')->first();
        if ($nordicPage) {
            $nordicPid = (int) $nordicPage->id;

            $nordicOatsBenefitsSlides = [
                1 => 'nordic-ve-oats-benefits.png',
                2 => 'nordic-ve-product-2.png',
                3 => 'nordic-ve-product-3.png',
            ];

            foreach ($nordicOatsBenefitsSlides as $index => $filename) {
                DB::table('product_images')->updateOrInsert(
                    [
                        'page_id' => $nordicPid,
                        'original_name' => $filename,
                    ],
                    [
                        'filename' => $filename,
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );

                $slideImageId = DB::table('product_images')
                    ->where('page_id', $nordicPid)
                    ->where('original_name', $filename)
                    ->value('id');

                if ($slideImageId) {
                    DB::table('text_content')->updateOrInsert(
                        ['page_id' => $nordicPid, 'key' => 'media_id_nordic_oats_benefits_slide_'.$index],
                        ['value' => (string) $slideImageId, 'created_at' => $now, 'updated_at' => $now]
                    );
                }
            }

            $nordicProductSlides = [
                1 => 'nordic-ve-product-1.png',
                2 => 'nordic-ve-product-2.png',
                3 => 'nordic-ve-product-3.png',
            ];

            foreach ($nordicProductSlides as $index => $filename) {
                DB::table('product_images')->updateOrInsert(
                    [
                        'page_id' => $nordicPid,
                        'original_name' => $filename,
                    ],
                    [
                        'filename' => $filename,
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );

                $slideImageId = DB::table('product_images')
                    ->where('page_id', $nordicPid)
                    ->where('original_name', $filename)
                    ->value('id');

                if ($slideImageId) {
                    DB::table('text_content')->updateOrInsert(
                        ['page_id' => $nordicPid, 'key' => 'media_id_nordic_product_slide_' . $index],
                        ['value' => (string) $slideImageId, 'created_at' => $now, 'updated_at' => $now]
                    );
                }
            }
        }

        $seedPageText('menu', [
            'menu_brand_wordmark'                 => 'MENU-MENUTONG SARAP',
            'menu_brand_tagline'                  => 'Sarap-saya sa bawat bite!',
            'menu_nav_home'                       => 'Home',
            'menu_nav_products'                   => 'Products',
            'menu_nav_recipes'                    => 'Recipes',
            'menu_nav_buy_now'                    => 'Buy Now',
            'menu_categories_heading_text'        => 'WHATS OUR MENU FOR TODAY?',
            'menu_popup_link_text'                => 'Go to Page',
            'menu_footer_contact_title'           => 'Contact Us',
            'menu_footer_contact_label'           => 'Contact Number:',
            'menu_footer_contact_number'          => '+1 234 567 890',
            'menu_footer_customer_service_label'  => 'Customer Service:',
            'menu_footer_customer_service_number' => '+1 234 000 111',
            'menu_footer_email_label'             => 'Email:',
            'menu_footer_email'                   => 'ultrafood05@google.com',
            'menu_footer_customer_email_label'    => 'Customer Service Email:',
            'menu_footer_customer_email'          => 'customerservice@menufood.com',
            'menu_footer_follow_title'            => 'Follow Us',
            'menu_footer_brands_title'            => 'Ultrafood Brands',
            'menu_scroll_top_aria'                => 'Back to top',
            'menu_scroll_top_icon'                => '↑',
            // Menu home category circle labels (match config/menu-images.php defaults)
            'menu_home_cat_appetizers_label'      => 'Appetizers',
            'menu_home_cat_soups_label'           => 'Soups',
            'menu_home_cat_main_dishes_label'     => 'Main Dishes',
            'menu_home_cat_noodles_pastas_label'  => 'Noodles and Pastas',
            'menu_home_cat_drinks_label'          => 'Drinks',
            'menu_home_cat_desserts_label'        => 'Desserts',
        ]);

        // ─────────────────────────────────────────────────────────────────────
        // MENU RECIPE CATEGORIES (seed 6 defaults so Recipes dropdown is not empty)
        // ─────────────────────────────────────────────────────────────────────
        $recipeCats = [
            ['name' => 'Appetizers', 'slug' => 'appetizers', 'icon' => 'Appetizers.png', 'sort_order' => 1],
            ['name' => 'Soups', 'slug' => 'soups', 'icon' => 'Soups.png', 'sort_order' => 2],
            ['name' => 'Main Dishes', 'slug' => 'main-dishes', 'icon' => 'Main-Dishes.png', 'sort_order' => 3],
            ['name' => 'Noodles and Pastas', 'slug' => 'noodles-and-pastas', 'icon' => 'Noodles-and-Pastas.png', 'sort_order' => 4],
            ['name' => 'Drinks', 'slug' => 'drinks', 'icon' => 'Drinks.png', 'sort_order' => 5],
            ['name' => 'Desserts', 'slug' => 'desserts', 'icon' => 'Desserts.png', 'sort_order' => 6],
        ];

        foreach ($recipeCats as $c) {
            DB::table('menu_recipe_categories')->updateOrInsert(
                ['slug' => $c['slug']],
                [
                    'name' => $c['name'],
                    'icon_filename' => $c['icon'],
                    'sort_order' => $c['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        // ─────────────────────────────────────────────────────────────────────
        // MENU MEDIA (category circle images + header/footer logos)
        // ─────────────────────────────────────────────────────────────────────
        $menuPage = DB::table('pages')->where('slug', 'menu')->first();
        if ($menuPage) {
            $menuPid = (int) $menuPage->id;

            // Ensure the menu-images directory exists (uploads will also use it).
            $menuDir = public_path('images/menu');
            if (!is_dir($menuDir)) {
                @mkdir($menuDir, 0755, true);
            }

            // Seed the 6 category circle images into menu_images and store stable IDs in text_content.
            $menuCatImgs = [
                'appetizers'     => ['filename' => 'Appetizers.png',          'original' => 'Appetizers.png'],
                'soups'          => ['filename' => 'Soups.png',               'original' => 'Soups.png'],
                'main_dishes'    => ['filename' => 'Main-Dishes.png',         'original' => 'Main-Dishes.png'],
                'noodles_pastas' => ['filename' => 'Noodles-and-Pastas.png',  'original' => 'Noodles-and-Pastas.png'],
                'drinks'         => ['filename' => 'Drinks.png',              'original' => 'Drinks.png'],
                'desserts'       => ['filename' => 'Desserts.png',            'original' => 'Desserts.png'],
            ];

            foreach ($menuCatImgs as $slug => $meta) {
                $id = DB::table('menu_images')->updateOrInsert(
                    [
                        'page_id' => $menuPid,
                        'original_name' => $meta['original'],
                    ],
                    [
                        'filename' => $meta['filename'],
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );

                // updateOrInsert doesn't return the id; fetch it after.
                $rowId = DB::table('menu_images')
                    ->where('page_id', $menuPid)
                    ->where('original_name', $meta['original'])
                    ->value('id');

                if ($rowId) {
                    DB::table('text_content')->updateOrInsert(
                        ['page_id' => $menuPid, 'key' => 'media_id_menu_home_cat_' . $slug . '_img'],
                        ['value' => (string) $rowId, 'created_at' => $now, 'updated_at' => $now]
                    );
                }
            }

            // Seed Menu gallery slides (food carousel) into menu_images and store stable IDs.
            // These back the Visual Editor zones: media_id_menu_gallery_slide_1..4.
            $galleryImgs = [
                1 => ['filename' => 'Menu-Gallery-1.jpg', 'original' => 'Menu-Gallery-1.jpg'],
                2 => ['filename' => 'Menu-Gallery-2.jpg', 'original' => 'Menu-Gallery-2.jpg'],
                3 => ['filename' => 'Menu-Gallery-3.jpg', 'original' => 'Menu-Gallery-3.jpg'],
                4 => ['filename' => 'Menu-Gallery-4.jpg', 'original' => 'Menu-Gallery-4.jpg'],
            ];

            foreach ($galleryImgs as $n => $meta) {
                DB::table('menu_images')->updateOrInsert(
                    [
                        'page_id' => $menuPid,
                        'original_name' => $meta['original'],
                    ],
                    [
                        'filename' => $meta['filename'],
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );

                $rowId = DB::table('menu_images')
                    ->where('page_id', $menuPid)
                    ->where('original_name', $meta['original'])
                    ->value('id');

                if ($rowId) {
                    DB::table('text_content')->updateOrInsert(
                        ['page_id' => $menuPid, 'key' => 'media_id_menu_gallery_slide_' . $n],
                        ['value' => (string) $rowId, 'created_at' => $now, 'updated_at' => $now]
                    );
                }
            }

            DB::table('menu_images')->updateOrInsert(
                [
                    'page_id' => $menuPid,
                    'original_name' => 'menu-categories.png',
                ],
                [
                    'filename' => 'headings/menu-categories.png',
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $menuCategoriesHeadingId = DB::table('menu_images')
                ->where('page_id', $menuPid)
                ->where('original_name', 'menu-categories.png')
                ->value('id');

            if ($menuCategoriesHeadingId) {
                DB::table('text_content')->updateOrInsert(
                    ['page_id' => $menuPid, 'key' => 'media_id_menu_categories_heading'],
                    ['value' => (string) $menuCategoriesHeadingId, 'created_at' => $now, 'updated_at' => $now]
                );
            }

            // Seed Menu header/footer logo slots as logo media for the menu page.
            $logoRows = [
                'media_id_menu_header_logo' => ['filename' => 'e2288ce5-75d7-4fc3-a4b2-8fb3642a811a.png', 'original' => 'Menu-Logo.png'],
                'media_id_menu_header_wordmark' => ['filename' => 'Menu-menutong-Sarap.png', 'original' => 'Menu-menutong-Sarap.png'],
                'media_id_menu_footer_logo' => ['filename' => 'e2288ce5-75d7-4fc3-a4b2-8fb3642a811a.png', 'original' => 'Menu-Logo.png'],
            ];

            foreach ($logoRows as $key => $meta) {
                DB::table('logos')->updateOrInsert(
                    ['page_id' => $menuPid, 'original_name' => $meta['original']],
                    [
                        'filename' => $meta['filename'],
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );

                $logoId = DB::table('logos')
                    ->where('page_id', $menuPid)
                    ->where('original_name', $meta['original'])
                    ->value('id');

                if ($logoId) {
                    DB::table('text_content')->updateOrInsert(
                        ['page_id' => $menuPid, 'key' => $key],
                        ['value' => (string) $logoId, 'created_at' => $now, 'updated_at' => $now]
                    );
                }
            }

            // Footer other brand logos (Ultrafood + Nordic) for the Menu page.
            $otherBrandLogos = [
                'media_id_menu_footer_ultrafood_logo' => ['filename' => '65480775-6edc-4e3e-b8c4-f989fe69c17c.png', 'original' => 'Ultrafood-Distributors-Inc-Logo.png'],
                'media_id_menu_footer_nordic_logo'    => ['filename' => 'Korpala-Nordic-Logo.png', 'original' => 'Korpala-Nordic-Logo.png'],
            ];

            foreach ($otherBrandLogos as $key => $meta) {
                DB::table('logos')->updateOrInsert(
                    ['page_id' => $menuPid, 'original_name' => $meta['original']],
                    [
                        'filename' => $meta['filename'],
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );

                $logoId = DB::table('logos')
                    ->where('page_id', $menuPid)
                    ->where('original_name', $meta['original'])
                    ->value('id');

                if ($logoId) {
                    DB::table('text_content')->updateOrInsert(
                        ['page_id' => $menuPid, 'key' => $key],
                        ['value' => (string) $logoId, 'created_at' => $now, 'updated_at' => $now]
                    );
                }
            }
        }

        $this->command->info(
            'ExistingResourcesSeeder: seeded ' .
            '3 carousel + 7 history BG + 3 logos + 3 product + 6 recipe images ' .
            'for page "' . $page->slug . '" (id=' . $pid . '), plus menu/nordic text defaults.'
        );
    }
}
