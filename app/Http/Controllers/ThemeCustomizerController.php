<?php

namespace App\Http\Controllers;

/**
 * Theme Customizer — serves the theme builder page.
 * All theme logic is client-side (localStorage + CSS vars);
 * this controller just returns the Blade view.
 */
class ThemeCustomizerController extends Controller
{
    public function index()
    {
        return view('themes.customizer');
    }
}
