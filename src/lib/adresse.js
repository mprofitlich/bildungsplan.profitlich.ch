// Laravel liefert die Anwendung unter denselben Pfaden aus (routes/web.php); beide Listen gehören zusammen.

/** Ansichten mit eigenem Pfad; «Kompetenzen» liegt unter `/`, der Kurs unter `/kurs/<kennung>-<slug>`. */
export const ANSICHTEN = ['uebersicht', 'glossar', 'meine-kurse', 'curriculum', 'hilfe', 'styleguide'];

/**
 * Lesbarer Teil der Adresse aus einem Titel, etwa `schrift-gestaltung-uebungen`.
 * @param {string} titel
 * @returns {string}
 */
export function slug(titel) {
  return titel
    .toLowerCase()
    .replace(/ä/g, 'ae')
    .replace(/ö/g, 'oe')
    .replace(/ü/g, 'ue')
    .replace(/ß/g, 'ss')
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 60)
    .replace(/-+$/, '');
}

/**
 * Kennung eines Kurses in der Adresse: die Nummer vom Server, ohne Login die Id des Browsers.
 * @param {import('./kurse.svelte.js').Kurs} kurs
 * @returns {string}
 */
export function kennung(kurs) {
  return kurs.nummer ? String(kurs.nummer) : kurs.id;
}

/**
 * Pfad einer Ansicht.
 * @param {string} ansicht
 * @param {import('./kurse.svelte.js').Kurs} [kurs] nur für die Ansicht `kurs`
 * @returns {string}
 */
export function pfad(ansicht, kurs) {
  if (ansicht === 'kurs') {
    const lesbar = slug(kurs.titel);
    return `/kurs/${kennung(kurs)}${lesbar ? `-${lesbar}` : ''}`;
  }
  if (ANSICHTEN.includes(ansicht)) return `/${ansicht}`;
  return '/';
}

// Die Kennung vorne im Abschnitt: eine Id mit ihren festen 36 Zeichen oder die führenden Ziffern.
const KENNUNG = /^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}|[0-9]+)(?:-|$)/i;

/**
 * Ansicht zu einem Pfad. Unbekannte Pfade ergeben «Kompetenzen»; der Slug zählt nicht.
 * @param {string} adresse etwa `location.pathname`
 * @returns {{ ansicht: string, kennung?: string }} `kennung` fehlt bei `/kurs` ohne Nummer oder Id.
 */
export function ansichtAus(adresse) {
  const [teil, kurs] = adresse.split('/').filter(Boolean);
  if (teil === 'kurs') {
    const gefunden = kurs?.match(KENNUNG);
    return gefunden ? { ansicht: 'kurs', kennung: gefunden[1] } : { ansicht: 'kurs' };
  }
  if (ANSICHTEN.includes(teil)) return { ansicht: teil };
  return { ansicht: 'kompetenzen' };
}

/**
 * Führt einen Klick auf einen Link der Anwendung selbst aus, statt die Seite neu zu laden.
 * Mit Zusatztaste oder mittlerer Maustaste bleibt er dem Browser, etwa für einen neuen Tab.
 * @param {MouseEvent} event
 * @param {() => void} wechsel
 */
export function folgen(event, wechsel) {
  if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
  event.preventDefault();
  wechsel();
}

/**
 * Sucht den Kurs zu einer Kennung. Die Id gilt auch für Kurse mit Nummer, damit Adressen
 * von vor dem Login weiterhin führen.
 * @param {import('./kurse.svelte.js').Kurs[]} kurse
 * @param {string} gesucht
 * @returns {import('./kurse.svelte.js').Kurs | undefined}
 */
export function kursZu(kurse, gesucht) {
  return kurse.find((kurs) => kurs.id === gesucht || String(kurs.nummer) === gesucht);
}
