import { konto } from './konto.svelte.js';
import { kriterienVon } from './kriterien.js';

const SCHLUESSEL = 'bildungsplan.kurse';
// Mit Login liegen die Kurse auf dem Server; der Browser merkt sich nur, welcher aktiv ist.
const SCHLUESSEL_AKTIV = 'bildungsplan.aktiv';

/**
 * @typedef {object} Sperre
 * @property {string} email Wer den Kurs gerade bearbeitet.
 * @property {boolean} eigene Ob die Sperre der eingeloggten Person gehört.
 * @property {string} bis
 */

/**
 * Die Felder ab `nummer` kommen vom Server und fehlen ohne Login.
 * @typedef {object} Kurs
 * @property {string} id
 * @property {string} titel
 * @property {string} beschrieb
 * @property {Record<string, string>} kriterien Text je Beurteilungskriterium (`kriterien.js`).
 * @property {string[]} ziele Ids der gewählten Leistungsziele.
 * @property {number} [nummer] Kurze Kennung für die Adresse.
 * @property {number} [version] Stand auf dem Server; fehlt, solange der Kurs dort noch nicht angelegt ist.
 * @property {string} [besitzer]
 * @property {boolean} [istBesitzer]
 * @property {boolean} [imCurriculum]
 * @property {string[]} [freigaben]
 * @property {Sperre | null} [sperre]
 */

/** @returns {Kurs} */
function leererKurs() {
  return { id: crypto.randomUUID(), titel: 'Neuer Kurs', beschrieb: '', kriterien: kriterienVon(null), ziele: [] };
}

/** Ergänzt die Kriterien in einem Stand aus dem Browser oder einer Sicherung, die älter sein kann als sie. */
function ergaenzt(stand) {
  return { ...stand, kurse: stand.kurse.map((kurs) => ({ ...kurs, kriterien: kriterienVon(kurs.kriterien) })) };
}

/** Prüft, ob ein geladener Stand die erwartete Form hat. */
function istGueltig(stand) {
  return (
    Array.isArray(stand?.kurse) &&
    stand.kurse.length > 0 &&
    stand.kurse.every(
      (kurs) =>
        typeof kurs.id === 'string' &&
        typeof kurs.titel === 'string' &&
        typeof kurs.beschrieb === 'string' &&
        Array.isArray(kurs.ziele),
    ) &&
    stand.kurse.some((kurs) => kurs.id === stand.aktiv)
  );
}

/**
 * Liefert die Kurse, die in diesem Browser gespeichert sind, oder `null`.
 * @returns {{ kurse: Kurs[], aktiv: string } | null}
 */
export function lokalerStand() {
  try {
    const stand = JSON.parse(localStorage.getItem(SCHLUESSEL));
    if (istGueltig(stand)) return ergaenzt(stand);
  } catch {
    // Unlesbarer Speicher wird wie ein leerer behandelt.
  }
  return null;
}

/** Entfernt die Kurse aus dem Browser, nachdem der Server sie übernommen hat. */
export function lokalenStandVergessen() {
  try {
    localStorage.removeItem(SCHLUESSEL);
  } catch {
    // Ohne Speicher gibt es nichts zu vergessen.
  }
}

function laden() {
  const stand = lokalerStand();
  if (stand) return stand;
  const kurs = leererKurs();
  return { kurse: [kurs], aktiv: kurs.id };
}

/**
 * Alle Kurse und die Id des gerade bearbeiteten Kurses.
 * @type {{ kurse: Kurs[], aktiv: string }}
 */
export const zustand = $state(laden());

$effect.root(() => {
  $effect(() => {
    try {
      if (konto.ich) localStorage.setItem(SCHLUESSEL_AKTIV, zustand.aktiv);
      else localStorage.setItem(SCHLUESSEL, JSON.stringify(zustand));
    } catch {
      // Ohne Speicher gelten die Kurse nur bis zum Neuladen.
    }
  });
});

/**
 * Ersetzt die Kurse durch den Stand vom Server. Aktiv bleibt der Kurs, den dieser Browser
 * zuletzt bearbeitet hat; gibt es keinen, kommt ein leerer dazu.
 * @param {Kurs[]} kurse
 */
