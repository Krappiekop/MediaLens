<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gebeurtenis;

class GebeurtenisController extends Controller
{
    public function index()
    {
        $gebeurtenissen = Gebeurtenis::has('artikelen', '>=', 2)
            ->withCount('artikelen')
            ->get();

        return view('gebeurtenissen.index', [
            'gebeurtenissen' => $gebeurtenissen,
        ]);
    }
}
