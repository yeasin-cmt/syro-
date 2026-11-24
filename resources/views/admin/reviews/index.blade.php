@extends('layouts.admin')

@section('title', 'Reviews')

@section('content')
<div class="d-flex gap-2 mb-3">
    @foreach (['' => 'All', 'pending' => 'Awaiting approval', 'approved' => 'Published'] as $key => $label)
        <a href="{{ route('admin.reviews.index', array_filter(['status' => $key])) }}" class="btn btn-sm {{ request('status', '') === $key ? 'btn-primary' : 'btn-light' }}">{{ $label }}</a>
    @endforeach
</div>
<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table">
            <thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th style="width:40%">Review</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($reviews as $r)
                    <tr>
                        <td class="small">{{ $r->product?->name ?? 'Deleted product' }}</td>
                        <td class="small">{{ $r->user?->name }}<div class="text-muted">{{ $r->created_at->format('d M Y') }}</div></td>
                        <td class="text-nowrap text-accent">@for ($s = 1; $s <= 5; $s++)<i class="bi bi-star{{ $s <= $r->rating ? '-fill' : '' }}"></i>@endfor</td>
                        <td class="small">@if ($r->title)<strong>{{ $r->title }}</strong><br>@endif{{ Str::limit($r->comment, 220) }}</td>
                        <td><span class="badge {{ $r->is_approved ? 'text-bg-success' : 'text-bg-warning' }}">{{ $r->is_approved ? 'Published' : 'Pending' }}</span></td>
                        <td class="text-end text-nowrap">
                            <form action="{{ route('admin.reviews.update', $r) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="is_approved" value="{{ $r->is_approved ? 0 : 1 }}">
                                <button class="btn btn-sm {{ $r->is_approved ? 'btn-light' : 'btn-success' }}">{{ $r->is_approved ? 'Hide' : 'Approve' }}</button>
                            </form>
                            <form action="{{ route('admin.reviews.destroy', $r) }}" method="POST" class="d-inline" data-confirm="Delete this review?">
                                @csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">No reviews.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-pad">{{ $reviews->links() }}</div>
</div>
@endsection
