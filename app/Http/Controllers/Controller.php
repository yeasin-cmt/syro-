<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Build a URL slug that is unique within the model's table (soft-deleted rows included).
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    protected function uniqueSlug(string $model, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::lower(Str::random(8));
        $slug = $base;
        $i = 2;

        $query = fn (string $s) => (method_exists($model, 'withTrashed') ? $model::withTrashed() : $model::query())
            ->where('slug', $s)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        while ($query($slug)) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
