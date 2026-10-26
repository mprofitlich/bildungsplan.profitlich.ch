# Einrichtung auf Cyon

Erstmalige Einrichtung von `bildungsplan.profitlich.ch` auf dem Webhosting von Cyon (ADR 0001). Danach veröffentlicht jeder Push auf `main` über die Action [veroeffentlichen.yml](../.github/workflows/veroeffentlichen.yml).

## Vorbereitung

- Zugang zu my.cyon und SSH auf das Webhosting.
- Workspace-Konto für den Mailversand mit App-Passwort (Google-Konto → Sicherheit → App-Passwörter; setzt Bestätigung in zwei Schritten voraus). Die Absenderadresse muss das Konto selbst oder einer seiner Aliasse sein, sonst setzt Gmail das Konto als Absender ein.
- In 1Password ein Eintrag für die Produktions-`.env` (Schlüssel siehe Schritt 4).

## Schritte

1. **Subdomain** `bildungsplan.profitlich.ch` in my.cyon anlegen, Dokumentstamm `bildungsplan/public` (relativ zum Home). SSL-Zertifikat (Let's Encrypt) für die Subdomain aktivieren.
2. **PHP 8.4** für das Verzeichnis `bildungsplan` im PHP-Versionsmanager setzen. Auf der Kommandozeile ebenso, in `~/.bashrc` gemäss Cyon-Anleitung; prüfen mit `ssh … 'php -v'` – die Action ruft `php` ohne Versionszusatz auf.
3. **Datenbank** (MariaDB) und Benutzer in my.cyon anlegen; Name, Benutzer und Passwort in 1Password.
4. **`.env`** unter `~/bildungsplan/.env` anlegen, aus `.env.example` mit diesen Abweichungen:

   ```ini
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://bildungsplan.profitlich.ch
   APP_KEY=            # einmalig: php artisan key:generate --show (lokal), Wert eintragen
   DB_HOST=localhost
   DB_DATABASE=…
   DB_USERNAME=…
   DB_PASSWORD=…
   LOG_LEVEL=warning
   MAIL_SCHEME=smtps
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=465
   MAIL_USERNAME=…     # Workspace-Konto
   MAIL_PASSWORD=…     # App-Passwort
   MAIL_FROM_ADDRESS=… # das Konto oder ein Alias davon
   ```

5. **SSH-Schlüssel für die Action** erzeugen (`ssh-keygen -t ed25519 -f bildungsplan-deploy -N ''`), den öffentlichen Teil in my.cyon unter SSH-Schlüssel hinterlegen.
6. **GitHub-Secrets** im Repo setzen (Settings → Secrets and variables → Actions):
   - `CYON_SSH_KEY`: privater Schlüssel aus Schritt 5
   - `CYON_KNOWN_HOSTS`: Ausgabe von `ssh-keyscan <host>`
   - `CYON_HOST`, `CYON_USER`: SSH-Host und -Benutzer
   - `CYON_PFAD`: absoluter Pfad, etwa `/home/<benutzer>/bildungsplan`
7. **Erste Veröffentlichung** über Actions → Veröffentlichen → «Run workflow» starten. Die Action überträgt Code und `vendor/`, legt `storage/` an und führt die Migrationen aus; die Migration trägt `sluz.ch` als erlaubte Domain ein.
8. **GitHub Pages abschalten** (Settings → Pages), damit die alte Fassung nicht weiter erreichbar ist.

## Abnahme

- `https://bildungsplan.profitlich.ch` lädt, ohne Login mit leerem Kurs; `https://bildungsplan.profitlich.ch/up` antwortet mit 200.
- Login-Link für eine eigene `sluz.ch`-Adresse anfordern: Mail kommt an, der Link öffnet die Seite mit «Einloggen», danach steht die Adresse oben rechts.
- Eine Adresse ausserhalb von `sluz.ch` wird abgelehnt.
- `~/bildungsplan/storage/logs/` enthält keine Fehler aus dem Test.

## Rollback

- Code: die Action mit dem letzten guten Commit erneut starten («Run workflow» auf einem Tag oder per Revert-Commit auf `main`).
- Datenbank: Vor einer Veröffentlichung mit neuen Migrationen einen Dump ziehen (`mysqldump` per SSH oder Backup in my.cyon); zurück mit dem Dump, nicht mit `migrate:rollback`.
- Die erste Einrichtung zurücknehmen: Subdomain löschen, GitHub Pages wieder einschalten.
