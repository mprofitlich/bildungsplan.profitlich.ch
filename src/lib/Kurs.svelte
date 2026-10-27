<script>
  import Freigabe from './Freigabe.svelte';
  import { konto } from './konto.svelte.js';
  import KursInhalt from './KursInhalt.svelte';
  import { KRITERIEN } from './kriterien.js';
  import { aktiverKurs, kursLoeschen, nurLesen } from './kurse.svelte.js';
  import { wachsend } from './wachsend.js';

  /** `ongeloescht` führt nach dem Löschen zurück zur Übersicht der Kurse. */
  let { ongeloescht } = $props();

  const kurs = $derived(aktiverKurs());
  const gesperrt = $derived(nurLesen(kurs));
  // Einen fremden Kurs löschen darf nur, wer ihn besitzt; ohne Login gehört jeder Kurs dem Browser.
  const eigener = $derived(!konto.ich || kurs.istBesitzer !== false);

  function loeschen() {
    if (!confirm(`Kurs «${kurs.titel}» mit ${kurs.ziele.length} Leistungszielen löschen?`)) return;
    kursLoeschen();
    ongeloescht();
  }
</script>


{#if gesperrt}
  <p class="hinweis" role="status">
    {kurs.sperre.email} bearbeitet diesen Kurs gerade. Sobald die Sperre frei ist, lässt er sich wieder ändern.
  </p>
{/if}

{#if konto.ich && !eigener}
  <p class="leise">Kurs von {kurs.besitzer}, für dich zum Mitbearbeiten freigegeben.</p>
{/if}

<label class="feld">
  <span>Titel</span>
  <input type="text" bind:value={kurs.titel} readonly={gesperrt} />
</label>

<label class="feld">
  <span>Beschrieb</span>
  <textarea
    class="wachsend"
    rows="4"
    bind:value={kurs.beschrieb}
    readonly={gesperrt}
    {@attach wachsend(() => kurs.beschrieb)}
  ></textarea>
</label>

<h2>Beurteilungskriterien</h2>
<div class="kriterien">
  {#each KRITERIEN as kriterium (kriterium.id)}
    <label class="kriterium">
      <span>{kriterium.name}</span>
      <textarea
        class="wachsend"
        rows="1"
        bind:value={kurs.kriterien[kriterium.id]}
        readonly={gesperrt}
        {@attach wachsend(() => kurs.kriterien[kriterium.id])}
      ></textarea>
    </label>
  {/each}
</div>

{#if konto.ich && eigener && kurs.version !== undefined}
  <Freigabe {kurs} />
{/if}

<KursInhalt {kurs} />

{#if eigener}
  <p><button class="loeschen" onclick={loeschen} disabled={gesperrt}>Kurs löschen</button></p>
{/if}

<style>
  .feld {
    margin-bottom: var(--abstand);
  }

  /* Die Höhe folgt dem Inhalt; ein Ziehgriff und eine Bildlaufleiste wären dabei nur im Weg. */
  .wachsend {
    overflow: hidden;
    resize: none;
  }

  /* Wie die Abschnitte im Inhalt des Kurses darunter. */
  h2 {
    margin: var(--abstand-gross) 0 var(--abstand);
    font-size: 1.375rem;
  }

  /* Bezeichnung links, Text rechts, wie in der Beurteilungstabelle; schmal untereinander. */
  .kriterien {
    display: grid;
    grid-template-columns: max-content minmax(0, 1fr);
    gap: var(--abstand) var(--abstand-gross);
    align-items: baseline;
  }

  .kriterium {
    display: grid;
    grid-column: 1 / -1;
    grid-template-columns: subgrid;
    align-items: baseline;
  }

  .kriterium span {
    font-weight: 600;
  }

  @media (max-width: 40rem) {
    .kriterien {
      grid-template-columns: minmax(0, 1fr);
      gap: var(--abstand);
    }

    .kriterium {
      gap: var(--abstand-klein);
    }
  }

  .hinweis {
    padding: var(--abstand-klein) var(--abstand);
    background: var(--treffer);
  }

  .loeschen {
    border-color: var(--gefahr);
    color: var(--gefahr);
  }

  .loeschen:hover {
    background: var(--gefahr);
    border-color: var(--gefahr);
    color: var(--gefahr-text);
  }

  p {
    margin: var(--abstand) 0;
  }

  .leise {
    color: var(--text-leise);
  }
</style>
