# SwitchUserBundle

User impersonation plugin for Kimai. Allows Super-Admins to view the interface
from another user's perspective without knowing their password.

## Features

- **Switch User** menu entry in the user dropdown (Super-Admin only)
- **User selection page** with searchable list of all users
- **Back link** to exit impersonation and return to the original admin account
- **API protection** - the `_switch_user` parameter is ignored on `/api/` routes
- **Translations** for German and English

## Requirements

- Kimai >= 2.32.0 (VERSION_ID: 23200)
- No additional plugin dependencies

## Installation

```bash
cd /path/to/kimai/var/plugins/
git clone https://github.com/ralf1070/SwitchUserBundle.git
```

Clear the cache:

```bash
bin/console cache:clear
```

## Usage

1. Log in as Super-Admin
2. Click your avatar in the top right corner
3. Select **"Switch User"** from the dropdown menu
4. Choose a user from the list
5. Kimai now shows the interface as the selected user
6. To return: click your avatar and select **"Back to [Your Name]"**

## How It Works

Symfony's built-in `switch_user` firewall feature cannot be activated from a
plugin via `PrependExtensionInterface` (Symfony forbids adding new elements
under `security.firewalls` from bundles).

Instead, the plugin registers Symfony's `SwitchUserListener` directly as a
service, wrapped by a `SwitchUserRequestListener` that:

- Delegates to the inner listener for web requests only
- Blocks `/api/` requests (the `_switch_user` parameter is silently ignored)
- Runs at priority 7 (after the firewall at priority 8)

Only users with `ROLE_SUPER_ADMIN` can switch. The URL parameter
`_switch_user=<username>` triggers the switch, `_switch_user=_exit` ends it.

## Testing

Requires the `feature/plugin-test-support` branch on the Kimai fork
(or the `integration` branch which includes it).

```bash
# Via test.sh (recommended)
./test.sh --init                                        # First time: setup test DB
./test.sh --plugin -- --plugin SwitchUserBundle         # Run SwitchUserBundle tests

# Via composer (inside container)
composer tests-plugins -- --plugin SwitchUserBundle
```

The test suite covers:
- Page access control (login required, denied for non-super-admins)
- Page content (user list, current user excluded)
- Impersonation workflow (switch + exit)
- Role-based denial (admin, teamlead, user cannot switch)
- API protection (`_switch_user` parameter ignored on API routes)

## License

MIT
