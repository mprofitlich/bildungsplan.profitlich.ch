import { ziel } from './bildungsplan.js';

/**
 * Zählt die Leistungsziele aller Kurse je Handlungskompetenz. Ein Ziel, das in drei Kursen
 * vorkommt, zählt dreimal (ADR 0002); unbekannte Ids zählen nicht.
 * @param {{ ziele: string[] }[]} kurse
 * @returns {Record<string, number>} Anzahl je Id der Handlungskompetenz.
 */
export function summeJeKompetenz(kurse) {
  /** @type {Record<string, number>} */
  const summe = {};
  for (const id of kurse.flatMap((kurs) => kurs.ziele)) {
    const gefunden = ziel(id);
    if (gefunden) summe[gefunden.kompetenz] = (summe[gefunden.kompetenz] ?? 0) + 1;
  }
  return summe;
}
