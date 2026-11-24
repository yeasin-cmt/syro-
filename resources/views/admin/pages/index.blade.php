@extends('layouts.admin')

@section('title', 'Pages')

@section('content')
<div class="admin-card">
    <div class="card-head"><h2>Content pages</h2><a href="{{ route('admin.pages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New page</a></div>
    <table class="table admin-table">
        <thead><tr><th>Title</th><th>Address</th><th>Type</th><th>Status</th><th>Updated</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
            @forelse ($pages as $page)
                <tr>
                    <td class="fw-medium">{{ $page->title }}</td>
                    <td class="small"><a href="{{ route('page', $page) }}" target="_blank">/pages/{{ $page->slug }}</a></td>
                    <td class="small">{{ $page->layout === 'faq' ? 'FAQ (questions)' : 'Standard' }}</td>
                    <td><span class="badge {{ $page->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $page->is_active ? 'Published' : 'Hidden' }}</span></td>
                    <td class="small">{{ $page->updated_at->diffForHumans() }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" class="d-inline" data-confirm="Delete page “{{ $page->title }}”? Links to it will stop working.">
                            @csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">No pages yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
