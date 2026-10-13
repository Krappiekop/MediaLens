<?php

namespace App\Http\Controllers;

use App\Models\Bron;
use App\Models\Gebeurtenis;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class BronController extends Controller
{
    private int $aantalLaatsteArtikelen = 10;

    /**
     * Alle bronnen, gegroepeerd in Links, Midden en Rechts.
     */
    public function index(): View
    {
        $bronnen = Bron::withCount('artikelen')
            ->orderBy('naam')
            ->get();

        $bronnenPerBakje = collect(['Links', 'Midden', 'Rechts', 'Onbekend'])
            ->mapWithKeys(fn (string $bakje): array => [
                $bakje => $bronnen->filter(fn (Bron $bron): bool => $bron->bakje() === $bakje)->values(),
            ])
            ->filter(fn ($bronnen): bool => $bronnen->isNotEmpty());

        return view('bronnen.index', [
            'bronnenPerBakje' => $bronnenPerBakje,
            'aantalBronnen' => $bronnen->count(),
        ]);
    }

    /**
     * Eén bron: label, betrouwbaarheid, in hoeveel gebeurtenissen die voorkomt en de laatste artikelen.
     */
    public function show(Bron $bron): View
    {
        $aantalGebeurtenissen = Gebeurtenis::zichtbaar()
            ->whereHas('artikelen', function (Builder $artikelQuery) use ($bron): void {
                $artikelQuery->where('bron_id', $bron->id);
            })
            ->count();

        $laatsteArtikelen = $bron->artikelen()
            ->with('gebeurtenis:id,onderwerp')
            ->orderByDesc('publicatiedatum')
            ->orderByDesc('id')
            ->limit($this->aantalLaatsteArtikelen)
            ->get();

        return view('bronnen.show', [
            'bron' => $bron,
            'aantalArtikelen' => $bron->artikelen()->count(),
            'aantalGebeurtenissen' => $aantalGebeurtenissen,
            'laatsteArtikelen' => $laatsteArtikelen,
        ]);
    }
}