export function uebernehmen(kurse) {
  let aktiv = null;
  try {
    aktiv = localStorage.getItem(SCHLUESSEL_AKTIV);
  } catch {
    // Dann gilt der erste Kurs.
  }
  zustand.kurse = kurse.length > 0 ? kurse : [leererKurs()];
  zustand.aktiv = zustand.kurse.some((kurs) => kurs.id === aktiv) ? aktiv : zustand.kurse[0].id;
}

/**
 * Ob jemand anderes den Kurs gerade bearbeitet; er lässt sich dann nur ansehen.
 * @param {Kurs} kurs
 * @returns {boolean}
 */
export function nurLesen(kurs) {
  return Boolean(kurs.sperre && !kurs.sperre.eigene);
}

/**
 * Liefert den gerade bearbeiteten Kurs.
 * @returns {Kurs}
 */
export function aktiverKurs() {
  return zustand.kurse.find((kurs) => kurs.id === zustand.aktiv);
}

/**
 * Prüft, ob ein Leistungsziel im aktiven Kurs liegt.
 * @param {string} zielId
 * @returns {boolean}
 */
export function enthaelt(zielId) {
  return aktiverKurs().ziele.includes(zielId);
}

/**
 * Legt ein Leistungsziel in den aktiven Kurs oder nimmt es wieder heraus.
 * @param {string} zielId
 */
export function zielUmschalten(zielId) {
  const kurs = aktiverKurs();
  if (nurLesen(kurs)) return;
  if (kurs.ziele.includes(zielId)) {
    kurs.ziele = kurs.ziele.filter((id) => id !== zielId);
  } else {
    kurs.ziele.push(zielId);
  }
}

/** Legt einen leeren Kurs an und macht ihn zum aktiven. */
export function kursAnlegen() {
  const kurs = leererKurs();
  zustand.kurse.push(kurs);
  zustand.aktiv = kurs.id;
}

/** Löscht den aktiven Kurs. Der letzte Kurs wird durch einen leeren ersetzt. */
export function kursLoeschen() {
  zustand.kurse = zustand.kurse.filter((kurs) => kurs.id !== zustand.aktiv);
  if (zustand.kurse.length === 0) zustand.kurse.push(leererKurs());
  zustand.aktiv = zustand.kurse[0].id;
}

/**
 * Liefert alle Kurse als JSON-Text für eine Sicherungsdatei.
 * @returns {string}
 */
export function sicherung() {
  return JSON.stringify(zustand, null, 2);
}

/**
 * Dateiname einer Sicherung mit Datum und Uhrzeit in Ortszeit, etwa
 * `bildungsplan-kurse-261015-2228-def-final-final2.json`. Der Schwanz ist
 * ein Augenzwinkern an die Dateinamen, die in jedem Grafikatelier liegen.
 * @param {Date} [zeit]
 * @returns {string}
 */
export function sicherungsname(zeit = new Date()) {
  const zweistellig = (zahl) => String(zahl).padStart(2, '0');
  const datum = [zeit.getFullYear() % 100, zeit.getMonth() + 1, zeit.getDate()].map(zweistellig).join('');
  const uhrzeit = [zeit.getHours(), zeit.getMinutes()].map(zweistellig).join('');
  return `bildungsplan-kurse-${datum}-${uhrzeit}-def-final-final2.json`;
}

/**
 * Ersetzt alle Kurse durch den Inhalt einer Sicherungsdatei.
 * @param {string} text Inhalt der Datei.
 * @returns {boolean} `false`, wenn die Datei keine gültige Sicherung ist.
 */
export function sicherungEinlesen(text) {
  const stand = sicherungLesen(text);
  if (!stand) return false;
  zustand.kurse = stand.kurse;
  zustand.aktiv = stand.aktiv;
  return true;
}

/**
 * Liest eine Sicherungsdatei.
 * @param {string} text Inhalt der Datei.
 * @returns {{ kurse: Kurs[], aktiv: string } | null} `null`, wenn die Datei keine gültige Sicherung ist.
 */
export function sicherungLesen(text) {
  try {
    const stand = JSON.parse(text);
    return istGueltig(stand) ? ergaenzt(stand) : null;
  } catch {
    return null;
  }
}
