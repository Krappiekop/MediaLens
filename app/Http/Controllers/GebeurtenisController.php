<?php

namespace App\Http\Controllers;

use App\Categorie;
use App\Models\Gebeurtenis;
use App\Services\GebeurtenisMatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GebeurtenisController extends Controller
{
    private int $aantalTrending = 3;

    private int $aantalPreview = 3;

    /**
     * Lijst met alle gebeurtenissen, of alleen die van één onderwerp via /onderwerpen/{categorie}.
     */
    public function index(Request $request, ?Categorie $categorie = null)
    {
        $zoekterm = $request->query('q');
        $zoekterm = is_string($zoekterm) ? trim($zoekterm) : '';
        $filter = $request->query('filter');
        $filter = in_array($filter, ['met', 'zonder'], true) ? $filter : 'alle';
        $perspectief = $request->query('perspectief');
        $perspectief = in_array($perspectief, ['links', 'midden', 'rechts'], true) ? $perspectief : 'alle';

        $query = Gebeurtenis::zichtbaar()
            ->voorKaart()
            ->nieuwsteEerst();

        if ($categorie !== null) {
            $query->where('categorie', $categorie);
        }

        // Trending alleen op een onderwerppagina zonder zoekterm of filters. Die gebeurtenissen gaan
        // op elke pagina uit de lijst, zodat ze niet dubbel staan en de paginering niet verschuift.
        $trending = collect();

        if ($categorie !== null && $zoekterm === '' && $filter === 'alle' && $perspectief === 'alle') {
            $trending = Gebeurtenis::voorKaart()
                ->hot()
                ->where('categorie', $categorie)
                ->limit($this->aantalTrending)
                ->get();

            $query->whereKeyNot($trending->modelKeys());
        }

        if ($zoekterm !== '') {
            $query->where(function (Builder $query) use ($zoekterm): void {
                $query->where('onderwerp', 'like', "%{$zoekterm}%")
                    ->orWhereHas('artikelen', function (Builder $artikelQuery) use ($zoekterm): void {
                        $artikelQuery->where('titel', 'like', "%{$zoekterm}%");
                    });
            });
        }

        if ($filter === 'met') {
            $query->has('samenvatting');
        } elseif ($filter === 'zonder') {
            $query->doesntHave('samenvatting');
        }

        $orientaties = match ($perspectief) {
            'links' => ['left', 'left-center'],
            'midden' => ['neutral'],
            'rechts' => ['right-center', 'right'],
            default => [],
        };

        if ($orientaties !== []) {
            $query->whereHas('artikelen', function (Builder $artikelQuery) use ($orientaties): void {
                $artikelQuery->whereHas('bron', function (Builder $bronQuery) use ($orientaties): void {
                    $bronQuery->whereIn('orientatie', $orientaties);
                });
            });
        }

        $gebeurtenissen = $query->paginate(15)->withQueryString();

        return view('gebeurtenissen.index', [
            'gebeurtenissen' => $gebeurtenissen,
            'trending' => $gebeurtenissen->onFirstPage() ? $trending : collect(),
            'aantalVerhalen' => $gebeurtenissen->total() + $trending->count(),
            'zoekterm' => $zoekterm,
            'filter' => $filter,
            'perspectief' => $perspectief,
            'categorie' => $categorie,
            'lijstUrl' => $categorie !== null
                ? route('gebeurtenissen.categorie', $categorie)
                : route('gebeurtenissen.index'),
        ]);
    }

    public function show(Gebeurtenis $gebeurtenis)
    {
        $gebeurtenis->load('samenvatting', 'artikelen.bron');

        $artikelen = $gebeurtenis->artikelen ?? collect();
        $artikelen->each(function ($artikel): void {
            $artikel->setAttribute('bron_logo_url', $this->bronLogoUrl($artikel->url));
        });

        $artikelenPerOrientatie = $artikelen->groupBy(fn ($artikel): string => $artikel->bron?->bakje() ?? 'Onbekend');

        $onbekendeArtikelen = $artikelenPerOrientatie->get('Onbekend', collect());

        foreach (['Links', 'Midden', 'Rechts', 'Onbekend'] as $naam) {
            $artikelenPerOrientatie->put(
                $naam,
                $artikelenPerOrientatie->get($naam, collect())
            );
        }

        $volgorde = [
            'Links' => 1,
            'Midden' => 2,
            'Rechts' => 3,
            'Onbekend' => 4,
        ];
        $artikelenPerOrientatie = $artikelenPerOrientatie->sortBy(function ($artikelen, $bakje) use ($volgorde) {
            return $volgorde[$bakje] ?? 5;
        });

        // Alle embeddings ophalen kost ~0,5 s, dus het resultaat blijft een uur bewaard
        $gerelateerdeIds = Cache::remember(
            "gebeurtenissen.{$gebeurtenis->id}.gerelateerd",
            now()->addHour(),
            fn (): array => (new GebeurtenisMatcher)->gerelateerdeGebeurtenissen($gebeurtenis),
        );
        $gerelateerd = Gebeurtenis::voorKaart()
            ->whereKey($gerelateerdeIds)
            ->get()
            ->sortBy(fn (Gebeurtenis $gerelateerde): int => array_search($gerelateerde->id, $gerelateerdeIds, true))
            ->values();

        return view('gebeurtenissen.show', [
            'gebeurtenis' => $gebeurtenis,
            'artikelenPerOrientatie' => $artikelenPerOrientatie,
            'onbekendeArtikelen' => $onbekendeArtikelen,
            'gerelateerd' => $gerelateerd,
            'aantalPreview' => $this->aantalPreview,
        ]);
    }

    private function bronLogoUrl(string $artikelUrl): ?string
    {
        if (filter_var($artikelUrl, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = parse_url($artikelUrl, PHP_URL_SCHEME);
        $host = parse_url($artikelUrl, PHP_URL_HOST);
        $port = parse_url($artikelUrl, PHP_URL_PORT);

        if (! is_string($scheme) || ! is_string($host) || ! in_array(strtolower($scheme), ['http', 'https'], true)) {
            return null;
        }

        return strtolower($scheme).'://'.$host.($port !== null ? ':'.$port : '').'/favicon.ico';
    }
}
