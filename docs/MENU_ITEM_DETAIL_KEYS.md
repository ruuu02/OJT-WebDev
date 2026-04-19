## Menu category item detail CMS keys

These keys are stored in `text_content` under the `menu` page and are used by item detail pages like:

- `/brands/menu/products/jelly-mixes/tiramisu-jelly`

The keys are **stable per item** because they use the item’s database ID (`menu_category_line_items.id`).

### For an item with ID = `{id}`

- **Description**: `menu_item_{id}_description`
- **Lazada URL**: `menu_item_{id}_lazada_url`
- **Shopee URL**: `menu_item_{id}_shopee_url`
- **TikTok URL**: `menu_item_{id}_tiktok_url`

### Optional global keys

- **Kicker above title** (defaults to `PRODUCTS`): `menu_product_kicker`

Category-level Menu product detail pages at `/brands/menu/products/{slug}` use:

- **Per-category description** (blank by default): `menu_product_{slug}_description`  
  - Examples:  
    - `menu_product_jelly-mixes_description`  
    - `menu_product_breading-mixes_description`  
    - `menu_product_powder-mixes_description`

