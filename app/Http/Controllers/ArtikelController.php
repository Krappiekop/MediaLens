<?php

namespace App\Http\Controllers;

use App\Models\Artikel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArtikelController extends Controller
{
    public function index(Request $request): View
    {
        $zoekterm = $this->zoekterm($request);
        $status = $request->query('status');
        $status = in_array($status, ['gekoppeld', 'los'], true) ? $status : 'alle';
        $perspectief = $request->query('perspectief');
        $perspectief = in_array($perspectief, ['links', 'midden', 'rechts'], true) ? $perspectief : 'alle';

        $query = Artikel::query()
            ->with([
                'bron:id,naam,orientatie',
                'gebeurtenis:id,onderwerp',
            ]);

        if ($zoekterm !== '') {
            $query->where(function (Builder $query) use ($zoekterm): void {
                $this->bevat($query, 'titel', $zoekterm);
                $this->bevat($query, 'volledige_tekst', $zoekterm, 'or')
                    ->orWhereHas('bron', function (Builder $bronQuery) use ($zoekterm): void {
                        $this->bevat($bronQuery, 'naam', $zoekterm);
                    })
                    ->orWhereHas('gebeurtenis', function (Builder $gebeurtenisQuery) use ($zoekterm): void {
                        $this->bevat($gebeurtenisQuery, 'onderwerp', $zoekterm);
                    });
            });
        }

        if ($status === 'los') {
            $query->whereNull('gebeurtenis_id');
        } elseif ($status === 'gekoppeld') {
            $query->whereNotNull('gebeurtenis_id');
        }

        $orientaties = match ($perspectief) {
            'links' => ['left', 'left-center'],
            'midden' => ['neutral'],
            'rechts' => ['right-center', 'right'],
            default => [],
        };

        if ($orientaties !== []) {
            $query->whereHas('bron', function (Builder $bronQuery) use ($orientaties): void {
                $bronQuery->whereIn('orientatie', $orientaties);
            });
        }

        $artikelen = $query
            ->orderByDesc('publicatiedatum')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('artikelen.index', [
            'artikelen' => $artikelen,
            'zoekterm' => $zoekterm,
            'status' => $status,
            'perspectief' => $perspectief,
        ]);
    }
}
