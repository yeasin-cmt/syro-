<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190']]);

        $subscriber = NewsletterSubscriber::firstOrCreate(['email' => strtolower($data['email'])]);
        $message = $subscriber->wasRecentlyCreated
            ? 'Welcome to SYRO. You are now on the list.'
            : 'You are already subscribed — thank you!';

        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : back()->with('success', $message);
    }
}
