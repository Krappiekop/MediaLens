<?php

namespace App\Http\Controllers;

use App\Models\Gebeurtenis;
use Illuminate\Http\Request;

class GebeurtenisController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter');

        $query = Gebeurtenis::has('artikelen', '>=', 2)
            ->withCount([
                'artikelen',
                'artikelen as links_count' => function ($query) {
                    $query->whereHas('bron', function ($bron) {
                        $bron->whereIn('orientatie', ['left', 'left-center']);
                    });
                },
                'artikelen as midden_count' => function ($query) {
                    $query->whereHas('bron', function ($bron) {
                        $bron->where('orientatie', 'neutral');
                    });
                },
                'artikelen as rechts_count' => function ($query) {
                    $query->whereHas('bron', function ($bron) {
                        $bron->whereIn('orientatie', ['right-center', 'right']);
                    });
                },
            ])
            ->withMax('artikelen', 'publicatiedatum')
            ->with(['artikelen' => function ($query) {
                $query->select(['id', 'gebeurtenis_id', 'titel', 'afbeelding_url', 'afbeelding_credit'])
                    ->whereNotNull('afbeelding_url')
                    ->orderByDesc('publicatiedatum')
                    ->orderByDesc('id')
                    ->limit(1);
            }])
            ->orderByDesc('artikelen_max_publicatiedatum')
            ->orderByDesc('id');

        if ($filter === 'met') {
            $query->has('samenvatting');
        } elseif ($filter === 'zonder') {
            $query->doesntHave('samenvatting');
        }

        $gebeurtenissen = $query->paginate(15)->withQueryString();

        return view('gebeurtenissen.index', [
            'gebeurtenissen' => $gebeurtenissen,
            'filter' => $filter,
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

        $artikelen = $gebeurtenis->artikelen ?? collect();
        $artikelen->each(function ($artikel): void {
            $artikel->setAttribute('bron_logo_url', $this->bronLogoUrl($artikel->url));
        });

        $artikelenPerOrientatie = $artikelen->groupBy(function ($artikel) use ($bakjes) {
            $orientatie = $artikel?->bron?->orientatie;

            if ($orientatie === null || $orientatie === '') {
                return 'Onbekend';
            }

            return $bakjes[$orientatie] ?? 'Onbekend';
        });

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

        return view('gebeurtenissen.show', [
            'gebeurtenis' => $gebeurtenis,
            'artikelenPerOrientatie' => $artikelenPerOrientatie,
            'onbekendeArtikelen' => $onbekendeArtikelen,
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
