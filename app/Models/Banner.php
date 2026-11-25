<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    public const POSITIONS = ['hero' => 'Hero slider', 'promo' => 'Promotional banner'];

    protected $fillable = ['title', 'subtitle', 'eyebrow', 'image', 'link', 'button_text', 'position', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return media_url($this->image);
    }
}
