# 0001 — Backend mit Laravel und MariaDB auf Cyon

**Status:** angenommen, 2026-10-26

## Kontext

Die Anwendung war bis hier rein statisch: Svelte und Vite, ausgeliefert über GitHub Pages, alle Kurse nur im localStorage des Browsers. Neu sollen sich Lehrpersonen einloggen, ihre Kurse serverseitig speichern, an Kolleginnen und Kollegen freigeben und in einem gemeinsamen Curriculum zeigen. Dafür braucht es Login, Datenbank und Mailversand.

Die Daten betreffen Lehrpersonen mit Adressen einer Schuldomain (vorerst `sluz.ch`) und sollen in der Schweiz liegen. Das Webhosting von Cyon liefert PHP-Anwendungen aus (PHP 8.1 bis 8.4), aber keine Node-Server.

## Entscheidung

- Das Backend ist eine **Laravel**-Anwendung mit **MariaDB** auf dem Webhosting von **Cyon**, erreichbar unter `bildungsplan.profitlich.ch`.
- Das **Frontend zieht mit um**: Laravel liefert die gebaute Svelte-Anwendung und die API von derselben Domain aus. GitHub Pages entfällt; die GitHub Action veröffentlicht per SSH auf Cyon.
- Die Sitzung läuft über ein Session-Cookie von Laravel (gleiche Domain, `SameSite=Lax`, `HttpOnly`).
- Mails verschickt Laravel über das SMTP eines Google-Workspace-Kontos; die Zugangsdaten stehen in der `.env`.
- Die lokale Entwicklung läuft **vollständig in ddev**, auch Composer und npm.
- Die **KI-Vorschlagsfunktion entfällt**.

## Begründung

- **Supabase** (Auth, Postgres, Zeilenrechte eingebaut, Frontend hätte auf Pages bleiben können) verworfen: Die Daten lägen bei einem US-Anbieter ausserhalb der Schweiz.
- **Frontend auf Pages, nur die API auf Cyon** verworfen: Das Session-Cookie wäre ein Drittanbieter-Cookie, das Safari blockiert. Ausweichen hiesse ein Token im localStorage, das jedes eingeschleuste Skript lesen kann.
- **Schlankes PHP** (PDO, PHPMailer, etwa zehn Endpunkte) verworfen: Login-Token, Sitzung, CSRF-Schutz und Drosselung des Mailversands müssten selbst geschrieben werden. Laravel bringt sie geprüft mit.
- **ddev für alles** statt nur für PHP und Datenbank: wie in den übrigen Projekten, eine Arbeitsweise statt einer Ausnahme.
- Die Vorschlagsfunktion lief nur auf dem lokalen Entwicklungsserver und rief dort die Claude-Kommandozeile des Rechners auf. In der veröffentlichten Fassung gab es sie nie; im ddev-Container liefe sie auch lokal nicht mehr.

## Konsequenzen

- Laravel ist für den kleinen Umfang schwer und verlangt regelmässige Updates des Frameworks und seiner Abhängigkeiten.
- Die Veröffentlichung braucht einen SSH-Schlüssel als GitHub-Secret; Pages fällt als kostenloser Fallback weg.
- Gmail Workspace begrenzt den Versand auf rund 2000 Mails pro Tag; das reicht für Login-Links und Freigabe-Mails, nicht für Massenversand.
- Die `.env` mit den SMTP-Daten wird aus 1Password erzeugt; jede neue Variable muss dort nachgezogen werden.
