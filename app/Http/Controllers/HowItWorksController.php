<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class HowItWorksController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.how-it-works');
    }
}
