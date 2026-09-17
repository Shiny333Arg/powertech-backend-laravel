<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HelpController extends Controller
{
    /**
     * Muestra la pantalla de ayuda del sistema.
     */
    public function index(): View
    {
        return view('help.index');
    }
}