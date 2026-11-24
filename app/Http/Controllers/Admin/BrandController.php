<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    public function index()
    {
        return view('admin.brands.index', ['brands' => Brand::withCount('products')->orderBy('name')->paginate(30)]);
    }

    public function create()
    {
        return view('admin.brands.form', ['brand' => new Brand(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug(Brand::class, $data['name']);
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }
        Brand::create($data);

        return redirect()->route('admin.brands.index')->with('success', 'Brand created.');
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.form', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $this->validated($request);
        if ($data['name'] !== $brand->name) {
            $data['slug'] = $this->uniqueSlug(Brand::class, $data['name'], $brand->id);
        }
        if ($request->hasFile('logo')) {
            $brand->logo && Storage::disk('public')->delete($brand->logo);
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }
        $brand->update($data);

        return redirect()->route('admin.brands.index')->with('success', 'Brand updated.');
    }

    public function destroy(Brand $brand)
    {
        $brand->logo && Storage::disk('public')->delete($brand->logo);
        $brand->delete(); // products keep existing with brand_id set to NULL

        return back()->with('success', 'Brand deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        unset($data['logo']);

        return $data;
    }
}
