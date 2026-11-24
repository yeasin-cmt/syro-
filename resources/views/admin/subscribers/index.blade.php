@extends('layouts.admin')

@section('title', 'Newsletter subscribers')

@section('content')
<div class="admin-card" style="max-width: 900px">
    <div class="card-head">
        <form method="GET" class="d-flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search email" style="width: 240px">
            <button class="btn btn-outline-primary">Search</button>
        </form>
        <a href="{{ route('admin.subscribers.export') }}" class="btn btn-primary"><i class="bi bi-download me-1"></i>Export CSV</a>
    </div>
    <table class="table admin-table">
        <thead><tr><th>Email</th><th>Subscribed</th><th></th></tr></thead>
        <tbody>
            @forelse ($subscribers as $s)
                <tr>
                    <td>{{ $s->email }}</td>
                    <td class="small">{{ $s->created_at->format('d M Y, h:i A') }}</td>
                    <td class="text-end">
                        <form action="{{ route('admin.subscribers.destroy', $s) }}" method="POST" data-confirm="Remove {{ $s->email }}?">
                            @csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-5">No subscribers yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="card-pad">{{ $subscribers->links() }}</div>
</div>
@endsection
