<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'hero_heading',
        'hero_subtext',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function contents()
    {
        return $this->hasMany(PageContent::class);
    }

    public function contactInfo()
    {
        return $this->hasMany(ContactInfo::class);
    }
}
