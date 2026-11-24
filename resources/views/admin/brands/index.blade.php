@extends('layouts.admin')

@section('title', 'Brands')

@section('content')
<div class="admin-card">
    <div class="card-head"><h2>All brands</h2><a href="{{ route('admin.brands.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add brand</a></div>
    <div class="table-responsive">
        <table class="table admin-table">
            <thead><tr><th></th><th>Name</th><th>Products</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($brands as $brand)
                    <tr>
                        <td style="width:70px">@if ($brand->logo_url)<img src="{{ $brand->logo_url }}" style="height:32px" alt="">@endif</td>
                        <td class="fw-medium">{{ $brand->name }}</td>
                        <td>{{ $brand->products_count }}</td>
                        <td><span class="badge {{ $brand->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $brand->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.brands.edit', $brand) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.brands.destroy', $brand) }}" method="POST" class="d-inline" data-confirm="Delete brand “{{ $brand->name }}”? Its products will remain without a brand.">
                                @csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">No brands yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-pad">{{ $brands->links() }}</div>
</div>
@endsection
