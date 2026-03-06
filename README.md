# Synaplan Marketing

Marketing assets and the Marketeer plugin for [Synaplan](https://github.com/metadist/synaplan) by metadist data management GmbH.

## Contents

| Directory | Purpose |
|-----------|---------|
| `landing/` | Landing page campaigns (HTML, keywords, images per language) |
| `marketeer-plugin/` | **Marketeer Synaplan plugin** -- AI-powered landing page generator (source of truth) |
| `planning/` | Strategy and architecture documents |
| `jobs/` | Job posting pages and social media assets |

## Marketeer Plugin

The Marketeer plugin generates landing pages, keyword lists, and social images via AI prompts. It follows the non-invasive Synaplan plugin architecture (same pattern as SortX and BroGent).

- **Plugin source**: `marketeer-plugin/` (edit here, sync to `synaplan/plugins/marketeer/`)
- **API routes**: `/api/v1/user/{userId}/plugins/marketeer/...`
- **Data storage**: `plugin_data` table + user upload directory

### Sync to Synaplan

```bash
rm -rf /wwwroot/synaplan/plugins/marketeer && \
cp -r /wwwroot/synaplan-marketing/marketeer-plugin /wwwroot/synaplan/plugins/marketeer
```

Then clear cache and install:

```bash
docker compose exec -T backend composer dump-autoload
docker compose exec -T backend php bin/console cache:clear
docker compose exec backend php bin/console app:plugin:install 1 marketeer
```

**Note**: The sync step will no longer require manual config edits in synaplan once the auto-discovery system is implemented (see `planning/plugin-architecture.md`).

## Planning Documents

| Document | Description |
|----------|-------------|
| `planning/landing-pages.md` | Full strategy, phased development plan, campaign status |
| `planning/plugin-architecture.md` | Auto-discovery system, 3-market model, synaplan core changes |
| `planning/automation.md` | Content generation and ad automation strategy |

## Landing Page Campaigns

Each campaign lives in `landing/{campaign-slug}/` with:
- `plan.md` -- Campaign topic, audience, messaging
- `{lang}/index.html` -- The landing page
- `{lang}/keywords.txt` -- Google Ads keywords

See `landing/AGENTS.md` for the full campaign structure guidelines.

## Related Repositories

| Repository | Description |
|---|---|
| [synaplan](https://github.com/metadist/synaplan) | Main application source code |
| [synaplan-platform](https://github.com/metadist/synaplan-platform) | Production deployment configs |
| [synaplan-sortx](https://github.com/metadist/synaplan-sortx) | Document sorting plugin |
| [synaplan-extension](https://github.com/metadist/synaplan-extension) | BroGent browser agent plugin |
| [synaplan-tts](https://github.com/metadist/synaplan-tts) | Text-to-Speech service |
| [synaplan-memories](https://github.com/metadist/synaplan-memories) | AI memory service (Qdrant) |
