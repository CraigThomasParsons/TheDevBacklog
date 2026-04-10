<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class MasonChatController extends Controller
{
    public function index(): View
    {
        return view('mason.chat');
    }
}
