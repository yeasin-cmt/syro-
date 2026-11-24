@extends('layouts.admin')

@section('title', $page->exists ? 'Edit page' : 'New page')

@section('content')
<form action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" method="POST">
    @csrf
    @if ($page->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="admin-card">
                <div class="card-pad row g-3">
                    <div class="col-12"><label class="form-label">Title</label><input name="title" class="form-control" value="{{ old('title', $page->title) }}" required></div>
                    <div class="col-12"><label class="form-label">Introduction (shown under the title)</label><textarea name="intro" rows="2" class="form-control" maxlength="500">{{ old('intro', $page->intro) }}</textarea></div>
                    <div class="col-12">
                        <label class="form-label">Content</label>
                        <textarea name="body" rows="22" class="form-control" style="font-family: ui-monospace, Consolas, monospace; font-size: .85rem">{{ old('body', $page->body) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="admin-card mb-3">
                <div class="card-head"><h2>Publish</h2></div>
                <div class="card-pad">
                    <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $page->is_active))><label class="form-check-label" for="is_active">Published (visible to everyone)</label></div>
                    <label class="form-label">Page address</label>
                    <div class="input-group mb-3"><span class="input-group-text small">/pages/</span><input name="slug" class="form-control" value="{{ old('slug', $page->slug) }}" placeholder="auto from title"></div>
                    <label class="form-label">Page type</label>
                    <select name="layout" class="form-select mb-3">
                        <option value="standard" @selected(old('layout', $page->layout) === 'standard')>Standard article</option>
                        <option value="faq" @selected(old('layout', $page->layout) === 'faq')>FAQ — headings become expandable questions</option>
                    </select>
                    <button class="btn btn-primary w-100 mb-2">Save page</button>
                    @if ($page->exists)<a href="{{ route('page', $page) }}" target="_blank" class="btn btn-light w-100">View page <i class="bi bi-box-arrow-up-right"></i></a>@endif
                </div>
            </div>
            <div class="admin-card">
                <div class="card-head"><h2>How to format</h2></div>
                <div class="card-pad small">
                    <p class="mb-2"><code>## Heading</code> — starts a new section (a question on FAQ pages)</p>
                    <p class="mb-2"><code>- Item</code> — bullet point</p>
                    <p class="mb-0">Leave an empty line between paragraphs.</p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
