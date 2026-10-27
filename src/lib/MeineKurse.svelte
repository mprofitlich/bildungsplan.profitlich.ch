<script>
  import { sicherungHinzufuegen } from './abgleich.svelte.js';
  import Einloggen from './Einloggen.svelte';
  import { fassung } from './fassung.svelte.js';
  import { lernorte, taxonomiestufen, wortlaut, ziel } from './bildungsplan.js';
  import { konto } from './konto.svelte.js';
  import { kursAnlegen, sicherung, sicherungEinlesen, sicherungsname, zustand } from './kurse.svelte.js';

  /** `onoeffne` zeigt den aktiven Kurs, nachdem er gewählt oder angelegt wurde. */
  let { onoeffne } = $props();

  let meldung = $state('');

  // Ohne Login fehlt `istBesitzer`; dann sind alle Kurse eigene.
  const eigene = $derived(zustand.kurse.filter((kurs) => kurs.istBesitzer !== false));
  const geteilte = $derived(zustand.kurse.filter((kurs) => kurs.istBesitzer === false));

  /** Zählt die Ziele eines Kurses nach Lernort und Taxonomiestufe. */
  function verteilung(kurs) {
    const gewaehlt = kurs.ziele
      .map(ziel)
      .filter(Boolean)
      .map((z) => wortlaut(z, fassung.id));
    return {
      lernorte: lernorte.map((ort) => ({
        ...ort,
        anzahl: gewaehlt.filter((z) => z.lernort === ort.id).length,
      })),
      stufen: taxonomiestufen
        .map((eintrag) => ({
          ...eintrag,
          anzahl: gewaehlt.filter((z) => z.stufe === eintrag.stufe).length,
        }))
        .filter((eintrag) => eintrag.anzahl > 0),
    };
  }

  function oeffnen(id) {
    zustand.aktiv = id;
    onoeffne();
  }

  function anlegen() {
    kursAnlegen();
    onoeffne();
  }

  function sichern() {
    const verweis = document.createElement('a');
    verweis.href = URL.createObjectURL(new Blob([sicherung()], { type: 'application/json' }));
    verweis.download = sicherungsname();
    verweis.click();
    URL.revokeObjectURL(verweis.href);
  }

  async function einlesen(event) {
    const datei = event.currentTarget.files[0];
    event.currentTarget.value = '';
    if (!datei) return;
    // Mit Login ersetzt die Sicherung nichts: Sonst verschwänden auch Kurse, die andere mitbearbeiten.
    if (konto.ich) {
      meldung = await sicherungHinzufuegen(await datei.text());
      return;
    }
    if (!confirm('Alle Kurse durch den Inhalt der Sicherung ersetzen?')) return;
    meldung = sicherungEinlesen(await datei.text())
      ? 'Sicherung eingelesen.'
      : 'Die Datei ist keine gültige Sicherung.';
  }
</script>

