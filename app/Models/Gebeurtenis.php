<?php

namespace App\Models;

use App\Categorie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Gebeurtenis extends Model
{
    protected $table = 'gebeurtenissen';

    protected $fillable = [
        'onderwerp',
        'categorie',
    ];

    protected function casts(): array
    {
        return [
            'categorie' => Categorie::class,
        ];
    }

    public function artikelen()
    {
        return $this->hasMany(Artikel::class);
    }

    public function samenvatting()
    {
        return $this->hasOne(Samenvatting::class);
    }

    /**
     * Alleen gebeurtenissen met minstens 2 artikelen, dezelfde drempel als samenvattingen:genereren.
     */
    public function scopeZichtbaar(Builder $query): void
    {
        $query->has('artikelen', '>=', 2);
    }

    /**
     * Alles wat een gebeurteniskaart toont: tellingen per perspectief, nieuwste publicatiedatum en één afbeelding.
     */
    public function scopeVoorKaart(Builder $query): void
    {
        $query->withCount([
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
            }]);
    }

    /**
     * Minstens 2 artikelen in de laatste 48 uur, meeste recente artikelen eerst. Vereist voorKaart() voor de tiebreaker.
     * publicatiedatum is een datum zonder tijd, dus de laatste 48 uur is vandaag plus gisteren.
     */
    public function scopeHot(Builder $query): void
    {
        $sinds = now()->subDay()->toDateString();

        $query->whereHas('artikelen', function (Builder $artikelQuery) use ($sinds): void {
            $artikelQuery->whereDate('publicatiedatum', '>=', $sinds);
        }, '>=', 2)
            ->withCount(['artikelen as recente_artikelen_count' => function (Builder $artikelQuery) use ($sinds): void {
                $artikelQuery->whereDate('publicatiedatum', '>=', $sinds);
            }])
            ->orderByDesc('recente_artikelen_count')
            ->nieuwsteEerst();
    }

    /**
     * Nieuwste artikelen.publicatiedatum eerst, id als tiebreaker. Vereist voorKaart() voor de withMax-kolom.
     */
    public function scopeNieuwsteEerst(Builder $query): void
    {
        $query->orderByDesc('artikelen_max_publicatiedatum')
            ->orderByDesc('id');
    }
}
