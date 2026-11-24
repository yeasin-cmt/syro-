@foreach ($blocks as $block)
    @if ($block['type'] === 'ul')
        <ul class="mb-3">@foreach ($block['content'] as $item)<li class="mb-1">{{ $item }}</li>@endforeach</ul>
    @else
        <p>{{ $block['content'] }}</p>
    @endif
@endforeach
