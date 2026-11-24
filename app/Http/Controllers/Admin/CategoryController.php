<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::roots()->with(['children' => fn ($q) => $q->withCount('products')])
            ->withCount('products')->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.form', [
            'category' => new Category(['is_active' => true]),
            'parents' => Category::roots()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug(Category::class, $data['name']);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($data);
        Cache::forget('syro.nav_categories');

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', [
            'category' => $category,
            'parents' => Category::roots()->whereKeyNot($category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request, $category);
        if ($data['name'] !== $category->name) {
            $data['slug'] = $this->uniqueSlug(Category::class, $data['name'], $category->id);
        }
        if ($request->hasFile('image')) {
            $category->image && Storage::disk('public')->delete($category->image);
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);
        Cache::forget('syro.nav_categories');

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->withTrashed()->exists() || $category->children()->exists()) {
            return back()->with('error', 'Move its products and sub-categories elsewhere before deleting this category.');
        }

        $category->image && Storage::disk('public')->delete($category->image);
        $category->delete();
        Cache::forget('syro.nav_categories');

        return back()->with('success', 'Category deleted.');
    }

    protected function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', Rule::exists('categories', 'id')->whereNull('parent_id'), Rule::notIn([$category?->id])],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        unset($data['image']);

        return $data;
    }
}
