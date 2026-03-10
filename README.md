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

## Installation

### 1. Clone the main Synaplan app

```bash
git clone https://github.com/metadist/synaplan.git
cd synaplan
docker compose up -d
```

See the [Synaplan README](https://github.com/metadist/synaplan) for full setup instructions (default credentials, services, etc.).

### 2. Clone this repository alongside it

```bash
cd ..
git clone https://github.com/metadist/synaplan-marketing.git
```

Your directory layout should look like:

```
your-workspace/
├── synaplan/                 # Main application
└── synaplan-marketing/       # This repo
```

### 3. Install the plugin

Copy the plugin into Synaplan's plugin directory:

```bash
cp -r synaplan-marketing/marketeer-plugin synaplan/plugins/marketeer
```

Then activate it inside the running Synaplan container:

```bash
cd synaplan
docker compose exec backend composer dump-autoload
docker compose exec backend php bin/console cache:clear
docker compose exec backend php bin/console app:plugin:install 1 marketeer
```

Replace `1` with your user ID if different.

### 4. Access the plugin

Open Synaplan in your browser and navigate to **Plugins > Marketeer** in the sidebar, or go directly to:

```
http://localhost:5173/plugins/marketeer
```

## Updating the Plugin

After pulling new changes from this repository:

```bash
rm -rf synaplan/plugins/marketeer
cp -r synaplan-marketing/marketeer-plugin synaplan/plugins/marketeer
cd synaplan
docker compose exec backend php bin/console cache:clear
```

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
