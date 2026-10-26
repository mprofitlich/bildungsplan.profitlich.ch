/**
 * @typedef {object} Neuigkeit
 * @property {string} id Bleibt fest; daran merkt sich der Browser, was gemeldet wurde.
 * @property {string} titel
 * @property {string} text
 */

/**
 * Neue Funktionen, die neueste zuoberst. Wer schon einmal da war, bekommt
 * jede Neuigkeit einmal gemeldet; wer zum ersten Mal kommt, keine.
 * @type {Neuigkeit[]}
 */
export const neuigkeiten = [
  {
    id: '2026-10-login',
    titel: 'Einloggen, Freigeben, Curriculum',
    text: 'Mit einer Schuladresse lässt sich einloggen (unter «Meine Kurse»). Die Kurse liegen dann auf dem Server, lassen sich an Kolleginnen und Kollegen freigeben und im neuen Reiter «Curriculum» zeigen.',
  },
  {
    id: '2026-10-glossar',
    titel: 'Glossar',
    text: 'Der neue Reiter «Glossar» erklärt die Fachbegriffe und die Begriffe der Berufsbildung.',
  },
  {
    id: '2026-10-hilfe',
    titel: 'Hilfe',
    text: 'Der neue Reiter «Hilfe» beschreibt kurz alle Funktionen.',
  },
];

/**
 * @typedef {object} Hinweise
 * @property {string[]} gemeldet Ids der Neuigkeiten, deren Meldung geschlossen wurde.
 * @property {boolean} pfeil Ob der Pfeil auf die Hilfe noch erscheint.
 */

/**
 * Liefert den Stand der Hinweise. Ohne gespeicherten Stand entscheidet, ob
 * schon Kurse gespeichert sind: Wer zum ersten Mal kommt, sieht den Pfeil auf
 * die Hilfe, aber keine Neuigkeiten, denn neu ist für ihn alles.
 * @param {unknown} gespeichert Der gespeicherte Stand, falls vorhanden.
 * @param {boolean} warSchonDa Ob der Browser schon Kurse gespeichert hat.
 * @param {Neuigkeit[]} [liste]
 * @returns {Hinweise}
 */
export function anfangsstand(gespeichert, warSchonDa, liste = neuigkeiten) {
  if (Array.isArray(gespeichert?.gemeldet) && typeof gespeichert?.pfeil === 'boolean') {
    return { gemeldet: gespeichert.gemeldet, pfeil: gespeichert.pfeil };
  }
  return warSchonDa ? { gemeldet: [], pfeil: false } : { gemeldet: liste.map((n) => n.id), pfeil: true };
}

/**
 * Liefert die Neuigkeiten, die noch nicht gemeldet wurden.
 * @param {Hinweise} stand
 * @param {Neuigkeit[]} [liste]
 * @returns {Neuigkeit[]}
 */
export function offen(stand, liste = neuigkeiten) {
  return liste.filter((neuigkeit) => !stand.gemeldet.includes(neuigkeit.id));
}
