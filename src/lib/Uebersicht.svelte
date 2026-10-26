<script>
  import { fassung } from './fassung.svelte.js';
  import { arbeitsphasen, bereiche, wortlaut } from './bildungsplan.js';

  /**
   * Die Übersicht der Handlungskompetenzen aus Kapitel 3.2 des Bildungsplans.
   * Mit `anzahl` zeigt sie, wie viele Ziele ein Kurs je Handlungskompetenz
   * enthält; mit `onwahl` werden die Felder anklickbar. `bezug` nennt im
   * Hinweis zur Zahl, worauf sie sich bezieht.
   */
  let { anzahl = undefined, onwahl = undefined, bezug = 'im Kurs' } = $props();

  function phasen(kompetenz) {
    return arbeitsphasen.filter((phase) => kompetenz.arbeitsphasen.includes(phase.id));
  }
</script>

{#snippet inhalt(kompetenz, zahl)}
  <span class="kopf">
    <strong>{kompetenz.id[0]}<span class="ziffer">{kompetenz.id.slice(1)}</span></strong>
    {#each phasen(kompetenz) as phase (phase.id)}
      <span class="marke" style:background={phase.farbe} title={phase.name}></span>
    {/each}
    {#if zahl > 0}
      <span class="anzahl" title="{zahl} Leistungsziele {bezug}">{zahl}</span>
    {/if}
  </span>
  <span class="name">{wortlaut(kompetenz, fassung.id).titel}</span>
{/snippet}

<div class:fussabdruck={anzahl !== undefined}>
  {#each bereiche as bereich (bereich.id)}
    <section>
      <p class="bereich"><strong>{bereich.id}</strong> {bereich.titel}</p>
      <ul>
        {#each bereich.handlungskompetenzen as kompetenz (kompetenz.id)}
          {@const zahl = anzahl?.[kompetenz.id] ?? 0}
          <li class:belegt={zahl > 0}>
            {#if onwahl}
              <button type="button" class="kachel" onclick={() => onwahl(kompetenz.id)}>
                {@render inhalt(kompetenz, zahl)}
              </button>
            {:else}
              <div class="kachel">{@render inhalt(kompetenz, zahl)}</div>
            {/if}
          </li>
        {/each}
      </ul>
    </section>
  {/each}
</div>

<style>
  section {
    display: grid;
    grid-template-columns: 11rem 1fr;
    gap: var(--abstand);
    padding: var(--abstand-klein) 0;
    border-top: 1px solid var(--linie);
    font-size: var(--schrift-klein);
  }

  /* Linien nur zwischen den Bereichen, nicht über dem ersten. */
  section:first-child {
    border-top: 0;
  }

  .bereich {
    margin: 0;
    padding-top: var(--abstand-klein);
  }

  /* Fünf Felder je Zeile wie im Bildungsplan; Bereich e bricht dort ebenfalls um. */
  ul {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: var(--abstand-klein);
    margin: 0;
    padding: 0;
    list-style: none;
  }

  /* Als Flexbox, weil ein Knopf seinen Inhalt sonst vertikal zentriert. */
  .kachel {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    width: 100%;
    height: 100%;
    padding: var(--abstand-klein);
    background: var(--grund);
    border: var(--rahmen-breite) solid var(--rahmen);
    font-size: inherit;
    text-align: left;
    hyphens: auto;
  }

  /* Nur anklickbare Felder reagieren auf die Maus. */
  button.kachel {
    transition:
      border-color 0.15s,
      background-color 0.15s;
  }

  button.kachel:hover {
    background: var(--hover);
    border-color: var(--akzent);
  }

  .kopf {
    display: flex;
    gap: var(--abstand-klein);
    align-items: center;
  }

  .ziffer {
    margin-left: 0.1em;
  }

  .marke {
    width: 0.625rem;
    height: 0.625rem;
  }

  .anzahl {
    margin-left: auto;
    padding: 0 0.45em;
    background: var(--akzent);
    color: var(--akzent-text);
    font-weight: 600;
    font-variant-numeric: tabular-nums;
  }

  .name {
    display: block;
  }

  .fussabdruck li:not(.belegt) .kachel {
    border-style: dashed;
    color: var(--text-leise);
  }

  .fussabdruck .belegt .kachel {
    background: var(--flaeche);
    border-color: var(--akzent);
  }

  @media (max-width: 48rem) {
    section {
      grid-template-columns: 1fr;
    }

    ul {
      grid-template-columns: repeat(2, 1fr);
    }
  }
</style>
