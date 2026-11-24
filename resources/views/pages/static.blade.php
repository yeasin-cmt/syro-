@extends('layouts.app')

@section('title', $page['title'])
@section('meta_description', $page['intro'])

@section('content')
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">SYRO</span>
        <h1>{{ $page['title'] }}</h1>
        <p class="text-muted mt-2 mb-0" style="max-width: 62ch">{{ $page['intro'] }}</p>
    </div>
</section>
<section class="section pt-5">
    <div class="container" style="max-width: 820px">
        @if ($slug === 'faq')
            <div class="accordion accordion-flush" id="faq">
                @foreach ($page['sections'] as $question => $answers)
                    <div class="accordion-item bg-transparent">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-transparent px-0" type="button" data-bs-toggle="collapse" data-bs-target="#faq-{{ $loop->index }}" style="font-family:Inter,sans-serif;font-weight:500">{{ $question }}</button>
                        </h2>
                        <div id="faq-{{ $loop->index }}" class="accordion-collapse collapse" data-bs-parent="#faq">
                            <div class="accordion-body px-0 text-muted">@foreach ($answers as $a)<p class="mb-2">{{ $a }}</p>@endforeach</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="prose">
                @foreach ($page['sections'] as $heading => $paragraphs)
                    <h2 class="h3 mt-5 mb-3">{{ $heading }}</h2>
                    @foreach ($paragraphs as $p)<p>{{ $p }}</p>@endforeach
                @endforeach
            </div>
        @endif
        <div class="mt-5 pt-4 border-top border-line small text-muted">Questions? <a href="{{ route('contact') }}" class="text-decoration-underline">Contact our team</a>.</div>
    </div>
</section>
@endsection
