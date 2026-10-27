<script>
  import { gruppiere, ziel } from './bildungsplan.js';
  import KProfil from './KProfil.svelte';
  import Uebersicht from './Uebersicht.svelte';
  import Zielgruppen from './Zielgruppen.svelte';

  /** Was ein Kurs abdeckt: Taxonomiestufen, Handlungskompetenzen und Ziele. */
  let { kurs } = $props();

  const gewaehlt = $derived(kurs.ziele.map(ziel).filter(Boolean));
  const gruppen = $derived(gruppiere(gewaehlt));
  const anzahl = $derived(
    Object.fromEntries(gruppen.map((gruppe) => [gruppe.kompetenz.id, gruppe.ziele.length])),
  );
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
{/if}

<style>
  h2 {
    margin: var(--abstand-gross) 0 var(--abstand);
    font-size: 1.375rem;
  }

  p {
    margin: var(--abstand) 0;
  }

  .leise {
    color: var(--text-leise);
  }
</style>
