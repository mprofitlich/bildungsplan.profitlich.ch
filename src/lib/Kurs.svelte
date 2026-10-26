<script>
  import Freigabe from './Freigabe.svelte';
  import { konto } from './konto.svelte.js';
  import KursInhalt from './KursInhalt.svelte';
  import { aktiverKurs, kursLoeschen, nurLesen } from './kurse.svelte.js';

  /** `ongeloescht` führt nach dem Löschen zurück zur Übersicht der Kurse. */
  let { ongeloescht } = $props();

  const kurs = $derived(aktiverKurs());
  const gesperrt = $derived(nurLesen(kurs));
  // Einen fremden Kurs löschen darf nur, wer ihn besitzt; ohne Login gehört jeder Kurs dem Browser.
  const eigener = $derived(!konto.ich || kurs.istBesitzer !== false);

  /** @type {HTMLTextAreaElement} */
  let beschrieb;
  let fensterbreite = $state(0);

  /*
   * Der Beschrieb wächst mit dem Inhalt; `rows` bleibt die Mindesthöhe, weil
   * «auto» zuerst darauf zurückfällt. Per Script statt `field-sizing: content`,
   * das noch nicht alle Browser kennen. Die Fensterbreite zählt mit, weil sich
   * mit ihr der Umbruch ändert.
   */
  $effect(() => {
    kurs.beschrieb;
    fensterbreite;
    beschrieb.style.height = 'auto';
    beschrieb.style.height = `${beschrieb.scrollHeight + beschrieb.offsetHeight - beschrieb.clientHeight}px`;
  });

  function loeschen() {
    if (!confirm(`Kurs «${kurs.titel}» mit ${kurs.ziele.length} Leistungszielen löschen?`)) return;
    kursLoeschen();
    ongeloescht();
  }
</script>

<svelte:window bind:innerWidth={fensterbreite} />

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
  <textarea class="beschrieb" rows="4" bind:this={beschrieb} bind:value={kurs.beschrieb} readonly={gesperrt}></textarea>
</label>

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
  .beschrieb {
    overflow: hidden;
    resize: none;
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
