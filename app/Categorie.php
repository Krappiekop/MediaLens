<?php

namespace App;

enum Categorie: string
{
    case Wereld = 'wereld';
    case Politiek = 'politiek';
    case Economie = 'economie';
    case TechWetenschap = 'tech-wetenschap';
    case Sport = 'sport';
    case Overig = 'overig';

    public function label(): string
    {
        return match ($this) {
            self::Wereld => 'Wereld',
            self::Politiek => 'Politiek',
            self::Economie => 'Economie',
            self::TechWetenschap => 'Tech & Wetenschap',
            self::Sport => 'Sport',
            self::Overig => 'Overig',
        };
    }

    /**
     * Uitleg per categorie voor de AI-prompt.
     */
    public function beschrijving(): string
    {
        return match ($this) {
            self::Wereld => 'internationale betrekkingen, oorlogen en conflicten, diplomatie, en nieuws over andere landen dan de Verenigde Staten',
            self::Politiek => 'binnenlandse politiek: regering, parlement of congres, verkiezingen, wetgeving, partijen, rechtszaken over beleid',
            self::Economie => 'bedrijven, markten, handel, banen, prijzen, belastingen en overheidsfinanciën',
            self::TechWetenschap => 'technologie, AI, internet, wetenschap, ruimtevaart, klimaatonderzoek en medisch onderzoek',
            self::Sport => 'sportwedstrijden, sporters, clubs en sportbonden',
            self::Overig => 'misdaad, rampen, cultuur, entertainment, weer en alles wat niet in een andere categorie past',
        };
    }

    /**
     * Categorieën die als onderwerp in de menubalk staan. Overig staat alleen onder Alles.
     *
     * @return array<int, self>
     */
    public static function menu(): array
    {
        return [self::Wereld, self::Politiek, self::Economie, self::TechWetenschap, self::Sport];
    }

    /**
     * Lijst van alle categorieën met uitleg, voor in een systeem-prompt.
     */
    public static function promptUitleg(): string
    {
        return collect(self::cases())
            ->map(fn (self $categorie): string => "- {$categorie->value}: {$categorie->beschrijving()}")
            ->implode("\n");
    }

    /**
     * @return array<int, string>
     */
    public static function waarden(): array
    {
        return array_column(self::cases(), 'value');
    }
}
