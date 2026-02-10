# SwitchUserBundle - Kimai Plugin

## Übersicht

Plugin zur User-Impersonation in Kimai. Erlaubt Super-Admins, die Oberfläche
aus der Perspektive eines anderen Benutzers zu betrachten, ohne dessen Passwort
zu kennen. Nutzt Symfonys `SwitchUserListener`.

## Funktionen

- **Benutzer wechseln:** Menüeintrag im User-Dropdown (nur für ROLE_SUPER_ADMIN)
- **User-Auswahl:** Seite mit durchsuchbarer Liste aller Benutzer
- **Zurück-Link:** Menüeintrag zum Beenden der Impersonation (nur sichtbar wenn aktiv)

## UX-Flow

1. Super-Admin klickt Avatar → Dropdown zeigt "Benutzer wechseln"
2. Klick → Seite mit User-Liste (mit clientseitiger Suche)
3. Klick auf "Wechseln" → Redirect mit `?_switch_user=username`
4. Symfony übernimmt: Token wird gewechselt, Seite lädt als gewählter User
5. Im User-Menü erscheint "Zurück zu [Admin-Name]"
6. Klick → Redirect mit `?_switch_user=_exit` → zurück zum Admin

## Technische Details

### SwitchUserListener als Service

Symfonys `switch_user` Firewall-Feature lässt sich nicht via `PrependExtensionInterface`
aktivieren (Symfony verbietet neue Elemente unter `security.firewalls` aus Plugins).

Stattdessen wird der `SwitchUserListener` direkt als Service registriert.
Ein Wrapper-Listener (`SwitchUserRequestListener`) delegiert nur für Web-Requests
und blockiert `/api/`-Requests (Priorität 7, nach der Firewall bei 8):

- **Rolle:** Nur `ROLE_SUPER_ADMIN` kann wechseln
- **Parameter:** `_switch_user` (URL-Query-Parameter)
- **Exit:** `?_switch_user=_exit` beendet die Impersonation
- **SwitchUserToken:** Enthält Original-Token mit Original-User
- **UserProvider:** `chain_provider` (wie Kimai's Firewall)
- **UserChecker:** `App\Security\UserChecker`
- **API-Schutz:** `SwitchUserRequestListener` ignoriert `/api/`-Pfade

### Menü-Integration

`UserMenuSubscriber` (Priorität 50, nach Kimai's 100) auf `UserDetailsEvent`:
- Bei `SwitchUserToken`: zeigt "Zurück zu [Name]" Link (vorab übersetzt wegen `%name%`)
- Bei `ROLE_SUPER_ADMIN` (nicht impersoniert): zeigt "Benutzer wechseln" Link

### Controller

Route `/{_locale}/admin/switch-user` (geschützt via `ROLE_SUPER_ADMIN`):
- Lädt alle nicht-System-User via `UserRepository::getUsersForQuery()`
- Zeigt Tabelle mit Avatar, Username, Display-Name, Rollen, Wechseln-Button

### Übersetzungen

Translation Keys im `messages` Domain (DE + EN):

| Key | EN | DE |
|-----|----|----|
| `switch_user` | Switch User | Benutzer wechseln |
| `switch_user.back_to` | Back to %name% | Zurück zu %name% |
| `switch_user.search` | Search... | Suchen... |
| `switch_user.username` | Username | Benutzername |
| `switch_user.display_name` | Display name | Anzeigename |
| `switch_user.roles` | Roles | Rollen |
| `switch_user.switch` | Switch | Wechseln |

## Dateistruktur

```
SwitchUserBundle/
├── CLAUDE.md                              # Diese Datei
├── SwitchUserBundle.php                   # Bundle-Klasse
├── composer.json                          # Plugin-Metadata
├── DependencyInjection/
│   └── SwitchUserExtension.php            # Lädt services.yaml
├── Controller/
│   └── SwitchUserController.php           # User-Auswahl-Seite
├── EventSubscriber/
│   ├── SwitchUserRequestListener.php      # Wrapper: blockiert API, delegiert an SwitchUserListener
│   └── UserMenuSubscriber.php             # Menüeinträge im User-Dropdown
└── Resources/
    ├── config/
    │   ├── routes.yaml                    # Routen mit _locale Prefix
    │   └── services.yaml                  # Services + SwitchUserListener
    ├── translations/
    │   ├── messages.de.xlf                # Deutsche Übersetzungen
    │   └── messages.en.xlf                # Englische Übersetzungen
    └── views/
        └── switch.html.twig              # User-Auswahl-Template
```

## Voraussetzungen

- Kimai >= 2.32.00 (VERSION_ID: 23200)
- Keine weiteren Plugin-Abhängigkeiten
