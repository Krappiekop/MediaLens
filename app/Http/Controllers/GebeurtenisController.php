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
            ->withExists('samenvatting')
            ->get();

        return view('gebeurtenissen.index', [
            'gebeurtenissen' => $gebeurtenissen,
        ]);
    }

    public function show(Gebeurtenis $gebeurtenis)
    {
        $gebeurtenis->load('samenvatting', 'artikelen.bron');

        $bakjes = [
            'left' => 'Links',
            'left-center' => 'Links',
            'neutral' => 'Midden',
            'right-center' => 'Rechts',
            'right' => 'Rechts',
        ];

        $artikelenPerOrientatie = $gebeurtenis->artikelen->groupBy(function ($artikel) use ($bakjes) {
            $orientatie = $artikel->bron->orientatie;

            return $bakjes[$orientatie] ?? $orientatie;
        });

        $volgorde = [
            'Links' => 1,
            'Midden' => 2,
            'Rechts' => 3,
        ];
        $artikelenPerOrientatie = $artikelenPerOrientatie->sortBy(function ($artikelen, $bakje) use ($volgorde) {
            return $volgorde[$bakje] ?? 4;
        });

        return view('gebeurtenissen.show', [
            'gebeurtenis' => $gebeurtenis,
            'artikelenPerOrientatie' => $artikelenPerOrientatie,
        ]);
    }
}
