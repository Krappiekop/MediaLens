<?php

namespace App\Http\Controllers;

use App\Categorie;
use App\Models\Gebeurtenis;

class HomeController extends Controller
{
    private int $aantalPerBlok = 5;

    /**
     * Homepage: hot gebeurtenissen, de laatste per onderwerp en de gebeurtenissen met de meeste artikelen.
     */
    public function index()
    {
        $hot = Gebeurtenis::voorKaart()
            ->hot()
            ->limit($this->aantalPerBlok)
            ->get();

        $perCategorie = collect(Categorie::menu())
            ->mapWithKeys(fn (Categorie $categorie): array => [
                $categorie->value => Gebeurtenis::zichtbaar()
                    ->voorKaart()
                    ->where('categorie', $categorie)
                    ->nieuwsteEerst()
                    ->limit($this->aantalPerBlok)
                    ->get(),
            ])
            ->filter(fn ($gebeurtenissen): bool => $gebeurtenissen->isNotEmpty());

        $meesteArtikelen = Gebeurtenis::zichtbaar()
            ->voorKaart()
            ->orderByDesc('artikelen_count')
            ->nieuwsteEerst()
            ->limit($this->aantalPerBlok)
            ->get();

        return view('home', [
            'hot' => $hot,
            'perCategorie' => $perCategorie,
            'meesteArtikelen' => $meesteArtikelen,
        ]);
    }
}
