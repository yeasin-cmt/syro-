<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Manages every account in the store: customers and administrators.
 */
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->withCount('orders')
            ->withSum(['orders as spent' => fn ($q) => $q->whereNotIn('status', ['cancelled', 'returned'])], 'total')
            ->when($request->role === 'admin', fn ($q) => $q->where('role', User::ROLE_ADMIN))
            ->when($request->role !== 'admin' && $request->role !== 'all', fn ($q) => $q->where('role', User::ROLE_CUSTOMER))
            ->when($request->q, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.customers.index', ['customers' => $users]);
    }

    public function create()
    {
        return view('admin.customers.form', ['user' => new User()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $user = new User();
        $user->fill($data);
        $user->password = $data['password'];
        $user->forceFill(['role' => $data['role'], 'is_active' => $request->boolean('is_active'), 'email_verified_at' => now()])->save();

        return redirect()->route('admin.customers.show', $user)->with('success', 'Account created.');
    }

    public function show(User $user)
    {
        return view('admin.customers.show', [
            'customer' => $user,
            'orders' => $user->orders()->withCount('items')->paginate(10),
        ]);
    }

    public function edit(User $user)
    {
        return view('admin.customers.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        $isSelf = $user->id === $request->user()->id;

        if ($isSelf && ($data['role'] !== User::ROLE_ADMIN || ! $request->boolean('is_active'))) {
            return back()->withInput()->with('error', 'You cannot remove your own admin access or deactivate yourself.');
        }
        if ($user->isAdmin() && $data['role'] !== User::ROLE_ADMIN && $this->isLastAdmin($user)) {
            return back()->withInput()->with('error', 'The store needs at least one administrator.');
        }

        $user->fill($data);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->forceFill(['role' => $data['role'], 'is_active' => $request->boolean('is_active')])->save();

        return redirect()->route('admin.customers.show', $user)->with('success', 'Account updated.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->isAdmin() && $this->isLastAdmin($user)) {
            return back()->with('error', 'The store needs at least one administrator.');
        }

        // Orders stay in the system (user_id becomes empty) so sales history is not lost.
        $user->delete();

        return redirect()->route('admin.customers.index')->with('success', "Account “{$user->name}” deleted. Their orders were kept.");
    }

    public function toggle(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        return back()->with('success', $user->is_active ? 'Account activated.' : 'Account deactivated.');
    }

    protected function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::in([User::ROLE_CUSTOMER, User::ROLE_ADMIN])],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ]);
    }

    protected function isLastAdmin(User $user): bool
    {
        return User::where('role', User::ROLE_ADMIN)->whereKeyNot($user->id)->doesntExist();
    }
}
