@extends('layouts.admin')

@section('title', 'Message')

@section('content')
<div class="admin-card" style="max-width: 820px">
    <div class="card-head">
        <h2>{{ $message->subject }}</h2>
        <span class="small text-muted">{{ $message->created_at->format('d M Y, h:i A') }}</span>
    </div>
    <div class="card-pad">
        <p class="mb-1"><strong>{{ $message->name }}</strong> · <a href="mailto:{{ $message->email }}">{{ $message->email }}</a> @if ($message->phone) · {{ $message->phone }} @endif</p>
        <hr>
        <p style="white-space: pre-line">{{ $message->message }}</p>
        <div class="d-flex gap-2 mt-4">
            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subject) }}" class="btn btn-primary"><i class="bi bi-reply me-1"></i>Reply by email</a>
            <form action="{{ route('admin.messages.destroy', $message) }}" method="POST" data-confirm="Delete this message?">
                @csrf @method('DELETE')<button class="btn btn-outline-danger">Delete</button>
            </form>
            <a href="{{ route('admin.messages.index') }}" class="btn btn-light ms-auto">Back</a>
        </div>
    </div>
</div>
@endsection
