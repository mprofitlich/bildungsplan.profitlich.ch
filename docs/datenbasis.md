# Datenbasis

`daten/bildungsplan.json` enthält den Bildungsplan Grafikerin/Grafiker EFZ in strukturierter Form. Alle Ansichten des Tools lesen aus dieser Datei; der Wortlaut eines Leistungsziels stammt immer von hier.

## Quellen

| Datei | Rolle |
| --- | --- |
| `quellen/grafiker-in-efz_bildungsplan.pdf` | Stammquelle: offizieller Bildungsplan des SBFI vom 1. Januar 2023 (Berufsnummer 90306). Liefert Nummern, Wortlaut, Lernort, K-Stufen, Fachbegriffe und Glossar. |
| `quellen/BiPla_Lernziele_230204.pdf` | Broschüre von Tobias Klauser und Felix Pfäffli zum [«Graphic Design Workflow»](https://graphicdesignworkflow.ch/). Liefert die Arbeitsphase je Handlungskompetenz und ihre Abweichungen vom Wortlaut. |

Die Datenbasis folgt dem offiziellen Bildungsplan. Wo die Broschüre abweicht, steht ihr Wert zusätzlich im Feld `broschuere`. Was an beiden Quellen korrigiert ist und worin sie sich unterscheiden, steht in [abweichungen.md](abweichungen.md). Im Tool heissen die beiden Fassungen «SGD/SGV» und «Graphic Design Workflow». Die gewählte Fassung gilt für die Ansichten «Kompetenzen», «Übersicht», «Meine Kurse» und den Kurs.

## Umfang

| | Anzahl |
| --- | --- |
| Handlungskompetenzbereiche (a–g) | 7 |
| Handlungskompetenzen | 33 |
| Leistungsziele | 357 |
| davon Betrieb | 153 |
| davon Berufsfachschule | 204 |
| Nummern an beiden Lernorten | 125 |
| Nummern an nur einem Lernort | 107 |
| Fachbegriffe (Anhang 2) | 21 |
| Einträge im Glossar der Berufsbildung | 25 |

Verteilung der Taxonomiestufen:

| | K1 | K2 | K3 | K4 | K5 | K6 |
| --- | --- | --- | --- | --- | --- | --- |
| Betrieb | 0 | 1 | 39 | 40 | 53 | 20 |
| Berufsfachschule | 9 | 59 | 53 | 46 | 29 | 8 |
| Total | 9 | 60 | 92 | 86 | 82 | 28 |

## Aufbau

```txt
quelle                  Titel, Stand, Berufsnummer, Dateinamen
lernorte[]              id (betrieb | schule), name
taxonomiestufen[]       stufe (1–6), kuerzel, begriff, beschreibung
arbeitsphasen[]         id, name, farbe (die fünf Phasen des «Graphic Design Workflow»)
bereiche[]              id (a–g), titel
  handlungskompetenzen[]  id (a1 …), titel, beschreibung, arbeitsphasen[], seite, broschuere?
    leistungsziele[]        id, nummer, lernort, text, stufe, seite, broschuere?
fachbegriffe[]          begriff, definition
berufsbildungsglossar[] begriff, definition
```

- `nummer` ist die Nummer aus dem Bildungsplan, etwa `b1.1`. Sie ist nicht eindeutig: Dieselbe Nummer steht oft an beiden Lernorten und bezeichnet dann die aufeinander abgestimmten Ziele.
- `id` ist eindeutig und setzt sich aus Nummer und Lernort zusammen, etwa `b1.1-betrieb` und `b1.1-schule`.
- `text` setzt den Satzanfang «Grafikerinnen und Grafiker …» fort und endet mit einem Punkt. Die K-Stufe steht nicht im Text, sondern in `stufe`.
- `seite` verweist auf die Seite im offiziellen Bildungsplan.
- `broschuere` enthält nur die Felder, in denen die Broschüre abweicht, etwa `{ "nummer": "c3.2" }`. Ohne Abweichung fehlt das Feld. `stufe` kann dort `null` sein: Die Broschüre führt g1.1 Betrieb ohne K-Stufe.
- `arbeitsphasen` einer Handlungskompetenz enthält die Ids ihrer Phasen. 31 Handlungskompetenzen gehören zu einer Phase; a4 gehört zu Vorbereitung, Entwicklung und Ausführung, g1 zu Vorbereitung, Entwicklung, Ausführung und Produktion.

## Arbeitsphasen

Die Broschüre kennzeichnet jede Seite mit einer Farbmarke am Seitenrand und nennt die Phase in derselben Farbe im Seitenkopf. Seiten mit der Bezeichnung «Mehrere Arbeitsphasen» tragen mehrere Marken. Die Farbwerte sind die RGB-Werte aus dem PDF.

| Phase | Farbe | Handlungskompetenzen |
| --- | --- | --- |
| Vorbereitung | `#ef3fa9` | a1, a2, a3, a4, b1, g1 |
| Entwicklung | `#009fe3` | a4, b2–b5, c1–c3, d1–d3, e1–e9, g1 |
| Ausführung | `#00ae41` | a4, f1–f4, g1 |
| Produktion | `#783cbd` | f5, g1 |
| Abschluss | `#fe5000` | a5, g2, g3 |
