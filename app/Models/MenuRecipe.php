<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuRecipe extends Model
{
    protected $table = 'menu_recipes';

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'image_filename',
        'description',
        'servings',
        'difficulty',
        'calories',
        'ingredients',
        'procedures',
        'sort_order',
    ];

    protected $casts = [
        'ingredients' => 'array',
        'procedures' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuRecipeCategory::class, 'category_id');
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_filename) {
            return null;
        }

        return asset('images/menu/'.$this->image_filename);
    }
}
