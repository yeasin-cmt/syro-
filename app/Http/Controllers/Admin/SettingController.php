<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $values = collect(Setting::DEFAULTS)->mapWithKeys(fn ($default, $key) => [$key => setting($key)]);

        return view('admin.settings', ['s' => $values]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:60'],
            'store_tagline' => ['nullable', 'string', 'max:120'],
            'store_email' => ['required', 'email', 'max:190'],
            'store_phone' => ['required', 'string', 'max:30'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'announcement_text' => ['nullable', 'string', 'max:200'],
            'shipping_inside_dhaka' => ['required', 'numeric', 'min:0'],
            'shipping_outside_dhaka' => ['required', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
            'bkash_number' => ['nullable', 'string', 'max:20'],
            'social_facebook' => ['nullable', 'url', 'max:255'],
            'social_instagram' => ['nullable', 'url', 'max:255'],
            'social_youtube' => ['nullable', 'url', 'max:255'],
            'social_tiktok' => ['nullable', 'url', 'max:255'],
        ]);

        $data['cod_enabled'] = $request->boolean('cod_enabled') ? '1' : '0';
        $data['bkash_enabled'] = $request->boolean('bkash_enabled') ? '1' : '0';

        if ($data['cod_enabled'] === '0' && $data['bkash_enabled'] === '0') {
            return back()->withInput()->withErrors(['cod_enabled' => 'At least one payment method must stay enabled.']);
        }
        if ($data['bkash_enabled'] === '1' && blank($data['bkash_number'])) {
            return back()->withInput()->withErrors(['bkash_number' => 'Enter the bKash merchant/personal number customers should pay to.']);
        }

        Setting::setMany(array_map(fn ($v) => $v ?? '', $data));

        return back()->with('success', 'Settings saved.');
    }
}
