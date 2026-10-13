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
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'categorie' => Categorie::class,
            'embedding' => 'array',
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
     * 'links' of 'rechts' als de gebeurtenis bijna alleen door die kant wordt gebracht, anders null.
     * Telt alleen links- en rechts-artikelen (Midden niet): minstens 3 samen, en minstens 80% van één kant.
     * Vereist voorKaart() voor links_count en rechts_count.
     */
    public function blindspotKant(): ?string
    {
        $linksEnRechts = $this->links_count + $this->rechts_count;

        if ($linksEnRechts < 3) {
            return null;
        }

        return match (true) {
            $this->links_count / $linksEnRechts >= 0.8 => 'links',
            $this->rechts_count / $linksEnRechts >= 0.8 => 'rechts',
            default => null,
        };
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
        // zonder de embedding: die is groot en een kaart heeft hem niet nodig
        $query->select(['gebeurtenissen.id', 'gebeurtenissen.onderwerp', 'gebeurtenissen.categorie'])
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
            }]);
    }

    /**
     * Minstens 2 artikelen in de laatste 48 uur, meeste recente artikelen eerst. Vereist voorKaart() voor de tiebreaker.
     */
    public function scopeHot(Builder $query): void
    {
        $sinds = now()->subHours(48);

        $query->whereHas('artikelen', function (Builder $artikelQuery) use ($sinds): void {
            $artikelQuery->where('publicatiedatum', '>=', $sinds);
        }, '>=', 2)
            ->withCount(['artikelen as recente_artikelen_count' => function (Builder $artikelQuery) use ($sinds): void {
                $artikelQuery->where('publicatiedatum', '>=', $sinds);
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
