<script>
  import { achse } from './achse.js';
  import { bereiche, wortlaut } from './bildungsplan.js';
  import { fassung } from './fassung.svelte.js';

  /**
   * Säulendiagramm der Leistungsziele je Handlungskompetenz, gruppiert nach Bereich a bis g.
   * `anzahl` und `bezug` wie bei der Übersicht; die Übersicht ist zugleich die Tabelle dazu.
   */
  let { anzahl = {}, bezug = 'im Kurs' } = $props();

  const zahl = (kompetenz) => anzahl[kompetenz.id] ?? 0;
  const teilung = $derived(achse(Math.max(0, ...bereiche.flatMap((b) => b.handlungskompetenzen.map(zahl)))));
  // Für Screenreader steht das Diagramm als ein Bild mit allen Werten.
  const beschreibung = $derived(
    `Leistungsziele ${bezug} je Handlungskompetenz: ` +
      bereiche.flatMap((b) => b.handlungskompetenzen.map((k) => `${k.id} ${zahl(k)}`)).join(', '),
  );

  /** @type {{ kompetenz: object, links: number, ausrichtung: 'start' | 'mitte' | 'ende' } | null} */
  let gezeigt = $state(null);
  /** @type {HTMLElement} */
  let flaeche;

  // Der Hinweis steht oben in der Fläche statt darüber: Der seitliche Scrollbereich schnitte ihn sonst ab.
  // Nah am Rand richtet er sich nach innen aus, damit er nicht aus der Fläche ragt.
  function zeigen(event, kompetenz) {
    const saeule = event.currentTarget.getBoundingClientRect();
    const rahmen = flaeche.getBoundingClientRect();
    const links = saeule.left - rahmen.left + saeule.width / 2;
    const rand = 8 * 16;
    const ausrichtung = links < rand ? 'start' : links > rahmen.width - rand ? 'ende' : 'mitte';
    gezeigt = { kompetenz, links, ausrichtung };
  }
</script>

<div class="rollen">
  <div class="diagramm" role="img" aria-label={beschreibung}>
    <div class="achse" aria-hidden="true">
      {#each teilung.striche as strich (strich)}
        <span style:bottom="{(strich / teilung.oben) * 100}%">{strich}</span>
      {/each}
    </div>

    <!-- Der Hinweis ergänzt nur die Maus; alle Werte stehen in der Beschreibung des Bilds und in der Matrix. -->
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div class="flaeche" bind:this={flaeche} onpointerleave={() => (gezeigt = null)} aria-hidden="true">
      {#each teilung.striche as strich (strich)}
        <span class="linie" style:bottom="{(strich / teilung.oben) * 100}%"></span>
      {/each}

      {#each bereiche as bereich (bereich.id)}
        <div class="gruppe" style:--anzahl={bereich.handlungskompetenzen.length}>
          {#each bereich.handlungskompetenzen as kompetenz (kompetenz.id)}
            <!-- svelte-ignore a11y_no_static_element_interactions -->
            <div class="saeule" onpointerenter={(event) => zeigen(event, kompetenz)}>
              {#if zahl(kompetenz) > 0}
                <span class="balken" style:height="{(zahl(kompetenz) / teilung.oben) * 100}%"></span>
              {/if}
              <span class="kennung">{kompetenz.id}</span>
            </div>
          {/each}
        </div>
      {/each}

      {#if gezeigt}
        <div class="hinweis {gezeigt.ausrichtung}" style:left="{gezeigt.links}px">
          <strong>{gezeigt.kompetenz.id}</strong>
          {wortlaut(gezeigt.kompetenz, fassung.id).titel}
          <span class="wert">{zahl(gezeigt.kompetenz)} {zahl(gezeigt.kompetenz) === 1 ? 'Leistungsziel' : 'Leistungsziele'} {bezug}</span>
        </div>
      {/if}
    </div>
  </div>
</div>

<style>
  /* Schmal scrollt das Diagramm seitlich, statt die Säulen unlesbar dünn zu machen. */
  /* Oben Luft für die halbe Höhe der obersten Achsenzahl, die über der Fläche steht. */
  .rollen {
    overflow-x: auto;
    padding: var(--abstand) 0 var(--abstand-klein);
  }

  .diagramm {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: var(--abstand-klein);
    /* Breit genug, dass die Kennungen unter 33 Säulen nicht aneinanderstossen. */
    min-width: 50rem;
    font-size: var(--schrift-klein);
    font-variant-numeric: tabular-nums;
  }

  /* Achse und Fläche gleich hoch; unter der Fläche bleibt Platz für die Kennungen. */
  .achse,
  .flaeche {
    position: relative;
    height: 12rem;
    margin-bottom: 1.75rem;
  }

  .achse {
    min-width: 1.5rem;
    color: var(--text-leise);
    text-align: right;
  }

  .achse span {
    position: absolute;
    right: 0;
    transform: translateY(50%);
    line-height: 1;
  }

  .flaeche {
    display: flex;
    gap: var(--abstand);
    align-items: stretch;
  }

  /* Rasterlinien zurückhaltend, damit die Säulen vorne stehen. */
  .linie {
    position: absolute;
    right: 0;
    left: 0;
    border-top: 1px solid var(--linie);
  }

  .gruppe {
    display: flex;
    flex: var(--anzahl, 1) 1 0;
    gap: 2px;
  }

  /* Die ganze Höhe ist Zielfläche für den Hinweis, nicht nur der Balken. */
  .saeule {
    position: relative;
    display: flex;
    flex: 1 1 0;
    align-items: flex-end;
    justify-content: center;
  }

  .saeule:hover {
    background: color-mix(in srgb, var(--grundton) 8%, transparent);
  }

  .balken {
    width: 100%;
    max-width: 2rem;
    background: var(--grundton);
    border-radius: 4px 4px 0 0;
  }

  .kennung {
    position: absolute;
    top: 100%;
    padding-top: var(--abstand-klein);
    color: var(--text-leise);
    line-height: 1;
  }

  .hinweis {
    position: absolute;
    top: 0;
    z-index: 2;
    width: max-content;
    max-width: 16rem;
    padding: var(--abstand-klein) var(--abstand);
    background: var(--grund);
    border: var(--rahmen-breite) solid var(--rahmen);
    color: var(--text);
    pointer-events: none;
  }

  .hinweis.mitte {
    transform: translateX(-50%);
  }

  .hinweis.ende {
    transform: translateX(-100%);
  }

  .wert {
    display: block;
    margin-top: 0.25em;
    font-weight: 600;
  }
</style>
