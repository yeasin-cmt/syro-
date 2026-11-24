@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
<div class="admin-card">
    <div class="card-head"><h2>All categories</h2><a href="{{ route('admin.categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add category</a></div>
    <div class="table-responsive">
        <table class="table admin-table">
            <thead><tr><th></th><th>Name</th><th>Products</th><th>Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($categories as $cat)
                    @foreach (collect([$cat])->merge($cat->children) as $row)
                        <tr>
                            <td style="width:60px">@if ($row->image_url)<img src="{{ $row->image_url }}" class="admin-thumb" alt="">@endif</td>
                            <td class="{{ $row->parent_id ? 'ps-5' : 'fw-medium' }}">@if ($row->parent_id)<span class="text-muted">↳</span> @endif{{ $row->name }}
                                @if ($row->is_featured)<span class="badge text-bg-light ms-1">Featured</span>@endif</td>
                            <td>{{ $row->products_count }}</td>
                            <td>{{ $row->sort_order }}</td>
                            <td><span class="badge {{ $row->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $row->is_active ? 'Active' : 'Hidden' }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.categories.edit', $row) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.categories.destroy', $row) }}" method="POST" class="d-inline" data-confirm="Delete category “{{ $row->name }}”?">
                                    @csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">No categories yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
