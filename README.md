# Bildungsplan Grafikerin/Grafiker EFZ

Werkzeug zur Kursplanung: Leistungsziele aus dem Bildungsplan finden und zu Kursen zusammenstellen.

<!-- hilfe:start -->

## Grundidee

Dieses Tool hilft dir, die zu deinem Kurs passenden Leistungsziele zusammenzustellen – durch eine filterbare Liste, eine Suche und durch eine Übersicht. Du kannst beliebig viele Kurse anlegen. Ohne Login werden sie in deinem Browser gespeichert und bleiben, bis du den Browsercache löschst (du kannst sie auch herunterladen unter Meine Kurse). Mit einer Schuladresse kannst du dich einloggen; dann liegen die Kurse auf dem Server, lassen sich freigeben und im Curriculum zeigen. Der Button ‹Hinzufügen› rechts in der Kompetenzenliste fügt ein Ziel zu deinem aktuellen Kurs hinzu. Zwischen den Kursen wechselst Du im Reiter ‹Meine Kurse›, dort kann du auch neue anlegen.

## Kompetenzen

Alle Handlungskompetenzen mit ihren Leistungszielen.

- **Version**: Texte im Wortlaut des Graphic Design Workflow (Vorgabe) oder des Bildungsplans (SGD/SGV). Gilt für alle Ansichten.
- **Suche**: Stichwort oder Nummer, etwa `Moodboard` oder `b1.1`. Mehrere Wörter müssen alle vorkommen. Durchsucht Titel, Beschreibungen und Ziele; Treffer sind gelb markiert. Beim Scrollen bleibt das Feld oben stehen.
- **Ziele zeigen**: alle Ziele einer gefundenen Kompetenz oder nur die, in denen die Suche trifft.
- **Filter**: Lernort, Taxonomiestufe, Handlungskompetenzbereich und Arbeitsphase. Mehrere Werte in einem Filter erweitern die Auswahl, mehrere Filter engen sie ein. «Filter zurücksetzen» hebt alle auf.
- **Fachbegriffe**: Punktiert unterstrichene Begriffe erklären sich beim Überfahren. Sucht man nach einem Fachbegriff, steht seine Erklärung über der Liste.
- **Hinzufügen / Entfernen**: legt ein Leistungsziel in den aktiven Kurs oder nimmt es heraus.
- **Gegenstück**: zeigt das entsprechende Ziel des anderen Lernorts.

## Übersicht

Alle Handlungskompetenzen auf einen Blick, geordnet nach Bereichen. Die Farbmarken zeigen die Arbeitsphasen. Ein Klick öffnet die Kompetenz unter «Kompetenzen».

## Glossar

Begriffe aus dem Bildungsplan mit ihrer Erklärung.

- **Fachbegriffe**: dieselben Begriffe, die in den Texten punktiert unterstrichen sind. Die Zahl nennt die Leistungsziele, die den Begriff enthalten; ein Klick sucht ihn unter «Kompetenzen».
- **Taxonomiestufen**: K1 bis K6. Der Filter «Taxonomiestufe» unter «Kompetenzen» verlinkt hierher.
- **Berufsbildung**: Begriffe der Berufsbildung wie Lerndokumentation, Qualifikationsverfahren oder überbetriebliche Kurse.

## Meine Kurse

- **Liste**: alle Kurse mit der Zahl ihrer Leistungsziele je Lernort und Taxonomiestufe. Ein Klick öffnet den Kurs. Freigegebene Kurse nennen, wem sie gehören.
- **Neuer Kurs**: legt einen leeren Kurs an und öffnet ihn.
- **Einloggen**: Adresse eingeben, den Link aus der Mail öffnen und «Einloggen» klicken. Der Link gilt eine Viertelstunde und nur einmal. Beim ersten Login kommen die Kurse dieses Browsers mit. «Abmelden» oben rechts.
- **Sicherung**: «Sicherung herunterladen» speichert alle Kurse in eine Datei. Ohne Login ersetzt «Sicherung einlesen» alle Kurse durch deren Inhalt, mit Login fügt es die Kurse der Datei hinzu.

