import { anfrage, fehlermeldung } from './server.js';
import { aktiverKurs, lokalenStandVergessen, lokalerStand, sicherungLesen, uebernehmen, zustand } from './kurse.svelte.js';

// So lange nach der letzten Änderung, bis gespeichert wird; Tippen löst nicht jedes Mal eine Anfrage aus.
const WARTEZEIT = 800;
// Takte für das Erneuern der Sperre und das Nachfragen nach dem Stand des aktiven Kurses (ADR 0003).
const PULS = 30_000;
const ABFRAGE = 20_000;
// Ohne Eingabe in dieser Zeit gilt der Kurs nicht mehr als in Bearbeitung.
const RUHE = 5 * 60_000;

/**
 * Meldung an die eingeloggte Person, etwa wenn ihre Änderung abgelehnt wurde.
 * @type {{ meldung: string }}
 */
export const abgleich = $state({ meldung: '' });

/**
 * Der Teil eines Kurses, den Speichern überträgt, als vergleichbarer Text.
 * @param {import('./kurse.svelte.js').Kurs} kurs
 * @returns {string}
 */
export function inhalt(kurs) {
  return JSON.stringify([kurs.titel, kurs.beschrieb, kurs.ziele]);
}

/**
 * Vergleicht die Kurse mit dem zuletzt gespeicherten Stand.
 * @param {import('./kurse.svelte.js').Kurs[]} kurse
 * @param {Map<string, string>} gespeichert Inhalt je Kurs-Id, wie er auf dem Server liegt.
 * @returns {{ neu: string[], geaendert: string[], entfernt: string[] }} Kurs-Ids.
 */
export function aenderungen(kurse, gespeichert) {
  const vorhanden = new Set(kurse.map((kurs) => kurs.id));
  // Den Inhalt jedes Kurses lesen, auch eines neuen: Nur was hier gelesen wird, löst den Effekt in
  // `starten` wieder aus. Sonst blieben Änderungen an einem Kurs ungespeichert, der gerade angelegt wird.
  const aktuell = new Map(kurse.map((kurs) => [kurs.id, inhalt(kurs)]));
  return {
    neu: kurse.filter((kurs) => !gespeichert.has(kurs.id)).map((kurs) => kurs.id),
    geaendert: kurse
      .filter((kurs) => gespeichert.has(kurs.id) && gespeichert.get(kurs.id) !== aktuell.get(kurs.id))
      .map((kurs) => kurs.id),
    entfernt: [...gespeichert.keys()].filter((id) => !vorhanden.has(id)),
  };
}

/** @type {Map<string, string>} */
const gespeichert = new Map();
/** @type {Map<string, ReturnType<typeof setTimeout>>} */
const uhren = new Map();
/** Kurse mit laufender Anfrage; wer währenddessen ändert, wird danach nochmals gespeichert. */
const laufend = new Set();
const nochmal = new Set();

let letzteEingabe = Date.now();

function finden(id) {
  return zustand.kurse.find((kurs) => kurs.id === id);
}

/** Übernimmt vom Server alles ausser dem Inhalt, der sich lokal inzwischen geändert haben kann. */
function rahmenUebernehmen(kurs, daten) {
  kurs.nummer = daten.nummer;
  kurs.version = daten.version;
  kurs.besitzer = daten.besitzer;
  kurs.istBesitzer = daten.istBesitzer;
  kurs.imCurriculum = daten.imCurriculum;
  kurs.freigaben = daten.freigaben;
  kurs.sperre = daten.sperre;
}

/** Ersetzt einen Kurs ganz durch den Stand vom Server. */
function einsetzen(daten) {
  const kurs = finden(daten.id);
  if (!kurs) return;
  rahmenUebernehmen(kurs, daten);
  kurs.titel = daten.titel;
  kurs.beschrieb = daten.beschrieb;
  kurs.ziele = daten.ziele;
  gespeichert.set(kurs.id, inhalt(kurs));
}

function planen(id) {
  clearTimeout(uhren.get(id));
  uhren.set(
    id,
    setTimeout(() => {
      uhren.delete(id);
      speichern(id);
    }, WARTEZEIT),
  );
}

