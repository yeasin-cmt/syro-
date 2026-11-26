{{--
    Store logo. Shows the uploaded logo image, or the store name as text when none is uploaded.
    variant="light" is for dark backgrounds (footer, admin sidebar): it uses the "logo for dark
    backgrounds" if uploaded, otherwise the main logo rendered in white.
--}}
@props(['variant' => 'dark', 'href' => null, 'height' => null])
@php
    $name = setting('store_name');
    $lightLogo = $variant === 'light' ? setting('logo_light') : null;
    $path = $lightLogo ?: setting('logo');
    $invert = $variant === 'light' && ! $lightLogo && $path;
    $tag = $href ? 'a' : 'span';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" aria-label="{{ $name }} home" @endif {{ $attributes->class(['syro-logo', 'syro-logo--img' => $path]) }}>@if ($path)<img src="{{ media_url($path) }}" alt="{{ $name }}" style="height: {{ (int) ($height ?? setting('logo_height', 40)) }}px" @class(['logo-invert' => $invert])>@else{{ $name }}@endif</{{ $tag }}>
