// Detailpagina: per perspectief eerst alleen de preview, met één knop voor alle artikelen.
// Zonder JavaScript blijven alle artikelen zichtbaar en blijft de knop verborgen.
document.querySelectorAll('[data-toon-alles]').forEach((knop) => {
    const blokken = document.querySelectorAll('[data-extra-artikelen]');

    const toon = (alles) => {
        blokken.forEach((blok) => {
            blok.hidden = !alles;
        });
        knop.setAttribute('aria-expanded', String(alles));
        knop.textContent = alles ? knop.dataset.tekstMinder : knop.dataset.tekstMeer;
    };

    toon(false);
    knop.hidden = false;
    knop.addEventListener('click', () => toon(knop.getAttribute('aria-expanded') !== 'true'));
});

// Dark mode: de knop in de kopbalk wisselt licht/donker en onthoudt de keus.
// Zonder eigen keus volgt de site het besturingssysteem (zie het script in de <head> van de layout).
const eigenThemaKeus = () => {
    try {
        return localStorage.getItem('thema');
    } catch {
        return null;
    }
};

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
    if (eigenThemaKeus() === null) {
        document.documentElement.classList.toggle('dark', event.matches);
        document.querySelectorAll('[data-thema-knop]').forEach((knop) => knop.dispatchEvent(new Event('thema-gewijzigd')));
    }
});

document.querySelectorAll('[data-thema-knop]').forEach((knop) => {
    const zetLabel = () => {
        const donker = document.documentElement.classList.contains('dark');
        knop.setAttribute('aria-pressed', String(donker));
        knop.setAttribute('aria-label', donker ? 'Lichte modus aanzetten' : 'Donkere modus aanzetten');
    };

    zetLabel();
    knop.hidden = false;
    knop.addEventListener('thema-gewijzigd', zetLabel);
    knop.addEventListener('click', () => {
        const donker = document.documentElement.classList.toggle('dark');
        try {
            localStorage.setItem('thema', donker ? 'donker' : 'licht');
        } catch {
            // opslag geblokkeerd: de keus geldt dan alleen voor deze pagina
        }
        zetLabel();
    });
});
