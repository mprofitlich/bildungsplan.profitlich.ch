<script module>
  // Gilt im Kurs und im Curriculum zugleich und bleibt beim Wechsel der Ansicht stehen.
  const darstellung = $state({ art: 'matrix' });
</script>

<script>
  import Diagramm from './Diagramm.svelte';
  import Uebersicht from './Uebersicht.svelte';

  /**
   * Der Abschnitt «Im Bildungsplan»: wie viele Leistungsziele je Handlungskompetenz,
   * als Matrix der Übersicht oder als Diagramm. `anzahl` und `bezug` wie bei der Übersicht.
   */
  let { anzahl, bezug = 'im Kurs' } = $props();
</script>

<h2>Im Bildungsplan</h2>
<fieldset class="chips gruppe" aria-label="Darstellung">
  <label><input type="radio" bind:group={darstellung.art} value="matrix" /><span>Matrix</span></label>
  <label><input type="radio" bind:group={darstellung.art} value="diagramm" /><span>Diagramm</span></label>
</fieldset>

{#if darstellung.art === 'matrix'}
  <Uebersicht {anzahl} {bezug} />
{:else}
  <Diagramm {anzahl} {bezug} />
{/if}

<style>
  h2 {
    margin: var(--abstand-gross) 0 var(--abstand);
    font-size: 1.375rem;
  }

  fieldset {
    margin-bottom: var(--abstand);
  }
</style>
