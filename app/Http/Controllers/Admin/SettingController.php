<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    /** Branding image slots and their labels. */
    public const BRANDING = [
        'logo' => 'Main logo',
        'logo_light' => 'Logo for dark backgrounds',
        'favicon' => 'Favicon',
    ];

    /**
     * Upload / replace the logo images and change the logo size.
     */
    public function updateBranding(Request $request)
    {
        $image = ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'];
        $request->validate([
            'logo' => $image,
            'logo_light' => $image,
            'favicon' => ['nullable', 'file', 'mimes:png,svg,ico', 'max:512'],
            'logo_height' => ['required', 'integer', 'min:20', 'max:120'],
        ], [
            'logo.mimes' => 'The logo must be a PNG, JPG, WEBP or SVG image.',
            'logo_light.mimes' => 'The logo must be a PNG, JPG, WEBP or SVG image.',
            'favicon.mimes' => 'The favicon must be a PNG, SVG or ICO file.',
        ]);

        $values = ['logo_height' => (string) $request->integer('logo_height')];

        foreach (array_keys(self::BRANDING) as $slot) {
            if (! $request->hasFile($slot)) {
                continue;
            }
            $path = $request->file($slot)->store('branding', 'public');
            if (! $path) {
                return back()->withErrors([$slot => 'Upload failed. Please try again.']);
            }
            $this->deleteFile(setting($slot));
            $values[$slot] = $path;
        }

        Setting::setMany($values);

        return back()->with('success', 'Logo settings saved.');
    }

    /**
     * Remove one branding image (the site falls back to the store name / default icon).
     */
    public function destroyBranding(string $slot)
    {
        $this->deleteFile(setting($slot));
        Setting::setMany([$slot => '']);

        return back()->with('success', self::BRANDING[$slot].' removed.');
    }

    private function deleteFile(?string $path): void
    {
        if (! $path) {
            return;
        }
        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $e) {
            report($e); // the setting is still cleared; an orphaned file is harmless
        }
    }
}