async function speichern(id) {
  if (laufend.has(id)) {
    nochmal.add(id);
    return;
  }
  const kurs = finden(id);
  if (!kurs || (kurs.version !== undefined && gespeichert.get(id) === inhalt(kurs))) return;

  const stand = inhalt(kurs);
  const daten = { titel: kurs.titel, beschrieb: kurs.beschrieb, ziele: [...kurs.ziele] };
  laufend.add(id);
  const antwort =
    kurs.version === undefined
      ? await anfrage('POST', '/kurse', { id, ...daten })
      : await anfrage('PUT', `/kurse/${id}`, { ...daten, version: kurs.version });
  laufend.delete(id);

  const aktuell = finden(id);
  if (!aktuell) return;
  if (antwort.status === 200 || antwort.status === 201) {
    rahmenUebernehmen(aktuell, antwort.daten);
    gespeichert.set(id, stand);
    abgleich.meldung = '';
  } else if (antwort.status === 409) {
    einsetzen(antwort.daten);
    abgleich.meldung = `Jemand anderes hat «${aktuell.titel}» inzwischen geändert. Der aktuelle Stand ist geladen; deine letzte Änderung fehlt darin.`;
  } else if (antwort.status === 423) {
    einsetzen(antwort.daten);
    abgleich.meldung = `${antwort.daten.sperre.email} bearbeitet «${aktuell.titel}» gerade. Deine letzte Änderung ist nicht gespeichert.`;
  } else {
    abgleich.meldung = `Nicht gespeichert: ${fehlermeldung(antwort)}`;
  }

  if (nochmal.delete(id)) planen(id);
}

async function loeschen(id) {
  gespeichert.delete(id);
  clearTimeout(uhren.get(id));
  const antwort = await anfrage('DELETE', `/kurse/${id}`);
  if (antwort.status !== 204) abgleich.meldung = `Nicht gelöscht: ${fehlermeldung(antwort)}`;
}

/** Nimmt einen Kurs aus der Liste, ohne ihn auf dem Server zu löschen. */
function vergessen(id) {
  gespeichert.delete(id);
  uebernehmen(zustand.kurse.filter((kurs) => kurs.id !== id));
}

/** Holt den Stand des aktiven Kurses: Sperre, Freigaben und Änderungen anderer. */
async function abfragen() {
  if (document.visibilityState !== 'visible') return;
  const kurs = aktiverKurs();
  if (kurs.version === undefined || laufend.has(kurs.id) || uhren.has(kurs.id)) return;

  const antwort = await anfrage('GET', `/kurse/${kurs.id}`);
  if (antwort.status === 403 || antwort.status === 404) {
    abgleich.meldung = `Der Kurs «${kurs.titel}» ist nicht mehr für dich freigegeben.`;
    vergessen(kurs.id);
  } else if (antwort.status === 200 && finden(kurs.id)) {
    const unveraendert = gespeichert.get(kurs.id) === inhalt(kurs);
    if (unveraendert && antwort.daten.version !== kurs.version) einsetzen(antwort.daten);
    else rahmenUebernehmen(kurs, antwort.daten);
  }
}

/** Erneuert die eigene Sperre, solange jemand am Kurs arbeitet, und gibt sie sonst frei. */
async function puls() {
  const kurs = aktiverKurs();
  if (!kurs.sperre?.eigene) return;
  if (document.visibilityState !== 'visible' || Date.now() - letzteEingabe > RUHE) {
    entsperren(kurs);
    return;
  }
  const antwort = await anfrage('POST', `/kurse/${kurs.id}/sperre`);
  if ((antwort.status === 200 || antwort.status === 423) && finden(kurs.id)) rahmenUebernehmen(kurs, antwort.daten);
}

function entsperren(kurs, { keepalive = false } = {}) {
  if (!kurs?.sperre?.eigene) return;
  kurs.sperre = null;
  anfrage('DELETE', `/kurse/${kurs.id}/sperre`, undefined, { keepalive });
}

/**
 * Übernimmt beim ersten Laden nach dem Login die Kurse aus dem Browser, lädt alle Kurse vom
 * Server und hält sie von da an abgeglichen.
 * @returns {Promise<boolean>} `false`, wenn der Server die Kurse nicht geliefert hat.
 */
