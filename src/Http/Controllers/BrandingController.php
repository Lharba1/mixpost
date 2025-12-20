<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inovector\Mixpost\Models\Branding;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    /**
     * Show branding settings
     */
    public function index(Request $request)
    {
        $branding = Branding::allAsArray();

        if ($request->wantsJson()) {
            return response()->json($branding);
        }

        return Inertia::render('Branding', [
            'branding' => $branding,
        ]);
    }

    /**
     * Update branding settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'app_name' => 'nullable|string|max:100',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'footer_text' => 'nullable|string|max:500',
            'hide_powered_by' => 'nullable|boolean',
            'custom_css' => 'nullable|string|max:10000',
        ]);

        // Update text settings
        if ($request->has('app_name')) {
            Branding::set('app_name', $request->app_name, 'text');
        }

        if ($request->has('primary_color')) {
            Branding::set('primary_color', $request->primary_color, 'color');
        }

        if ($request->has('secondary_color')) {
            Branding::set('secondary_color', $request->secondary_color, 'color');
        }

        if ($request->has('footer_text')) {
            Branding::set('footer_text', $request->footer_text, 'text');
        }

        if ($request->has('hide_powered_by')) {
            Branding::set('hide_powered_by', $request->hide_powered_by ? '1' : '0', 'boolean');
        }

        if ($request->has('custom_css')) {
            Branding::set('custom_css', $request->custom_css, 'text');
        }

        return back()->with('success', 'Branding updated successfully.');
    }

    /**
     * Upload logo (light mode)
     */
    public function uploadLogoLight(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $path = $request->file('logo')->store('branding', 'public');
        Branding::set('logo_light', '/storage/' . $path, 'image');

        return back()->with('success', 'Logo uploaded successfully.');
    }

    /**
     * Upload logo (dark mode)
     */
    public function uploadLogoDark(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $path = $request->file('logo')->store('branding', 'public');
        Branding::set('logo_dark', '/storage/' . $path, 'image');

        return back()->with('success', 'Dark mode logo uploaded successfully.');
    }

    /**
     * Upload favicon
     */
    public function uploadFavicon(Request $request)
    {
        $request->validate([
            'favicon' => 'required|image|mimes:png,ico|max:512',
        ]);

        $path = $request->file('favicon')->store('branding', 'public');
        Branding::set('favicon', '/storage/' . $path, 'image');

        return back()->with('success', 'Favicon uploaded successfully.');
    }

    /**
     * Upload login background
     */
    public function uploadLoginBackground(Request $request)
    {
        $request->validate([
            'background' => 'required|image|mimes:png,jpg,jpeg|max:5120',
        ]);

        $path = $request->file('background')->store('branding', 'public');
        Branding::set('login_background', '/storage/' . $path, 'image');

        return back()->with('success', 'Login background uploaded successfully.');
    }

    /**
     * Remove a logo/image
     */
    public function removeImage(Request $request)
    {
        $request->validate([
            'key' => 'required|string|in:logo_light,logo_dark,favicon,login_background',
        ]);

        $currentValue = Branding::get($request->key);
        
        if ($currentValue) {
            // Try to delete the file
            $path = str_replace('/storage/', '', $currentValue);
            Storage::disk('public')->delete($path);
        }

        Branding::set($request->key, null, 'image');

        return back()->with('success', 'Image removed successfully.');
    }

    /**
     * Reset all branding to defaults
     */
    public function reset()
    {
        // Delete uploaded files
        $imageKeys = ['logo_light', 'logo_dark', 'favicon', 'login_background'];
        foreach ($imageKeys as $key) {
            $value = Branding::get($key);
            if ($value) {
                $path = str_replace('/storage/', '', $value);
                Storage::disk('public')->delete($path);
            }
        }

        // Reset to defaults
        Branding::set('app_name', 'Mixpost', 'text');
        Branding::set('logo_light', null, 'image');
        Branding::set('logo_dark', null, 'image');
        Branding::set('favicon', null, 'image');
        Branding::set('primary_color', '#6366f1', 'color');
        Branding::set('secondary_color', '#8b5cf6', 'color');
        Branding::set('login_background', null, 'image');
        Branding::set('footer_text', null, 'text');
        Branding::set('hide_powered_by', '0', 'boolean');
        Branding::set('custom_css', null, 'text');

        return back()->with('success', 'Branding reset to defaults.');
    }

    /**
     * Preview branding (returns CSS)
     */
    public function previewCss()
    {
        $branding = Branding::forFrontend();

        $css = ":root {\n";
        $css .= "  --primary-color: {$branding['primary_color']};\n";
        $css .= "  --secondary-color: {$branding['secondary_color']};\n";
        $css .= "}\n";

        if ($branding['custom_css']) {
            $css .= "\n/* Custom CSS */\n";
            $css .= $branding['custom_css'];
        }

        return response($css)->header('Content-Type', 'text/css');
    }
}
