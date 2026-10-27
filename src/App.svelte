<script>
  import { tick } from 'svelte';
  import Banane from './lib/ostereier/Banane.svelte';
  import Curriculum from './lib/Curriculum.svelte';
  import Erklaerung from './lib/Erklaerung.svelte';
  import Glossar from './lib/Glossar.svelte';
  import Hilfe from './lib/Hilfe.svelte';
  import Kompetenzen from './lib/Kompetenzen.svelte';
  import Kurs from './lib/Kurs.svelte';
  import MeineKurse from './lib/MeineKurse.svelte';
  import Neuigkeiten from './lib/Neuigkeiten.svelte';
  import Pfeil from './lib/Pfeil.svelte';
  import Styleguide from './lib/ostereier/Styleguide.svelte';
  import Uebersicht from './lib/Uebersicht.svelte';
  import { abgleich } from './lib/abgleich.svelte.js';
  import { ansichtAus, pfad } from './lib/adresse.js';
  import { quelle } from './lib/bildungsplan.js';
  import { hinweise, styleguide } from './lib/hinweise.svelte.js';
  import { abmelden, konto } from './lib/konto.svelte.js';
  import { aktiverKurs, zustand } from './lib/kurse.svelte.js';

  const stand = new Intl.DateTimeFormat('de-CH', { dateStyle: 'long' }).format(new Date(quelle.stand));

  /**
   * Ansicht zur Adresse im Browser; macht einen Kurs in der Adresse zum aktiven.
   * Was sich nicht zeigen lässt, ersetzt die Adresse, ohne einen Eintrag im Verlauf zu hinterlassen.
   */
  function ausAdresse() {
    let { ansicht: ziel, kursId } = ansichtAus(location.pathname);
    if (kursId && zustand.kurse.some((k) => k.id === kursId)) {
      zustand.aktiv = kursId;
    } else if (kursId) {
      abgleich.meldung = konto.ich
        ? 'Diesen Kurs gibt es nicht, oder er ist nicht für dich freigegeben.'
        : 'Dieser Kurs liegt nicht in diesem Browser. Eingeloggt sind freigegebene Kurse überall erreichbar.';
      ziel = 'meine-kurse';
    }
    if (ziel === 'curriculum' && !konto.ich) ziel = 'meine-kurse';
    // Das Ei bleibt nach dem Neuladen versteckt, auch für den, der die Adresse kennt.
    if (ziel === 'styleguide' && !styleguide.frei) ziel = 'kompetenzen';
    history.replaceState(null, '', pfad(ziel, zustand.aktiv) + location.search + location.hash);
    return ziel;
  }

  /** @type {'kompetenzen' | 'uebersicht' | 'glossar' | 'meine-kurse' | 'curriculum' | 'kurs' | 'hilfe' | 'styleguide'} */
  let ansicht = $state(ausAdresse());
  const kurs = $derived(aktiverKurs());

  $effect(() => {
    const ziel = pfad(ansicht, zustand.aktiv);
    if (ziel === location.pathname) return;
    // Wechselt nur der Kurs, etwa nach dem Löschen, ersetzt das den Eintrag im Verlauf statt einen anzuhängen.
    if (ansichtAus(location.pathname).ansicht === ansicht) history.replaceState(null, '', ziel);
    else history.pushState(null, '', ziel);
  });

  /** @type {HTMLElement} */
  let kopf = $state();
  /** @type {HTMLButtonElement} */
  let hilfeReiter = $state();
  /** @type {HTMLButtonElement} */
  let styleguideReiter = $state();

  // Klebende Elemente der Ansichten und Sprungziele halten diesen Abstand zum Menü.
  let menueHoehe = $state(0);
  $effect(() => {
    document.documentElement.style.setProperty('--menue', `${menueHoehe}px`);
  });

  // Wer die Hilfe oder den Styleguide einmal geöffnet hat, braucht den Pfeil dorthin nicht mehr.
  $effect(() => {
    if (ansicht === 'hilfe') hinweise.pfeil = false;
    if (ansicht === 'styleguide') styleguide.pfeil = false;
  });

  /** Öffnet die Ansicht «Kompetenzen» bei einer Handlungskompetenz. */
  async function zeigeKompetenz(kompetenzId) {
    // Die Ansicht startet ohne Filter, die Handlungskompetenz ist also immer sichtbar.
    ansicht = 'kompetenzen';
    await tick();
    document.getElementById(`hk-${kompetenzId}`)?.scrollIntoView();
  }

  // Suche, mit der «Kompetenzen» öffnet; über das Menü immer leer.
  let anfangssuche = $state('');

  function zeigeKompetenzen() {
    anfangssuche = '';
    ansicht = 'kompetenzen';
  }

  /** Öffnet «Kompetenzen» oben auf der Seite, mit einem Begriff in der Suche. */
  async function sucheBegriff(begriff) {
    anfangssuche = begriff;
    ansicht = 'kompetenzen';
    await tick();
    window.scrollTo(0, 0);
  }

  /** Öffnet das Glossar bei einem Abschnitt, etwa `taxonomiestufen`. */
  async function zeigeGlossar(abschnitt) {
    ansicht = 'glossar';
    await tick();
    document.getElementById(`glossar-${abschnitt}`)?.scrollIntoView();
  }

  // So lange ohne Eingabe, bis die Banane erscheint.
  const RUHEZEIT = 5 * 60 * 1000;
  let ruhend = $state(false);
  let uhr;

  /** Startet die Ruhezeit neu; liegt die Banane schon da, bleibt sie, bis jemand klickt. */
  function aufwecken() {
    if (ruhend) return;
    clearTimeout(uhr);
    uhr = setTimeout(() => (ruhend = true), RUHEZEIT);
  }

  function bananeWeg() {
    ruhend = false;
    aufwecken();
  }

  $effect(() => {
    aufwecken();
    return () => clearTimeout(uhr);
  });
