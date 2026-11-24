@extends('layouts.admin')

@section('title', 'Banners')

@section('content')
<div class="admin-card">
    <div class="card-head"><h2>Homepage banners</h2><a href="{{ route('admin.banners.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add banner</a></div>
    <div class="table-responsive">
        <table class="table admin-table">
            <thead><tr><th></th><th>Title</th><th>Position</th><th>Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($banners as $b)
                    <tr>
                        <td style="width:140px">@if ($b->image_url)<img src="{{ $b->image_url }}" style="width:120px;height:60px;object-fit:cover" alt="">@endif</td>
                        <td class="fw-medium">{{ $b->title }}<div class="small text-muted">{{ Str::limit($b->subtitle, 60) }}</div></td>
                        <td>{{ \App\Models\Banner::POSITIONS[$b->position] ?? $b->position }}</td>
                        <td>{{ $b->sort_order }}</td>
                        <td><span class="badge {{ $b->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $b->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.banners.edit', $b) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.banners.destroy', $b) }}" method="POST" class="d-inline" data-confirm="Delete this banner?">
                                @csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">No banners yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
