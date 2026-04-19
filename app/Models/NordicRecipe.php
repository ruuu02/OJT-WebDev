<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NordicRecipe extends Model
{
    protected $table = 'nordic_recipes';

    protected $fillable = [
        'name',
        'slug',
        'image_filename',
        'description',
        'servings',
        'calories',
        'ingredients',
        'procedures',
        'sort_order',
    ];

    protected $casts = [
        'ingredients' => 'array',
        'procedures' => 'array',
    ];

    public function imageUrl(): ?string
    {
        if (! $this->image_filename) {
            return null;
        }

        return asset('images/nordic/recipes/'.$this->image_filename);
    }
}
