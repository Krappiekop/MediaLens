<?php

namespace App\Models;

use App\Services\OrientatieSchatter;
use Illuminate\Database\Eloquent\Model;

class Bron extends Model
{
    protected $table = 'bronnen';

    protected $fillable = [
        'naam',
        'orientatie',
        'betrouwbaarheid',
    ];

    public function artikelen()
    {
        return $this->hasMany(Artikel::class);
    }

    /**
     * Weergavegroep van het MBFC-label: Links, Midden, Rechts of Onbekend.
     */
    public function bakje(): string
    {
        return self::bakjeVan($this->orientatie);
    }

    /**
     * Weergavegroep van een oriëntatielabel, voor het MBFC-label én de AI-schatting.
     */
    public static function bakjeVan(?string $orientatie): string
    {
        return match ($orientatie) {
            'left', 'left-center' => 'Links',
            'neutral' => 'Midden',
            'right-center', 'right' => 'Rechts',
            default => 'Onbekend',
        };
    }

    /**
     * Gewogen gemiddelde van de AI-schattingen van de politiek geladen artikelen van deze bron.
     * Elk label krijgt een score van -2 (left) tot 2 (right) en weegt mee naar zijn zekerheid.
     * Artikelen zonder politieke lading tellen niet mee, anders trekken sport- en bedrijfsnieuws elke bron naar neutral.
     *
     * @return array{orientatie: string, score: float, aantal: int, gemiddelde_zekerheid: int}|null
     */
    public function aiSchatting(): ?array
    {
        // Per label alleen tellen en optellen; het wegen met negatieve scores gebeurt in PHP,
        // omdat MySQL een unsigned kolom keer een negatief getal niet aankan
        $perLabel = $this->artikelen()
            ->toBase()
            ->whereNotNull('ai_orientatie')
            ->where(fn ($query) => $query->where('ai_politiek', true)->orWhereNull('ai_politiek'))
            ->groupBy('ai_orientatie')
            ->selectRaw('ai_orientatie, COUNT(*) AS aantal, SUM(ai_zekerheid) AS gewicht')
            ->get();

        $aantal = (int) $perLabel->sum('aantal');

        if ($aantal === 0) {
            return null;
        }

        $gewicht = (int) $perLabel->sum('gewicht');
        $gewogen = $perLabel->sum(fn (object $rij): int => (int) $rij->gewicht * (array_search($rij->ai_orientatie, OrientatieSchatter::LABELS, true) - 2));
        $score = $gewicht > 0 ? $gewogen / $gewicht : 0.0;

        return [
            'orientatie' => OrientatieSchatter::LABELS[(int) round($score) + 2],
            'score' => round($score, 1),
            'aantal' => $aantal,
            'gemiddelde_zekerheid' => (int) round($gewicht / $aantal),
        ];
    }

    /**
     * Nederlandse weergave van MBFC factual_reporting (high/mixed/low).
     */
    public function betrouwbaarheidLabel(): string
    {
        return match ($this->betrouwbaarheid) {
            'high' => 'Hoog',
            'mixed' => 'Gemengd',
            'low' => 'Laag',
            default => 'Onbekend',
        };
    }
}
