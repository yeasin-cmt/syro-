@if (setting('favicon'))
    <link rel="icon" href="{{ media_url(setting('favicon')) }}">
@else
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
@endif
