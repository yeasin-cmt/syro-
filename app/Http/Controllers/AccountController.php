<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        return view('account.dashboard', [
            'user' => $user,
            'recentOrders' => $user->orders()->withCount('items')->take(5)->get(),
            'stats' => [
                'orders' => $user->orders()->count(),
                'spent' => $user->orders()->where('status', 'delivered')->sum('total'),
                'wishlist' => $user->wishlist()->count(),
            ],
        ]);
    }

    public function orders(Request $request)
    {
        return view('account.orders', [
            'orders' => $request->user()->orders()->withCount('items')->paginate(10),
        ]);
    }

    public function showOrder(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        return view('account.order-show', ['order' => $order->load(['items.product.images', 'histories'])]);
    }

    public function cancelOrder(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        if (! $order->canBeCancelledByCustomer()) {
            return back()->with('error', 'This order can no longer be cancelled. Please contact support.');
        }

        $order->transitionTo('cancelled', 'Cancelled by customer.', $request->user()->id);

        return back()->with('success', "Order {$order->order_number} has been cancelled.");
    }

    public function profile(Request $request)
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
        ], ['phone.regex' => 'Enter a valid Bangladeshi mobile number.']);

        $user->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Password changed.');
    }

    protected function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 404);
    }
}
