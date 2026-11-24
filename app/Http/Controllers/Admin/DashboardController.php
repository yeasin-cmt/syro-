<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index()
    {
        $revenueQuery = fn () => Order::whereNotIn('status', ['cancelled', 'returned']);

        // Daily revenue for the last 30 days (Asia/Dhaka dates).
        $from = now()->subDays(29)->startOfDay();
        $daily = $revenueQuery()->where('created_at', '>=', $from)->get(['created_at', 'total'])
            ->groupBy(fn ($o) => $o->created_at->format('Y-m-d'))
            ->map(fn ($g) => (float) $g->sum('total'));

        $chart = collect(CarbonPeriod::create($from, now()))->mapWithKeys(fn ($d) => [
            $d->format('d M') => $daily[$d->format('Y-m-d')] ?? 0,
        ]);

        return view('admin.dashboard', [
            'stats' => [
                'revenue_today' => $revenueQuery()->whereDate('created_at', today())->sum('total'),
                'revenue_month' => $revenueQuery()->where('created_at', '>=', now()->startOfMonth())->sum('total'),
                'orders_pending' => Order::where('status', 'pending')->count(),
                'orders_total' => Order::count(),
                'customers' => User::where('role', User::ROLE_CUSTOMER)->count(),
                'products' => Product::count(),
                'low_stock' => Product::where('stock', '<=', 5)->count(),
                'bkash_to_verify' => Order::where('payment_status', 'pending_verification')->count(),
                'reviews_pending' => Review::where('is_approved', false)->count(),
                'messages_unread' => ContactMessage::where('is_read', false)->count(),
            ],
            'chart' => $chart,
            'recentOrders' => Order::latest()->take(8)->get(),
            'lowStock' => Product::with('images')->where('stock', '<=', 5)->orderBy('stock')->take(6)->get(),
            'topProducts' => OrderItem::selectRaw('product_id, product_name, SUM(quantity) as qty, SUM(total) as revenue')
                ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled', 'returned']))
                ->groupBy('product_id', 'product_name')->orderByDesc('qty')->take(5)->get(),
        ]);
    }
}
