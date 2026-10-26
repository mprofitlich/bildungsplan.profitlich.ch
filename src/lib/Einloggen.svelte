<script>
  import { konto, linkAnfordern } from './konto.svelte.js';

  let email = $state('');
  let laeuft = $state(false);
  /** @type {{ ok: boolean, meldung: string } | null} */
  let ergebnis = $state(null);

  const domains = $derived(new Intl.ListFormat('de-CH', { type: 'conjunction' }).format(konto.domains));

  async function anfordern(event) {
    event.preventDefault();
    laeuft = true;
    ergebnis = await linkAnfordern(email);
    laeuft = false;
  }
</script>

<h2>Einloggen</h2>
<p class="leise">
  Mit einer Adresse von {domains} liegen die Kurse auf dem Server statt nur in diesem Browser. Sie lassen
  sich dann an Kolleginnen und Kollegen freigeben und im Curriculum zeigen. Beim ersten Login kommen die
  Kurse dieses Browsers mit.
</p>

<form onsubmit={anfordern}>
  <input type="email" required autocomplete="email" placeholder="name@{konto.domains[0] ?? 'schule.ch'}" aria-label="Mailadresse" bind:value={email} />
  <button type="submit" disabled={laeuft}>Link schicken</button>
</form>

{#if ergebnis}
  <p class:fehler={!ergebnis.ok} role={ergebnis.ok ? 'status' : 'alert'}>
    {ergebnis.meldung}
    {#if ergebnis.ok}Er gilt eine Viertelstunde.{/if}
  </p>
{/if}

<style>
  h2 {
    margin: var(--abstand-gross) 0 var(--abstand);
    font-size: 1.375rem;
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
    max-width: 62.75rem;
    margin: var(--abstand) 0;
  }

  .leise {
    color: var(--text-leise);
  }

  .fehler {
    color: var(--gefahr);
  }
</style>
