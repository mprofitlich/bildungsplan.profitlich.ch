<script>
  import { curriculumSetzen, freigabeEntfernen, freigeben, personen } from './abgleich.svelte.js';
  import { konto } from './konto.svelte.js';

  /** Freigaben und Curriculum eines eigenen Kurses. */
  let { kurs } = $props();

  let email = $state('');
  let fehler = $state('');
  let laeuft = $state(false);
  /** @type {string[]} */
  let vorschlaege = $state([]);
  let uhr;

  // Vorschläge erst nach einer kurzen Tipp-Pause holen, nicht bei jedem Buchstaben.
  function suchen() {
    clearTimeout(uhr);
    uhr = setTimeout(async () => {
      const gefunden = await personen(email);
      vorschlaege = gefunden.filter((adresse) => !kurs.freigaben.includes(adresse));
    }, 250);
  }

  async function hinzufuegen(event) {
    event.preventDefault();
    laeuft = true;
    fehler = await freigeben(kurs, email);
    laeuft = false;
    if (!fehler) email = '';
  }
</script>

<section>
  <h2>Freigabe</h2>

  <label class="schalter">
    <input
      type="checkbox"
      checked={kurs.imCurriculum}
      onchange={(event) => curriculumSetzen(kurs, event.currentTarget.checked)}
    />
    Im Curriculum zeigen
    <span class="leise">– alle Eingeloggten von {konto.ich.domain} sehen den Kurs unter «Curriculum».</span>
  </label>

  <p class="leise">Wer den Kurs mitbearbeiten darf. Es geht eine Mail an die Adresse; der Kurs erscheint dort unter «Meine Kurse».</p>

  {#if kurs.freigaben.length > 0}
    <ul>
      {#each kurs.freigaben as adresse (adresse)}
        <li>
          {adresse}
          <button class="klein" onclick={() => freigabeEntfernen(kurs, adresse)}>Entfernen</button>
        </li>
      {/each}
    </ul>
  {/if}

  <form onsubmit={hinzufuegen}>
    <input
      type="email"
      required
      placeholder="name@{konto.ich.domain}"
      aria-label="Mailadresse"
      autocomplete="off"
      list="freigabe-personen"
      bind:value={email}
      oninput={suchen}
      onfocus={suchen}
    />
    <datalist id="freigabe-personen">
      {#each vorschlaege as adresse (adresse)}
        <option value={adresse}></option>
      {/each}
    </datalist>
    <button type="submit" disabled={laeuft}>Freigeben</button>
  </form>
  {#if fehler}
    <p class="fehler" role="alert">{fehler}</p>
  {/if}
</section>

<style>
  h2 {
    margin: var(--abstand-gross) 0 var(--abstand);
    font-size: 1.375rem;
  }

  .schalter {
    display: flex;
    flex-wrap: wrap;
    gap: var(--abstand-klein);
    align-items: baseline;
    font-weight: 600;
  }

  .schalter .leise {
    font-weight: 400;
  }

  ul {
    margin: var(--abstand) 0;
    padding: 0;
    border-bottom: 1px solid var(--linie);
    list-style: none;
  }

  li {
    display: flex;
    gap: var(--abstand);
    align-items: center;
    justify-content: space-between;
    padding: var(--abstand-klein) 0;
    border-top: 1px solid var(--linie);
  }

  .klein {
    padding: 0.1em var(--abstand-klein);
    font-size: var(--schrift-klein);
  }

  form {
    display: flex;
    gap: var(--abstand);
    max-width: 36rem;
  }

  form input {
    flex: 1;
    min-width: 0;
  }

  p {
    margin: var(--abstand) 0;
  }

  .leise {
    color: var(--text-leise);
  }

  .fehler {
    color: var(--gefahr);
  }
</style>
