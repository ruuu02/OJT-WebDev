<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuCategoryLineItem extends Model
{
    protected $table = 'menu_category_line_items';

    protected $fillable = [
        'category_slug',
        'title',
        'flavor_type',
        'price',
        'image_filename',
        'back_image_filename',
        'sort_order',
    ];

    public function imageUrl(): ?string
    {
        if (! $this->image_filename) {
            return null;
        }

        return asset('images/banner/'.$this->image_filename);
    }

    public function backImageUrl(): ?string
    {
        if (! $this->back_image_filename) {
            return null;
        }

        return asset('images/banner/'.$this->back_image_filename);
    }
}
