<script>
  import { summeJeKompetenz } from './curriculum.js';
  import KursInhalt from './KursInhalt.svelte';
  import { anfrage, fehlermeldung } from './server.js';
  import Uebersicht from './Uebersicht.svelte';

  /**
   * @typedef {object} CurriculumKurs
   * @property {string} id
   * @property {string} titel
   * @property {string} beschrieb
   * @property {string[]} ziele
   * @property {string} besitzer
   */

  /** @type {{ domain: string, kurse: CurriculumKurs[] } | null} */
  let curriculum = $state(null);
  let fehler = $state('');
  /** @type {CurriculumKurs | null} */
  let offen = $state(null);

  const summe = $derived(curriculum ? summeJeKompetenz(curriculum.kurse) : {});

  // Bei jedem Öffnen neu, damit eben freigegebene Kurse dabei sind.
  $effect(() => {
    anfrage('GET', '/curriculum').then((antwort) => {
      if (antwort.status === 200) curriculum = antwort.daten;
      else fehler = fehlermeldung(antwort);
    });
  });

  function oeffnen(kurs) {
    offen = kurs;
    window.scrollTo(0, 0);
  }
</script>

{#if fehler}
  <p class="fehler" role="alert">Das Curriculum konnte nicht geladen werden: {fehler}</p>
{:else if !curriculum}
  <p class="leise">Curriculum wird geladen …</p>
{:else if offen}
  <p><button class="schlicht" onclick={() => (offen = null)}>Zurück zum Curriculum</button></p>
  <h2 class="titel">{offen.titel || 'Kurs ohne Titel'}</h2>
  <p class="leise">Kurs von {offen.besitzer}, nur zum Ansehen.</p>
  {#if offen.beschrieb}
    <p class="beschrieb">{offen.beschrieb}</p>
  {/if}
  <KursInhalt kurs={offen} />
{:else}
  <h2 class="titel">Curriculum {curriculum.domain}</h2>
  <p class="leise">
    Alle Kurse von {curriculum.domain} mit «Im Curriculum zeigen». Die Zahlen summieren die Leistungsziele
    aller Kurse; ein Ziel in drei Kursen zählt dreimal.
  </p>

  <h2>Im Bildungsplan</h2>
  <Uebersicht anzahl={summe} bezug="in allen Kursen des Curriculums" />

  <h2>Kurse ({curriculum.kurse.length})</h2>
  {#if curriculum.kurse.length === 0}
    <p class="leise">Noch kein Kurs ist im Curriculum. Unter «Kurs» lässt sich ein eigener dafür freigeben.</p>
  {:else}
    <ul class="liste">
      {#each curriculum.kurse as kurs (kurs.id)}
        <li>
          <button class="kurs" onclick={() => oeffnen(kurs)}>{kurs.titel || 'Kurs ohne Titel'}</button>
          <span class="leise">{kurs.besitzer}</span>
          <span class="anzahl">
            {kurs.ziele.length} {kurs.ziele.length === 1 ? 'Leistungsziel' : 'Leistungsziele'}
          </span>
        </li>
      {/each}
    </ul>
  {/if}
{/if}

<style>
  h2 {
    margin: var(--abstand-gross) 0 var(--abstand);
    font-size: 1.375rem;
  }

  /* Die Ansicht beginnt am Innenabstand der Seite wie alle anderen. */
  .titel {
    margin-top: 0;
  }

  p {
    max-width: 62.75rem;
    margin: var(--abstand) 0;
  }

  .beschrieb {
    white-space: pre-line;
  }

  .liste {
    margin: 0;
    padding: 0;
    border-bottom: 1px solid var(--linie);
    list-style: none;
  }

  li {
    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 16rem) 9rem;
    gap: var(--abstand);
    align-items: baseline;
    padding: var(--abstand);
    border-top: 1px solid var(--linie);
    transition: background-color 0.15s;
  }

  li:hover {
    background: var(--hover);
  }

  /* Der Titel bleibt der einzige Knopf; seine Klickfläche deckt die ganze Zeile. */
  .kurs {
    padding: 0;
    background: none;
    border: 0;
    color: var(--text);
    font-size: 1.125rem;
    font-weight: 600;
    text-align: left;
  }

  .kurs::after {
    content: '';
    position: absolute;
    inset: 0;
  }

  .anzahl {
    font-size: var(--schrift-klein);
    font-variant-numeric: tabular-nums;
    font-weight: 600;
  }

  @media (max-width: 48rem) {
    li {
      grid-template-columns: 1fr auto;
    }

    .kurs {
      grid-column: 1 / -1;
    }
  }

  .leise {
    color: var(--text-leise);
  }

  .fehler {
    color: var(--gefahr);
  }
</style>
