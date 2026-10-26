import { anfrage, fehlermeldung } from './server.js';

/**
 * @typedef {object} Ich
 * @property {string} email
 * @property {string} domain
 */

/** Die Seite bringt mit, wer eingeloggt ist und welche Domains es dürfen; ohne Server (Tests) fehlt beides. */
function lesen(feld, ersatz) {
  try {
    return JSON.parse(document.getElementById('app')?.dataset[feld]) ?? ersatz;
  } catch {
    return ersatz;
  }
}

const imBrowser = typeof document !== 'undefined';

/**
 * Die eingeloggte Person oder `null`, dazu die Domains, deren Adressen sich einloggen dürfen.
 * @type {{ ich: Ich | null, domains: string[] }}
 */
export const konto = $state({
  ich: imBrowser ? lesen('ich', null) : null,
  domains: imBrowser ? lesen('domains', []) : [],
});

/**
 * Fordert einen Link zum Einloggen an.
 * @param {string} email
 * @returns {Promise<{ ok: boolean, meldung: string }>}
 */
export async function linkAnfordern(email) {
  const antwort = await anfrage('POST', '/login', { email });
  return antwort.status === 200
    ? { ok: true, meldung: antwort.daten.meldung }
    : { ok: false, meldung: fehlermeldung(antwort) };
}

/** Meldet ab und lädt die Seite neu; danach gelten wieder die Kurse dieses Browsers. */
export async function abmelden() {
  await anfrage('POST', '/logout');
  location.reload();
}
