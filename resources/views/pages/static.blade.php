@extends('layouts.app')

@section('title', $page->title)
@section('meta_description', (string) ($page->intro ?: $page->title))

@section('content')
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">SYRO</span>
        <h1>{{ $page->title }}</h1>
        @if ($page->intro)<p class="text-muted mt-2 mb-0" style="max-width: 62ch">{{ $page->intro }}</p>@endif
        @unless ($page->is_active)<span class="badge text-bg-warning mt-2">Hidden — only admins can see this page</span>@endunless
    </div>
</section>
<section class="section pt-5">
    <div class="container" style="max-width: 820px">
        @if ($page->layout === 'faq')
            <div class="accordion accordion-flush" id="faq">
                @foreach ($page->sections() as $i => $section)
                    <div class="accordion-item bg-transparent">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-transparent px-0" type="button" data-bs-toggle="collapse" data-bs-target="#faq-{{ $i }}" style="font-family:Inter,sans-serif;font-weight:500">{{ $section['heading'] }}</button>
                        </h2>
                        <div id="faq-{{ $i }}" class="accordion-collapse collapse" data-bs-parent="#faq">
                            <div class="accordion-body px-0 text-muted">@include('pages._blocks', ['blocks' => $section['blocks']])</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="prose">
                @foreach ($page->sections() as $section)
                    @if ($section['heading'])<h2 class="h3 mt-5 mb-3">{{ $section['heading'] }}</h2>@endif
                    @include('pages._blocks', ['blocks' => $section['blocks']])
                @endforeach
            </div>
        @endif
        <div class="mt-5 pt-4 border-top border-line small text-muted">Questions? <a href="{{ route('contact') }}" class="text-decoration-underline">Contact our team</a>.</div>
    </div>
</section>
@endsection
