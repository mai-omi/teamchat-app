<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ChannelController extends Controller
{
    public function index(): View
    {
        return view('channels.index');
    }
}
