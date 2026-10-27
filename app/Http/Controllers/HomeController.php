<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Beranda publik. Seluruh data dimuat real-time dari GET /api/v1/portal.
     */
    public function __invoke(): View
    {
        return view('welcome');
    }
}