</script>

<svelte:window
  onpopstate={() => (ansicht = ausAdresse())}
  onpointermove={aufwecken}
  onpointerdown={aufwecken}
  onkeydown={aufwecken}
  onwheel={aufwecken}
  onscroll={aufwecken}
/>

<header>
  <div class="innen">
    <div>
      <h1>Bildungsplan Grafikerin/Grafiker EFZ</h1>
      <p class="quelle">{quelle.titel}, vom {stand}</p>
    </div>
    <p class="konto">
      {#if konto.ich}
        {konto.ich.email}
        <button onclick={abmelden}>Abmelden</button>
      {:else}
        <button onclick={() => (ansicht = 'meine-kurse')}>Einloggen</button>
      {/if}
    </p>
  </div>
</header>

<!-- Ausserhalb des Kopfs, damit das Menü über die ganze Seite kleben kann und nicht nur innerhalb des Kopfs. -->
<div class="menue" bind:clientHeight={menueHoehe}>
  <div class="innen" bind:this={kopf}>
    <nav aria-label="Ansichten">
      <button
        aria-current={ansicht === 'kompetenzen' ? 'page' : undefined}
        onclick={zeigeKompetenzen}
      >
        Kompetenzen
      </button>
      <button
        aria-current={ansicht === 'uebersicht' ? 'page' : undefined}
        onclick={() => (ansicht = 'uebersicht')}
      >
        Übersicht
      </button>
      <button
        aria-current={ansicht === 'glossar' ? 'page' : undefined}
        onclick={() => (ansicht = 'glossar')}
      >
        Glossar
      </button>
      <button
        aria-current={ansicht === 'meine-kurse' ? 'page' : undefined}
        onclick={() => (ansicht = 'meine-kurse')}
      >
        Meine Kurse
      </button>
      {#if konto.ich}
        <button
          aria-current={ansicht === 'curriculum' ? 'page' : undefined}
          onclick={() => (ansicht = 'curriculum')}
        >
          Curriculum
        </button>
      {/if}
      <button
        bind:this={hilfeReiter}
        aria-current={ansicht === 'hilfe' ? 'page' : undefined}
        onclick={() => (ansicht = 'hilfe')}
      >
        Hilfe
      </button>
      {#if styleguide.frei}
        <button
          bind:this={styleguideReiter}
          aria-current={ansicht === 'styleguide' ? 'page' : undefined}
          onclick={() => (ansicht = 'styleguide')}
        >
          Styleguide
        </button>
      {/if}
      <button
        class="aktiver-kurs"
        aria-current={ansicht === 'kurs' ? 'page' : undefined}
        onclick={() => (ansicht = 'kurs')}
      >
        Kurs: {kurs.titel || 'ohne Titel'}
        <span class="zahl" title="{kurs.ziele.length} Leistungsziele">{kurs.ziele.length}</span>
      </button>
    </nav>
    <!-- Die Reiter stehen nebeneinander; zwei Pfeile zugleich lägen übereinander, deshalb nacheinander. -->
    {#if hinweise.pfeil && hilfeReiter}
      <Pfeil
        ziel={hilfeReiter}
        rahmen={kopf}
        text="Neu hier? Unter «Hilfe» steht kurz, was die Anwendung kann."
        onweg={() => (hinweise.pfeil = false)}
      />
    {:else if styleguide.pfeil && styleguideReiter}
      <Pfeil
        ziel={styleguideReiter}
        rahmen={kopf}
        text="Gefunden! Der neue Reiter «Styleguide» zeigt, nach welchen Regeln diese Seite gestaltet ist."
        onweg={() => (styleguide.pfeil = false)}
      />
    {/if}
  </div>
</div>

{#if abgleich.meldung}
  <div class="meldung" role="status">
    <p>{abgleich.meldung}</p>
    <button onclick={() => (abgleich.meldung = '')}>Schliessen</button>
  </div>
{/if}

<main>
  {#if ansicht === 'kompetenzen'}
    <Kompetenzen onglossar={zeigeGlossar} {anfangssuche} />
  {:else if ansicht === 'uebersicht'}
    <Uebersicht onwahl={zeigeKompetenz} />
  {:else if ansicht === 'glossar'}
    <Glossar onsuche={sucheBegriff} />
  {:else if ansicht === 'meine-kurse'}
    <MeineKurse onoeffne={() => (ansicht = 'kurs')} />
  {:else if ansicht === 'curriculum' && konto.ich}
    <Curriculum />
  {:else if ansicht === 'hilfe'}
    <Hilfe />
  {:else if ansicht === 'styleguide'}
    <Styleguide />
  {:else}
    <Kurs ongeloescht={() => (ansicht = 'meine-kurse')} />
  {/if}
</main>

<Erklaerung />
<Neuigkeiten onhilfe={() => (ansicht = 'hilfe')} />

{#if ruhend}
  <Banane onweg={bananeWeg} />
{/if}

<style>
  /* Kopf und Menü laufen über die ganze Breite, ihr Inhalt so breit wie die Seite. */
  header,
  .menue {
    background: var(--grundton);
    color: var(--grundton-text);
  }

  /* Beim Scrollen bleibt nur das Menü stehen, der Titel geht mit. */
  .menue {
    position: sticky;
    top: 0;
    z-index: 4;
  }

  .innen,
  main {
    max-width: 90rem;
    margin: 0 auto;
    padding: var(--abstand-gross) var(--abstand);
  }

  header .innen {
    display: flex;
    gap: var(--abstand);
    align-items: start;
    justify-content: space-between;
    padding-bottom: var(--abstand);
  }

  .konto {
    display: flex;
    flex: none;
    gap: var(--abstand);
    align-items: center;
    margin: 0;
    font-size: var(--schrift-klein);
  }

  .konto button {
    padding: 0.15em var(--abstand-klein);
    background: none;
    border-color: var(--grundton-text);
    color: var(--grundton-text);
    font-size: inherit;
  }

  .konto button:hover {
    background: color-mix(in srgb, var(--grundton-text) 18%, transparent);
  }

  @media (max-width: 40rem) {
    header .innen {
      flex-direction: column;
    }
  }

  /* Klebt unter dem Menü, damit eine abgelehnte Änderung nicht unbemerkt bleibt. */
  .meldung {
    position: sticky;
    top: var(--menue);
    z-index: 3;
    display: flex;
    gap: var(--abstand);
    align-items: center;
    justify-content: space-between;
    max-width: 90rem;
    margin: var(--abstand) auto 0;
    padding: var(--abstand-klein) var(--abstand);
    background: var(--treffer);
    color: var(--text);
  }

  .meldung p {
    margin: 0;
  }

  .menue .innen {
    position: relative;
    padding-top: 0;
    padding-bottom: 0;
  }

  h1 {
    margin: 0;
    font-size: 1.25rem;
  }

  .quelle {
    max-width: 62.75rem;
    margin: var(--abstand-klein) 0 0;
    color: var(--grundton-text);
    font-size: var(--schrift-klein);
    /* Gleich lange Zeilen, statt dass «(EFZ), vom …» allein in der zweiten steht. */
    text-wrap: balance;
  }

  .menue :focus-visible {
    outline-color: var(--grundton-text);
  }

  /*
   * Reiter über die ganze Breite; der aktive Kurs steht rechts abgesetzt.
   * Auf schmalen Bildschirmen scrollt die Zeile, statt umzubrechen. Die Linie
   * ist deshalb ein Schatten: Ein Rahmen mit negativem Rand würde vom
   * Scrollbereich abgeschnitten.
   */
  nav {
    display: flex;
    gap: var(--abstand-gross);
    overflow-x: auto;
    scrollbar-width: none;
  }

  nav button {
    display: flex;
    flex: none;
    gap: var(--abstand-klein);
    align-items: center;
    padding: var(--abstand) 0;
    white-space: nowrap;
    background: none;
    border: 0;
    border-bottom: 3px solid transparent;
    color: var(--grundton-text);
    font-weight: 500;
  }

  /* Weiss ist auf dem Violett schon an der Kontrastgrenze; der Hover zeigt sich deshalb am Unterstrich. */
  nav button:hover {
    border-bottom-color: color-mix(in srgb, var(--grundton-text) 50%, transparent);
  }

  nav button[aria-current='page'] {
    background: none;
    border-bottom-color: var(--grundton-text);
    color: var(--grundton-text);
    font-weight: 600;
  }

  .aktiver-kurs {
    margin-left: auto;
  }

  .zahl {
    padding: 0 0.45em;
    background: color-mix(in srgb, var(--grundton-text) 18%, transparent);
    color: var(--grundton-text);
    font-size: var(--schrift-klein);
    font-weight: 600;
    font-variant-numeric: tabular-nums;
  }

  [aria-current='page'] .zahl {
    background: var(--grundton-text);
    color: var(--grundton);
  }
</style>
