<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index()
    {
        return view('admin.pages.index', ['pages' => Page::orderBy('title')->get()]);
    }

    public function create()
    {
        return view('admin.pages.form', ['page' => new Page(['is_active' => true, 'layout' => 'standard'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug(Page::class, $data['slug'] ?: $data['title']);
        $page = Page::create($data);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page created.');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $data = $this->validated($request, $page);
        $data['slug'] = $this->uniqueSlug(Page::class, $data['slug'] ?: $data['title'], $page->id);
        $page->update($data);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page saved.');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', "Page “{$page->title}” deleted.");
    }

    protected function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'alpha_dash', 'max:150'],
            'layout' => ['required', Rule::in(['standard', 'faq'])],
            'intro' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:50000'],
        ]);
        $data['slug'] = strtolower((string) ($data['slug'] ?? ''));
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
