// Die Schlüssel prüft auch der Server (Kurs::KRITERIEN); beide Listen gehören zusammen.

/**
 * Die Beurteilungskriterien eines Kurses. Die Bezeichnungen sind fest, den Text dazu
 * schreibt die Lehrperson für jeden Kurs selbst.
 * @type {{ id: string, name: string }[]}
 */
export const KRITERIEN = [
  { id: 'entwicklung', name: 'Entwicklung/Entwurf' },
  { id: 'gestaltungskonzept', name: 'Gestaltungskonzept' },
  { id: 'umsetzung', name: 'Ausführung/Umsetzung' },
  { id: 'vermittlung', name: 'Vermittlung' },
];

/**
 * Alle Kriterien mit Text, fehlende leer; unbekannte Schlüssel fallen weg. Kurse und
 * Sicherungen von vor den Kriterien haben keine.
 * @param {Record<string, unknown> | null | undefined} kriterien
 * @returns {Record<string, string>}
 */
export function kriterienVon(kriterien) {
  return Object.fromEntries(
    KRITERIEN.map(({ id }) => [id, typeof kriterien?.[id] === 'string' ? kriterien[id] : '']),
  );
}
