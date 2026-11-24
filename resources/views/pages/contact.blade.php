@extends('layouts.app')

@section('title', 'Contact us')

@section('content')
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">We're here to help</span>
        <h1>Contact us</h1>
    </div>
</section>
<section class="section pt-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <div class="mb-4"><div class="form-label">Call us</div><p class="mb-0">{{ setting('store_phone') }}</p><p class="small text-muted">Sat–Thu, 10am – 8pm</p></div>
                <div class="mb-4"><div class="form-label">Email</div><p><a href="mailto:{{ setting('store_email') }}">{{ setting('store_email') }}</a></p></div>
                <div class="mb-4"><div class="form-label">Visit</div><p class="text-muted">{{ setting('store_address') }}</p></div>
            </div>
            <div class="col-lg-8">
                <form action="{{ route('contact.store') }}" method="POST" class="panel">
                    @csrf
                    <input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="c-name">Name</label>
                            <input id="c-name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', auth()->user()?->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="c-email">Email</label>
                            <input id="c-email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', auth()->user()?->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="c-phone">Phone (optional)</label>
                            <input id="c-phone" name="phone" class="form-control" value="{{ old('phone') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="c-subject">Subject</label>
                            <input id="c-subject" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" required>
                            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="c-message">Message</label>
                            <textarea id="c-message" name="message" rows="5" class="form-control @error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
                            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4">Send message</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
