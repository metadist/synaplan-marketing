# Synaplan Marketeer Plugin

AI-powered marketing campaign generator for [Synaplan](https://github.com/metadist/synaplan). Creates landing pages, Google Ads campaigns, ad copy, social media graphics, and videos — all from a single campaign brief.

## Features

- **Landing Pages** — AI-generated, multilingual HTML pages with SEO metadata
- **Google Ads** — Campaign plans with keywords, negative keywords, and ad groups
- **Ad Copy** — Platform-specific copy for Google, LinkedIn, Facebook, Instagram
- **Media Generation** — Social graphics (LinkedIn, Instagram) and video via Google Veo
- **Compliance** — Cookie consent, privacy policy, and imprint link enforcement
- **ZIP Export** — Download all campaign assets including media, keywords, and ad data

## Repository Structure

```
synaplan-marketing/
├── marketeer-plugin/     # Plugin source code (install into Synaplan)
│   ├── backend/          #   Symfony controllers & services
│   ├── frontend/         #   Plugin UI (vanilla JS)
│   ├── migrations/       #   Database setup
│   └── manifest.json     #   Plugin metadata
├── _devextras/           # Development extras (planning docs, test scripts, examples)
├── LICENSE               # Apache 2.0
└── README.md
```

## Prerequisites

- A running [Synaplan](https://github.com/metadist/synaplan) instance (see its README for setup)
- Docker Compose

## Installation & Activation

Synaplan uses **automatic plugin discovery** — no config file edits are needed. Drop the plugin into the `plugins/` directory, clear the cache, and run the install command.

### 1. Clone both repositories

```bash
git clone https://github.com/metadist/synaplan.git
git clone https://github.com/metadist/synaplan-marketing.git
```

Your directory layout should look like:

```
your-workspace/
├── synaplan/                 # Main application
└── synaplan-marketing/       # This repo
```

### 2. Copy the plugin into Synaplan

```bash
cp -r synaplan-marketing/marketeer-plugin synaplan/plugins/marketeer
```

Synaplan's Kernel scans `plugins/*/manifest.json` on boot to auto-register services and routes. No changes to `composer.json`, `routes.yaml`, or `services.yaml` are required.

### 3. Start Synaplan (if not already running)

```bash
cd synaplan
docker compose up -d
```

### 4. Clear cache and activate

```bash
docker compose exec backend php bin/console cache:clear
docker compose exec backend php bin/console app:plugin:install <userId> marketeer
```

Replace `<userId>` with the target user's ID (typically `1` for the first admin user). This command:

- Runs the migration in `migrations/001_setup.sql` which writes default settings to `BCONFIG` with group `P_marketeer`
- Calls the `MarketeerInstallService::seedDefaults` hook to create a sample campaign
- Enables the plugin for that user (`P_marketeer.enabled = 1`)

### 5. Access the plugin

Open Synaplan in your browser and navigate to **Plugins > Marketeer** in the sidebar, or go directly to:

```
http://localhost:5173/plugins/marketeer
```

### Activate for all existing users (production)

To install the plugin for every active, verified user at once:

```bash
docker compose exec backend php bin/console app:plugin:install-verified-users marketeer
```

### Auto-activate for new users

To have the plugin automatically installed when new users register, set the `DEFAULT_USER_PLUGINS` environment variable in the backend `.env`:

```env
DEFAULT_USER_PLUGINS='["marketeer"]'
```

This is a JSON array — add alongside any other default plugins.

### Verify activation

Check that the plugin is responding:

```bash
curl -s -H "X-API-Key: <your-api-key>" \
  http://localhost:8000/api/v1/user/<userId>/plugins/marketeer/setup-check
```

A successful response returns `{ "success": true, ... }` with the plugin's configuration status.

## Updating the Plugin

After pulling new changes from this repository:

```bash
rm -rf synaplan/plugins/marketeer
cp -r synaplan-marketing/marketeer-plugin synaplan/plugins/marketeer
cd synaplan
docker compose exec backend php bin/console cache:clear
```

No re-install is needed — the install command only needs to run once per user.

## How Auto-Discovery Works

Synaplan's plugin system requires zero core code changes to add a new plugin:

1. **Boot** — `Kernel::getPlugins()` scans `/plugins/*/manifest.json` for plugin metadata
2. **Autoloading** — PSR-4 mapping is registered: `Plugin\Marketeer\*` resolves to `plugins/marketeer/backend/*`
3. **Services** — All classes under `backend/` are autowired into Symfony's DI container
4. **Routes** — Controller attributes in `backend/Controller/` are auto-imported
5. **Security** — The public page endpoint (`/api/v1/marketeer/public/*`) is already configured in Synaplan's `security.yaml` for anonymous access
6. **Per-user gate** — Every API call checks `BCONFIG P_marketeer.enabled = 1` for the requesting user

## Configuration

The plugin stores its settings per-user in the Synaplan database. Configure these in the plugin's **Settings** tab:

| Setting | Description |
|---------|-------------|
| **Brand name** | Your company/product name |
| **Brand description** | Short description for AI context |
| **Logo URL** | Logo for landing pages |
| **Accent color** | Brand color for generated pages |
| **CTA target URL** | Where the call-to-action links to |
| **Privacy policy URL** | Required for compliance |
| **Imprint URL** | Required for compliance |

## API Routes

All endpoints are under `/api/v1/user/{userId}/plugins/marketeer/`:

| Method | Path | Description |
|--------|------|-------------|
| GET | `/setup-check` | Check plugin installation status |
| POST | `/setup` | Initialize plugin with defaults |
| GET | `/config` | Get plugin configuration |
| PUT | `/config` | Update plugin configuration |
| GET | `/dashboard` | Campaign overview |
| POST | `/campaigns` | Create a new campaign |
| GET | `/campaigns` | List all campaigns |
| GET | `/campaigns/{id}` | Get campaign details |
| PUT | `/campaigns/{id}` | Update campaign |
| DELETE | `/campaigns/{id}` | Delete campaign |
| POST | `/campaigns/{id}/generate` | Generate landing page |
| POST | `/campaigns/{id}/generate-media` | Generate social graphics/video |
| GET | `/campaigns/{id}/download` | Download campaign ZIP |

## Related Repositories

| Repository | Description |
|---|---|
| [synaplan](https://github.com/metadist/synaplan) | Main application |
| [synaplan-platform](https://github.com/metadist/synaplan-platform) | Production deployment | *private*
| [synaplan-sortx](https://github.com/metadist/synaplan-sortx) | Document sorting plugin | *private* 
| [synaplan-extension](https://github.com/metadist/synaplan-extension) | BroGent browser agent plugin | *private, coming soon*
| [synaplan-memories](https://github.com/metadist/synaplan-memories) | AI memory service |

## License

[Apache-2.0](LICENSE)
