/**
 * Teilung einer Achse für Anzahlen: runde Schritte (1, 2, 5, 10, 20, 50 …) und eine
 * Obergrenze, die den grössten Wert einschliesst. Rund vier Teilstriche, nie Bruchzahlen.
 * @param {number} groesster grösster Wert, auch 0
 * @returns {{ oben: number, striche: number[] }} `striche` von 0 bis `oben`
 */
export function achse(groesster) {
  const ziel = Math.max(groesster, 1) / 4;
  const zehner = 10 ** Math.floor(Math.log10(ziel));
  const schritt = Math.max(1, [1, 2, 5, 10].map((faktor) => faktor * zehner).find((s) => s >= ziel));
  const oben = Math.max(schritt, Math.ceil(groesster / schritt) * schritt);
  const striche = Array.from({ length: oben / schritt + 1 }, (_, i) => i * schritt);
  return { oben, striche };
}
