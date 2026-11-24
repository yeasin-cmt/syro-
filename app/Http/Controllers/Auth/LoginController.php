<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Admins land on the dashboard, customers on their account page.
     */
    protected function redirectTo(): string
    {
        return auth()->user()?->isAdmin() ? route('admin.dashboard') : route('account.dashboard');
    }

    /**
     * Deactivated accounts cannot sign in.
     */
    protected function authenticated(Request $request, $user)
    {
        if (! $user->is_active) {
            $this->guard()->logout();
            $request->session()->invalidate();

            return redirect()->route('login')
                ->withErrors(['email' => 'This account has been deactivated. Please contact support.']);
        }
    }
}