{#snippet liste(kurse)}
<ul class="liste">
  {#each kurse as kurs (kurs.id)}
    {@const zahlen = verteilung(kurs)}
    <li class:aktiv={kurs.id === zustand.aktiv}>
      <div class="name">
        <button class="titel" onclick={() => oeffnen(kurs.id)}>{kurs.titel || 'Kurs ohne Titel'}</button>
        {#if kurs.istBesitzer === false}
          <p class="herkunft">von {kurs.besitzer}</p>
        {:else if kurs.freigaben?.length > 0}
          <p class="herkunft">
            freigegeben an {kurs.freigaben.length} {kurs.freigaben.length === 1 ? 'Person' : 'Personen'}
          </p>
        {/if}
      </div>
      <p class="anzahl">
        {kurs.ziele.length} {kurs.ziele.length === 1 ? 'Leistungsziel' : 'Leistungsziele'}
      </p>
      {#each zahlen.lernorte as ort (ort.id)}
        <p class="lernort">{ort.name} {ort.anzahl}</p>
      {/each}
      <p class="stufen" aria-label="Taxonomiestufen">
        {#each zahlen.stufen as eintrag (eintrag.stufe)}
          <span class="stufe" data-stufe={eintrag.stufe} title={eintrag.begriff}
            >{eintrag.kuerzel} <span class="zahl">{eintrag.anzahl}</span></span
          >
        {/each}
      </p>
    </li>
  {/each}
</ul>
{/snippet}

<div class="kopf">
  <h2>Meine Kurse ({eigene.length})</h2>
  <button class="neu" onclick={anlegen}>
    <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true">
      <path d="M8 2v12M2 8h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
    </svg>
    Neuer Kurs
  </button>
</div>

{@render liste(eigene)}

{#if geteilte.length > 0}
  <h2>Mit mir geteilt ({geteilte.length})</h2>
  {@render liste(geteilte)}
{/if}

{#if !konto.ich}
  <Einloggen />
{/if}

<h2>Sicherung</h2>
{#if konto.ich}
  <p class="leise">
    Die Kurse liegen auf dem Server. «Sicherung einlesen» fügt die Kurse einer Datei hinzu und ersetzt keine.
  </p>
{:else}
  <p class="leise">Die Kurse liegen nur in diesem Browser. Eine Sicherungsdatei schützt vor Verlust.</p>
{/if}
<div class="sicherung">
  <button onclick={sichern}>Sicherung herunterladen</button>
  <label class="datei">
    Sicherung einlesen
    <input type="file" accept="application/json" onchange={einlesen} />
  </label>
</div>

<p class="leise" aria-live="polite">{meldung}</p>

<style>
  .kopf {
    display: flex;
    gap: var(--abstand);
    /*
     * Oben statt an der Schriftlinie: Die Schriftlinie des Knopfs liegt wegen
     * Innenabstand und Rahmen tiefer und schöbe den Titel unter die Oberkante
     * der anderen Ansichten.
     */
    align-items: start;
    justify-content: space-between;
    margin-bottom: var(--abstand);
  }

  /* Die Ansicht beginnt am Innenabstand der Seite wie alle anderen. */
  .kopf h2 {
    margin: 0;
  }

  h2 {
    margin: var(--abstand-gross) 0 var(--abstand);
    font-size: 1.375rem;
  }

  .liste {
    margin: 0;
    padding: 0;
    border-bottom: 1px solid var(--linie);
    list-style: none;
  }

  /* Spalten: Kurs, Anzahl, Betrieb, Berufsfachschule, Taxonomiestufen. */
  li {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 9rem 6rem 9.5rem 17rem;
    gap: var(--abstand);
    align-items: baseline;
    padding: var(--abstand) var(--abstand) var(--abstand) calc(var(--abstand) - 3px);
    border-top: 1px solid var(--linie);
    border-left: 3px solid transparent;
  }

  li.aktiv {
    border-left-color: var(--akzent);
  }

  /* Der Titel bleibt der einzige Knopf; seine Klickfläche deckt die ganze Zeile. */
  li {
    position: relative;
    transition: background-color 0.15s;
  }

  li:hover {
    background: var(--hover);
  }

  .titel::after {
    content: '';
    position: absolute;
    inset: 0;
  }

  .name {
    min-width: 0;
  }

  .titel {
    padding: 0;
    background: none;
    border: 0;
    color: var(--text);
    font-size: 1.125rem;
    font-weight: 600;
    text-align: left;
  }

  p {
    margin: 0;
  }

  .anzahl,
  .lernort {
    font-size: var(--schrift-klein);
    font-variant-numeric: tabular-nums;
  }

  .anzahl {
    font-weight: 600;
  }

  .lernort,
  .herkunft {
    color: var(--text-leise);
  }

  .herkunft {
    font-size: var(--schrift-klein);
  }

  .neu {
    display: flex;
    gap: var(--abstand-klein);
    align-items: center;
  }

  .stufen {
    display: flex;
    flex-wrap: wrap;
    gap: var(--abstand-klein);
  }

  /* Ein feiner Strich trennt Kürzel und Anzahl. */
  .stufe {
    display: inline-flex;
    gap: 0.4em;
    align-items: stretch;
  }

  .stufe .zahl {
    padding-left: 0.4em;
    border-left: 1px solid color-mix(in srgb, currentColor 30%, transparent);
  }

  @media (max-width: 64rem) {
    li {
      grid-template-columns: auto auto 1fr;
      gap: var(--abstand-klein) var(--abstand);
    }

    .name,
    .stufen {
      grid-column: 1 / -1;
    }
  }

  .sicherung {
    display: flex;
    flex-wrap: wrap;
    gap: var(--abstand);
    align-items: end;
  }

  /* Das Dateifeld selbst lässt sich nicht gestalten; das Label sieht deshalb aus wie ein Knopf. */
  .datei {
    padding: var(--abstand-klein) var(--abstand);
    background: var(--grund);
    border: var(--rahmen-breite) solid var(--rahmen);
    cursor: pointer;
  }

  .datei:hover {
    background: var(--hover);
    border-color: var(--akzent);
  }

  .datei:focus-within {
    outline: 2px solid var(--akzent);
    outline-offset: 2px;
  }

  /* Unsichtbar, aber per Tastatur erreichbar. */
  .datei input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
  }

  .leise {
    margin: var(--abstand) 0;
    color: var(--text-leise);
  }
</style>
