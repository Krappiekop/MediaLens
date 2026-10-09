<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OverController extends Controller
{
    public function index()
    {
        return view('over');
    }
}
