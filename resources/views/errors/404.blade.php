<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found — SYRO</title>
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:500,600|inter:400,500,600" rel="stylesheet">
    @vite(['resources/scss/app.scss'])
</head>
<body class="d-flex align-items-center min-vh-100">
    <div class="container text-center py-5">
        <a href="{{ url('/') }}" class="syro-logo d-inline-block mb-5">SYRO</a>
        <div class="eyebrow">Error 404</div>
        <h1 class="section-title mt-2">This page has moved on</h1>
        <p class="text-muted mb-4">The page you were looking for doesn't exist or is no longer available.</p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="{{ url('/') }}" class="btn btn-primary">Back to home</a>
            <a href="{{ url('/shop') }}" class="btn btn-outline-primary">Visit the shop</a>
        </div>
    </div>
</body>
</html>
