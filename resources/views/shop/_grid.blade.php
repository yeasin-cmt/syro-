@if ($products->isEmpty())
    <div class="empty-state">
        <i class="bi bi-search"></i>
        <h3 class="mt-3">No products match your filters</h3>
        <p class="text-muted">Try removing a filter or searching for something else.</p>
        <a href="{{ route('shop') }}" class="btn btn-outline-primary mt-2">Clear all filters</a>
    </div>
@else
    <div class="row row-cols-2 row-cols-md-3 g-3 g-lg-4">
        @foreach ($products as $product)
            <div class="col">@include('partials.product-card')</div>
        @endforeach
    </div>
    <div class="d-flex justify-content-center mt-5">
        {{ $products->links() }}
    </div>
@endif