## Kurs

Der Reiter rechts zeigt den aktiven Kurs und die Zahl seiner Leistungsziele.

- **Titel und Beschrieb**: werden laufend gespeichert.
- **Beurteilungskriterien**: je ein Text zu Entwicklung/Entwurf, Gestaltungskonzept, Ausführung/Umsetzung und Vermittlung; leere Kriterien erscheinen im Curriculum nicht.
- **Freigabe**: nur mit Login und nur bei eigenen Kursen. «Im Curriculum zeigen» macht den Kurs für alle Eingeloggten derselben Schule sichtbar. Darunter lässt er sich an Adressen derselben Schule zum Mitbearbeiten freigeben; bekannte Adressen werden vorgeschlagen, und die Person bekommt eine Mail.
- **Gleichzeitig bearbeiten**: Wer einen Kurs gerade ändert, hält ihn; alle anderen sehen ihn so lange nur zum Lesen. Die Sperre löst sich, sobald die Person den Kurs wechselt, den Tab verlässt oder fünf Minuten nichts tut.
- **Taxonomiestufen**: Verteilung der gewählten Ziele auf K1 bis K6.
- **Im Bildungsplan**: welche Handlungskompetenzen der Kurs wie stark abdeckt.
- **Kurs löschen**: entfernt den Kurs nach einer Rückfrage. Freigegebene Kurse löschen kann nur, wem sie gehören.

## Curriculum

Nur mit Login. Alle Kurse der eigenen Schule mit «Im Curriculum zeigen».

- **Im Bildungsplan**: die Leistungsziele aller Kurse je Handlungskompetenz zusammengezählt; ein Ziel in drei Kursen zählt dreimal.
- **Kurse**: Ein Klick öffnet den Kurs zum Ansehen.

## Neue Funktionen

Neue Funktionen meldet ein Fenster beim nächsten Besuch. Nach dem Schliessen erscheint es erst bei der nächsten Neuerung wieder.

<!-- hilfe:end -->

## Entwicklung

Laravel liefert die Anwendung und ihre API aus; das Frontend ist Svelte. Alles läuft in ddev, auch npm.

```sh
ddev start
ddev composer install
ddev npm install
ddev artisan migrate
ddev npm run dev                 # Vite mit Neuladen; ohne läuft der letzte Build
ddev launch                      # https://bildungsplan.profitlich.ch.ddev.site
ddev launch -m                   # Mailpit, dort landen die Login-Links
ddev exec php artisan test --compact
ddev npm test
ddev npm run build
ddev exec vendor/bin/pint --dirty
```

Wer sich einloggen darf, steht in der Tabelle `domains` (vorerst `sluz.ch`).

Die `.env` schreibt `ddev start` jedes Mal neu aus dem 1Password-Environment des Projekts (pre-start-Hook in `.ddev/config.yaml`, Skript `.ddev/env-from-1password.sh`). Eine Änderung von Hand hält deshalb nur bis zum nächsten Start; sie gehört nach 1Password. Ist 1Password nicht erreichbar, startet ddev mit der bisherigen `.env`. `.env.example` zeigt die nötigen Schlüssel.

Weil die Adresse Punkte enthält, löst das öffentliche DNS von `ddev.site` sie nicht auf. Der erste `ddev start` trägt sie deshalb in `/etc/hosts` ein und fragt dafür nach dem Admin-Passwort; aus einem Terminal starten, nicht aus einem Werkzeug ohne Eingabe.

Der Teil zwischen `hilfe:start` und `hilfe:end` erscheint in der Anwendung unter «Hilfe» und unterstützt Überschriften, Absätze, Listen, **fett**, `Code` und Links.

Weitere Unterlagen: [Datenbasis](docs/datenbasis.md), [Abweichungen](docs/abweichungen.md), [Entscheide](docs/adr/), [Einrichtung auf Cyon](docs/deploy-cyon.md).
