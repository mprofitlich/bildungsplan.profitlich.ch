<script>
  import { fassung } from './fassung.svelte.js';
  import { fassungen, gruppiere, ziel } from './bildungsplan.js';
  import KProfil from './KProfil.svelte';
  import { nachweis } from './nachweis.js';
  import Uebersicht from './Uebersicht.svelte';
  import Zielgruppen from './Zielgruppen.svelte';

  /** Was ein Kurs abdeckt: Taxonomiestufen, Handlungskompetenzen, Ziele und Nachweis. */
  let { kurs } = $props();

  const gewaehlt = $derived(kurs.ziele.map(ziel).filter(Boolean));
  const gruppen = $derived(gruppiere(gewaehlt));
  const anzahl = $derived(
    Object.fromEntries(gruppen.map((gruppe) => [gruppe.kompetenz.id, gruppe.ziele.length])),
  );
  const text = $derived(nachweis(kurs, fassung.id));

  let meldung = $state('');

  async function kopieren() {
    await navigator.clipboard.writeText(text);
    meldung = 'Nachweis kopiert.';
  }
</script>

{#if gewaehlt.length === 0}
  <h2>Leistungsziele (0)</h2>
  <p class="leise">Noch keine Leistungsziele gewählt. Unter «Kompetenzen» lassen sie sich hinzufügen.</p>
{:else}
  <h2>Taxonomiestufen</h2>
  <KProfil ziele={gewaehlt} />

  <h2>Im Bildungsplan</h2>
  <Uebersicht {anzahl} />

  <h2>Leistungsziele ({gewaehlt.length})</h2>
  <Zielgruppen {gruppen} />

  <h2>Nachweis</h2>
  <p class="leise">
    Version {fassungen.find((eintrag) => eintrag.id === fassung.id).name}, umschaltbar unter «Kompetenzen».
  </p>
  <textarea class="nachweis" readonly rows="14" value={text}></textarea>
  <p><button onclick={kopieren}>Nachweis kopieren</button></p>
{/if}

<p class="leise" aria-live="polite">{meldung}</p>

<style>
  h2 {
    margin: var(--abstand-gross) 0 var(--abstand);
    font-size: 1.375rem;
  }

  .nachweis {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: var(--schrift-klein);
  }

  p {
    margin: var(--abstand) 0;
  }

  .leise {
    color: var(--text-leise);
  }
</style>