export async function starten() {
  const lokal = lokalerStand();
  const antwort = lokal
    ? await anfrage('POST', '/kurse/import', {
        kurse: lokal.kurse.map(({ id, titel, beschrieb, ziele }) => ({ id, titel, beschrieb, ziele })),
      })
    : await anfrage('GET', '/kurse');
  if (antwort.status !== 200) {
    abgleich.meldung = `Die Kurse konnten nicht geladen werden (${fehlermeldung(antwort)}). Bitte die Seite neu laden.`;
    return false;
  }
  if (lokal) lokalenStandVergessen();

  for (const kurs of antwort.daten) gespeichert.set(kurs.id, inhalt(kurs));
  uebernehmen(antwort.daten);
  if (lokal && finden(lokal.aktiv)) zustand.aktiv = lokal.aktiv;

  $effect.root(() => {
    $effect(() => {
      const { neu, geaendert, entfernt } = aenderungen(zustand.kurse, gespeichert);
      for (const id of [...neu, ...geaendert]) planen(id);
      for (const id of entfernt) loeschen(id);
    });

    // Wer den Kurs wechselt, gibt die Sperre des bisherigen frei.
    let vorher = zustand.aktiv;
    $effect(() => {
      const jetzt = zustand.aktiv;
      if (jetzt !== vorher) entsperren(finden(vorher));
      vorher = jetzt;
    });
  });

  for (const ereignis of ['pointerdown', 'keydown']) {
    addEventListener(ereignis, () => (letzteEingabe = Date.now()), { capture: true, passive: true });
  }
  addEventListener('pagehide', () => entsperren(aktiverKurs(), { keepalive: true }));
  setInterval(abfragen, ABFRAGE);
  setInterval(puls, PULS);
  return true;
}

/**
 * Zeigt einen eigenen Kurs im Curriculum oder nimmt ihn heraus.
 * @param {import('./kurse.svelte.js').Kurs} kurs
 * @param {boolean} sichtbar
 */
export async function curriculumSetzen(kurs, sichtbar) {
  const antwort = await anfrage('PUT', `/kurse/${kurs.id}/curriculum`, { sichtbar });
  if (antwort.status === 200) rahmenUebernehmen(kurs, antwort.daten);
  else abgleich.meldung = fehlermeldung(antwort);
}

/**
 * Gibt einen eigenen Kurs an eine Mailadresse frei.
 * @param {import('./kurse.svelte.js').Kurs} kurs
 * @param {string} email
 * @returns {Promise<string>} Leer bei Erfolg, sonst die Fehlermeldung.
 */
export async function freigeben(kurs, email) {
  const antwort = await anfrage('POST', `/kurse/${kurs.id}/freigaben`, { email });
  if (antwort.status !== 200) return fehlermeldung(antwort);
  rahmenUebernehmen(kurs, antwort.daten);
  return '';
}

/**
 * Zieht eine Freigabe zurück.
 * @param {import('./kurse.svelte.js').Kurs} kurs
 * @param {string} email
 */
export async function freigabeEntfernen(kurs, email) {
  const antwort = await anfrage('DELETE', `/kurse/${kurs.id}/freigaben`, { email });
  if (antwort.status === 200) rahmenUebernehmen(kurs, antwort.daten);
  else abgleich.meldung = fehlermeldung(antwort);
}

/**
 * Bekannte Personen der eigenen Domain, deren Adresse die Suche enthält.
 * @param {string} suche
 * @returns {Promise<string[]>}
 */
export async function personen(suche) {
  const antwort = await anfrage('GET', `/personen?suche=${encodeURIComponent(suche)}`);
  return antwort.status === 200 ? antwort.daten : [];
}

/**
 * Fügt die Kurse einer Sicherungsdatei zu den eigenen hinzu; schon vorhandene bleiben, wie sie sind.
 * @param {string} text Inhalt der Datei.
 * @returns {Promise<string>} Meldung für die Person.
 */
export async function sicherungHinzufuegen(text) {
  const stand = sicherungLesen(text);
  if (!stand) return 'Die Datei ist keine gültige Sicherung.';
  const antwort = await anfrage('POST', '/kurse/import', {
    kurse: stand.kurse.map(({ id, titel, beschrieb, ziele }) => ({ id, titel, beschrieb, ziele })),
  });
  if (antwort.status !== 200) return `Nicht eingelesen: ${fehlermeldung(antwort)}`;
  const neu = antwort.daten.filter((kurs) => !finden(kurs.id));
  for (const kurs of neu) {
    gespeichert.set(kurs.id, inhalt(kurs));
    zustand.kurse.push(kurs);
  }
  return neu.length === 1 ? '1 Kurs hinzugefügt.' : `${neu.length} Kurse hinzugefügt.`;
}
