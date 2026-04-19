<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuRecipeCategory extends Model
{
    protected $table = 'menu_recipe_categories';

    protected $fillable = [
        'name',
        'slug',
        'icon_filename',
        'sort_order',
    ];

    public function recipes(): HasMany
    {
        return $this->hasMany(MenuRecipe::class, 'category_id');
    }

    public function iconUrl(): ?string
    {
        if (! $this->icon_filename) {
            return null;
        }

        return asset('images/menu/'.$this->icon_filename);
    }
}

