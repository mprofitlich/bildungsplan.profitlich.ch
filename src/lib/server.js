/**
 * @typedef {object} Antwort
 * @property {number} status HTTP-Status; 0, wenn der Server nicht erreichbar war.
 * @property {any} daten Der JSON-Inhalt der Antwort, sonst `null`.
 */

/** Laravel legt das CSRF-Token in ein Cookie; jede ändernde Anfrage schickt es als Kopfzeile zurück. */
function csrfToken() {
  const eintrag = document.cookie.split('; ').find((teil) => teil.startsWith('XSRF-TOKEN='));
  return eintrag ? decodeURIComponent(eintrag.slice('XSRF-TOKEN='.length)) : '';
}

/**
 * Schickt eine Anfrage an die API der Anwendung.
 * @param {'GET' | 'POST' | 'PUT' | 'DELETE'} methode
 * @param {string} pfad Pfad unterhalb von `/api`, etwa `/kurse`.
 * @param {unknown} [daten] Wird als JSON mitgeschickt.
 * @param {{ keepalive?: boolean }} [optionen] `keepalive` lässt die Anfrage das Schliessen der Seite überdauern.
 * @returns {Promise<Antwort>}
 */
export async function anfrage(methode, pfad, daten, { keepalive = false } = {}) {
  let antwort;
  try {
    antwort = await fetch(`/api${pfad}`, {
      method: methode,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': csrfToken(),
      },
      body: daten === undefined ? undefined : JSON.stringify(daten),
      keepalive,
    });
  } catch {
    return { status: 0, daten: null };
  }
  const inhalt = antwort.status === 204 ? null : await antwort.json().catch(() => null);
  return { status: antwort.status, daten: inhalt };
}

/**
 * Liefert die erste Fehlermeldung einer abgelehnten Anfrage.
 * @param {Antwort} antwort
 * @returns {string}
 */
export function fehlermeldung(antwort) {
  if (antwort.status === 0) return 'Der Server ist nicht erreichbar.';
  const fehler = antwort.daten?.errors;
  if (fehler) return Object.values(fehler).flat()[0];
  if (antwort.status === 429) return 'Zu viele Versuche. Bitte später nochmals.';
  return antwort.daten?.message || 'Das hat nicht geklappt.';
}
