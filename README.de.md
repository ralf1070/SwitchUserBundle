# SwitchUserBundle

User-Impersonation-Plugin für Kimai. Erlaubt Super-Admins, die Oberfläche
aus der Perspektive eines anderen Benutzers zu betrachten, ohne dessen
Passwort zu kennen.

## Funktionen

- **Benutzer wechseln** Menüeintrag im User-Dropdown (nur für Super-Admins)
- **Benutzer-Auswahl** mit durchsuchbarer Liste aller Benutzer
- **Zurück-Link** zum Beenden der Impersonation und Rückkehr zum Admin-Account
- **API-Schutz** - der `_switch_user`-Parameter wird auf `/api/`-Routen ignoriert
- **Übersetzungen** für Deutsch und Englisch

## Voraussetzungen

- Kimai >= 2.32.0 (VERSION_ID: 23200)
- Keine weiteren Plugin-Abhängigkeiten

## Installation

```bash
cd /path/to/kimai/var/plugins/
git clone https://github.com/ralf1070/SwitchUserBundle.git
```

Cache leeren:

```bash
bin/console cache:clear
```

## Bedienung

1. Als Super-Admin einloggen
2. Avatar oben rechts anklicken
3. **"Benutzer wechseln"** im Dropdown wählen
4. Benutzer aus der Liste auswählen
5. Kimai zeigt nun die Oberfläche als ausgewählter Benutzer
6. Zum Zurückkehren: Avatar klicken und **"Zurück zu [Ihr Name]"** wählen

## Funktionsweise

Symfonys eingebautes `switch_user` Firewall-Feature lässt sich nicht per
`PrependExtensionInterface` aus einem Plugin heraus aktivieren (Symfony
verbietet neue Elemente unter `security.firewalls` aus Bundles).

Stattdessen registriert das Plugin Symfonys `SwitchUserListener` direkt als
Service, umhüllt von einem `SwitchUserRequestListener`, der:

- Nur bei Web-Requests an den inneren Listener delegiert
- `/api/`-Requests blockiert (der `_switch_user`-Parameter wird ignoriert)
- Mit Priorität 7 läuft (nach der Firewall bei Priorität 8)

Nur Benutzer mit `ROLE_SUPER_ADMIN` können wechseln. Der URL-Parameter
`_switch_user=<username>` löst den Wechsel aus, `_switch_user=_exit` beendet ihn.

## Tests

Benötigt den `feature/plugin-test-support` Branch auf dem Kimai-Fork
(oder den `integration` Branch, der diesen enthält).

```bash
# Via test.sh (empfohlen)
./test.sh --init                                        # Erstmalig: Test-DB einrichten
./test.sh --plugin -- --plugin SwitchUserBundle         # SwitchUserBundle Tests ausführen

# Via Composer (im Container)
composer tests-plugins -- --plugin SwitchUserBundle
```

Die Testsuite prüft:
- Zugriffsschutz (Login erforderlich, Zugriff für Nicht-Super-Admins verweigert)
- Seiteninhalt (Benutzerliste, aktueller Benutzer ausgeschlossen)
- Impersonation-Workflow (Wechsel + Rückkehr)
- Rollenbasierte Verweigerung (Admin, Teamlead, User können nicht wechseln)
- API-Schutz (`_switch_user`-Parameter wird auf API-Routen ignoriert)

## Lizenz

MIT
