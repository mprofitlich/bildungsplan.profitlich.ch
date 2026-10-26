# 0003 — Bearbeitungssperre und Versionsprüfung

**Status:** angenommen, 2026-10-26

## Kontext

Freigegebene Kurse ([0002](0002-login-freigaben-curriculum.md)) können mehrere Personen bearbeiten. Die Anwendung speichert laufend und ohne Speichern-Knopf; jede Person hat immer einen aktiven Kurs, in den «Hinzufügen» schreibt. Ohne Vorkehrung überschreibt die letzte Speicherung stillschweigend die andere.

## Entscheidung

Zwei Mechanismen zusammen:

- **Bearbeitungssperre:** Wer einen Kurs bearbeitet, hält eine Sperre. Der Browser erneuert sie etwa alle 30 Sekunden, solange der Tab sichtbar ist und in den letzten fünf Minuten eine Eingabe kam. Ohne Erneuerung läuft sie nach rund zwei Minuten ab. Alle anderen sehen den Kurs währenddessen schreibgeschützt, mit Hinweis, wer ihn bearbeitet.
- **Versionsprüfung:** Jede Speicherung nennt die Version, von der sie ausging. Ist der Kurs inzwischen neuer, lehnt der Server ab; die Anwendung meldet das und lädt den aktuellen Stand.

Eine Sperre wird nie von Hand aufgehoben; sie läuft ab.

## Begründung

- **Letzte Speicherung gilt** verworfen: Verluste blieben unbemerkt.
- **Nur Versionsprüfung** verworfen: Bei laufendem Speichern gäbe es im Normalfall gleichzeitiger Arbeit ständig Konflikte statt eines klaren «gerade belegt».
- **Nur Sperre** verworfen: Läuft eine Sperre während eines Netzunterbruchs ab, könnten danach beide speichern; die Versionsprüfung fängt das ab.
- **Sperre am aktiven Kurs** verworfen: Ein offener, vergessener Tab hielte den Kurs unbegrenzt fest.

## Konsequenzen

- Es braucht zwei Sperrspalten am Kurs (wer, bis wann), einen regelmässigen Abgleich und einen schreibgeschützten Zustand in den Ansichten «Kurs» und «Kompetenzen».
- Hat jemand den Tab eben verlassen, wartet die nächste Person bis zu zwei Minuten, bis die verwaiste Sperre abläuft.
