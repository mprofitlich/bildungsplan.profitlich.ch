// Laravel liefert die Anwendung unter denselben Pfaden aus (routes/web.php); beide Listen gehören zusammen.

/** Ansichten mit eigenem Pfad; «Kompetenzen» liegt unter `/`, der Kurs unter `/kurs/<id>`. */
export const ANSICHTEN = ['uebersicht', 'glossar', 'meine-kurse', 'curriculum', 'hilfe', 'styleguide'];

/**
 * Pfad einer Ansicht.
 * @param {string} ansicht
 * @param {string} [kursId] nur für die Ansicht `kurs`
 * @returns {string}
 */
export function pfad(ansicht, kursId) {
  if (ansicht === 'kurs') return `/kurs/${kursId}`;
  if (ANSICHTEN.includes(ansicht)) return `/${ansicht}`;
  return '/';
}

/**
 * Ansicht zu einem Pfad. Unbekannte Pfade ergeben «Kompetenzen».
 * @param {string} adresse etwa `location.pathname`
 * @returns {{ ansicht: string, kursId?: string }} `kursId` fehlt bei `/kurs` ohne Id.
 */
export function ansichtAus(adresse) {
  const [teil, id] = adresse.split('/').filter(Boolean);
  if (teil === 'kurs') return id ? { ansicht: 'kurs', kursId: id } : { ansicht: 'kurs' };
  if (ANSICHTEN.includes(teil)) return { ansicht: teil };
  return { ansicht: 'kompetenzen' };
}
