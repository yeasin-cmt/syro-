<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    public function show(Page $page)
    {
        abort_unless($page->is_active || auth()->user()?->isAdmin(), 404);

        return view('pages.static', ['page' => $page]);
    }
}
