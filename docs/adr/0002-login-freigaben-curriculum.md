# 0002 — Login, Freigaben und Curriculum

**Status:** angenommen, 2026-10-26

## Kontext

Mit dem Backend aus [0001](0001-backend-laravel-auf-cyon.md) bekommen Kurse Besitzerinnen und Besitzer. Zu klären war, wer sich einloggen darf, was eine Freigabe erlaubt, wer das Curriculum sieht und was mit den Kursen geschieht, die bisher nur im Browser lagen.

## Entscheidung

**Login**

- Einloggen erfolgt per Magic Link: Mailadresse eingeben, Link aus der Mail öffnen. Kein Passwort.
- Einloggen darf nur, wessen Adresse auf eine **erlaubte Domain** endet. Die Liste steht in der Datenbank, vorerst nur `sluz.ch`. Abgleich exakt, ohne Subdomains.
- **Ohne Login bleibt die Anwendung nutzbar** wie bisher, mit Kursen nur im Browser.
- Beim Login werden die Kurse aus dem localStorage in die Datenbank übernommen. Massgeblich ist die Kurs-Id, damit ein zweites Gerät keine Duplikate erzeugt. Danach ist die Datenbank die Quelle; welcher Kurs gerade aktiv ist, merkt sich jedes Gerät selbst.

**Freigaben**

- Ein Kurs lässt sich an einzelne Mailadressen **derselben Domain** freigeben. Die Freigabe erlaubt das Mitbearbeiten.
- Die Freigabe gilt der Mailadresse, auch wenn deren Person sich noch nie eingeloggt hat. Beim ersten Login steht der Kurs unter «Meine Kurse».
- Die Empfängerin oder der Empfänger bekommt eine Mail.
- Die Autovervollständigung schlägt nur bekannte Personen der eigenen Domain vor.
- **Nur die Besitzerin oder der Besitzer** darf den Kurs löschen, weiter freigeben und «Im Curriculum zeigen» setzen.

**Curriculum**

- Ein eigener Reiter zeigt alle Kurse mit «Im Curriculum zeigen», nur für Eingeloggte und nur die Kurse der eigenen Domain.
- Kurse öffnen sich dort schreibgeschützt.
- Zuoberst steht eine Tabelle wie «Im Bildungsplan», summiert über alle Kurse des Curriculums. Ein Leistungsziel in drei Kursen zählt dreimal.

## Begründung

- **Login als Pflicht** verworfen: Alle ohne Adresse einer erlaubten Domain wären ausgesperrt, obwohl das Werkzeug ohne Server voll funktioniert.
- **Freigabe nur zum Lesen** verworfen: Gemeinsames Planen ist der Zweck. Für reines Ansehen gibt es das Curriculum.
- **Freigabe über Domaingrenzen** verworfen: Das Curriculum ist pro Domain gedacht; Kurse sollen nicht unbemerkt in eine andere Schule wandern.
- **Mehrfachabdeckung einmal zählen** verworfen: Die Tabelle setzt «Im Bildungsplan» fort, das ebenfalls Ziele zählt; die Dichte zeigt, wo Schwerpunkte liegen.

## Konsequenzen

- Die Autovervollständigung zeigt jeder eingeloggten Person die Adressen aller Kolleginnen und Kollegen derselben Domain, die das Werkzeug nutzen.
- Eine mehrfach behandelte Kompetenz sieht in der Summentabelle gut abgedeckt aus, auch wenn daneben Lücken bestehen.
- Zwei Speicherwege – Browser ohne Login, Datenbank mit Login – bedeuten mehr Code und mehr Testfälle.
