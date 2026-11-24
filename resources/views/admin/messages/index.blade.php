@extends('layouts.admin')

@section('title', 'Messages')

@section('content')
<div class="admin-card">
    <table class="table admin-table">
        <thead><tr><th>From</th><th>Subject</th><th>Received</th><th></th></tr></thead>
        <tbody>
            @forelse ($messages as $m)
                <tr class="{{ $m->is_read ? '' : 'fw-semibold' }}">
                    <td>@unless ($m->is_read)<span class="badge text-bg-warning me-1">New</span>@endunless{{ $m->name }}<div class="small text-muted fw-normal">{{ $m->email }}</div></td>
                    <td><a href="{{ route('admin.messages.show', $m) }}">{{ $m->subject }}</a></td>
                    <td class="small fw-normal">{{ $m->created_at->diffForHumans() }}</td>
                    <td class="text-end"><a href="{{ route('admin.messages.show', $m) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-5">No messages yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="card-pad">{{ $messages->links() }}</div>
</div>
@endsection
