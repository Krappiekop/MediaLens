<?php

namespace App\Http\Controllers;

use App\Categorie;
use App\Models\Gebeurtenis;
use Illuminate\Database\Eloquent\Builder;

class HomeController extends Controller
{
    private int $aantalPerBlok = 5;

    private int $aantalBlindspots = 3;

    private int $blindspotDagen = 7;

    /**
     * Homepage: hot gebeurtenissen, blindspots, de laatste per onderwerp en de gebeurtenissen met de meeste artikelen.
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

        $vanDezeWeek = Gebeurtenis::zichtbaar()
            ->voorKaart()
            ->whereHas('artikelen', function (Builder $artikelQuery): void {
                $artikelQuery->where('publicatiedatum', '>=', now()->subDays($this->blindspotDagen));
            })
            ->orderByDesc('artikelen_count')
            ->nieuwsteEerst()
            ->get();

        $blindspots = collect(['links', 'rechts'])->mapWithKeys(fn (string $kant): array => [
            $kant => $vanDezeWeek
                ->filter(fn (Gebeurtenis $gebeurtenis): bool => $gebeurtenis->blindspotKant() === $kant)
                ->take($this->aantalBlindspots),
        ]);

        return view('home', [
            'hot' => $hot,
            'blindspots' => $blindspots,
            'perCategorie' => $perCategorie,
            'meesteArtikelen' => $meesteArtikelen,
        ]);
    }
}
