# Landing Page Strategy & Development Plan

**Goal**: Build an AI-powered marketing campaign generator ("Marketeer") that creates landing pages, keyword lists, and social images -- first for Synaplan's own marketing, then as a sellable Synaplan plugin.

## Architectural Decision: The "Marketeer" Plugin

We are building a fully integrated **Synaplan Plugin** (non-invasive architecture, same pattern as `synaplan-sortx` and `synaplan-extension/brogent`). The plugin will be rolled out to `synaplan-platform` and offered as a premium feature.

**Source of truth**: `synaplan-marketing/marketeer-plugin/` (already created).

**Key Pillars:**

1. **Web Interface**: Configure campaigns, define topics, modify content with prompts.
2. **API Integration**: Uses the live Synaplan API on `web.synaplan.com`. Users store their API key in plugin config.
3. **Storage & Hosting**: Generated assets (HTML, images, keyword files) saved to the user's public upload directory on the platform (mounted NFS storage).
4. **Multi-Language**: Each campaign has per-language subdirectories (`/en/`, `/de/`, etc.).
5. **Export**: Download complete landing page packages as ZIP files.

**Important**: Before this plugin can be deployed, the Synaplan core needs a **plugin auto-discovery system** so that plugins can be added without editing core config files. See `planning/plugin-architecture.md` for the full plan.

---

## Step-by-Step Development Plan

### Phase 1: MVP Release -- Foundation & Generation

*Focus: Manual proof-of-concept, core plugin infrastructure, basic AI generation.*

1. **Manual Prototyping** (in this workspace)
   - Create 3-4 landing pages manually using prompts in Cursor.
   - Perfect the HTML template, prompt structure, and output quality.
   - Campaigns: `any-model` (en, de), `rag-pipeline` (en), `local-ai` (en).

2. **Plugin Auto-Discovery** (in `synaplan/`)
   - Implement the auto-discovery system in `Kernel.php` (see `planning/plugin-architecture.md`).
   - Create `plugin-autoloader.php` for dynamic class loading.
   - Remove all per-plugin config entries from routes.yaml, services.yaml, composer.json.
   - Add per-user access gating (`isPluginInstalled()` check) to all plugin controllers.

3. **Deploy Marketeer Plugin** (from `marketeer-plugin/` to `synaplan/plugins/`)
   - Copy plugin, restart container, install for admin user.
   - Plugin skeleton is already built with full CRUD, generation, and ZIP endpoints.

4. **MVP API Endpoints** (already implemented in `marketeer-plugin/backend/Controller/MarketeerController.php`):
   - `GET/PUT /config` -- Plugin configuration.
   - `GET/POST /campaigns` -- Campaign CRUD.
   - `POST /campaigns/{id}/generate` -- Generate landing page HTML via AI.
   - `POST /campaigns/{id}/generate-keywords` -- Generate Google Ads keyword lists.
   - `POST /campaigns/{id}/generate-image` -- Generate social images (hero, LinkedIn, Instagram, OG).
   - `POST /campaigns/{id}/refine` -- Iterate on content with follow-up prompts.
   - `GET /campaigns/{id}/files` -- List generated files.
   - `GET /campaigns/{id}/download` -- Download campaign as ZIP.

### Phase 2: Release 2 -- Publishing & Distribution

*Focus: Pushing generated content to external channels.*

1. **"Add Publication" Workflows**
   - UI and logic to distribute generated content to external platforms.

2. **LinkedIn Integration**
   - Share landing page as a LinkedIn post with generated social image and text.
   - Use LinkedIn API or manual export with pre-formatted content.

3. **Instagram Integration**
   - Upload generated images to Instagram with landing page URL.
   - Pre-formatted captions with hashtags.

4. **Google Ads Management** (most complex)
   - Integrate with Google Ads API.
   - Sync generated keyword lists to ad groups.
   - Create responsive search ads pointing to hosted landing pages.
   - Basic campaign budget and bidding configuration.

### Phase 3: Final Release -- Advanced Media & Video

*Focus: Rich media and video platforms.*

1. **Short Video Integration**
   - Incorporate video content into the marketing workflow.

2. **Screenmovie Feature**
   - Integrate the existing screenmovie option already in Synaplan.
   - Record feature demos, product walkthroughs.

3. **TikTok Publishing**
   - Record/generate a video of a feature and publish to TikTok.
   - Drive traffic to landing pages via video description links.

---

## Campaign Status (Manual Prototyping Phase)

| Campaign | Language | Status | Plan | Keywords | Page |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `any-model` | `en` | **Ready** | [View](../landing/any-model/plan.md) | [View](../landing/any-model/en/keywords.txt) | [View](../landing/any-model/en/index.html) |
| `any-model` | `de` | *Planned* | - | - | - |
| `rag-pipeline` | `en` | *Planned* | [View](../landing/rag-pipeline/plan.md) | - | - |
| `local-ai` | `en` | *Planned* | - | - | - |

## Plugin Data Model

**Config** (BCONFIG table, group `P_marketeer`):
- `enabled` -- Plugin enabled for this user (set by install migration)
- `default_language` -- Default language code (default: `en`)
- `cta_url` -- Default call-to-action URL (default: `https://web.synaplan.com`)
- `brand_name` -- Brand name used in generated content (default: `Synaplan`)

**Structured Data** (plugin_data table, plugin `marketeer`):
- Type `campaign` -- Campaign definitions (slug, title, topic, languages, CTA, status)
- Type `page` -- Generated landing page data per campaign per language (HTML, metadata)

**File Storage** (user upload directory):
```
{uploadDir}/{userPath}/marketeer/{campaign-slug}/{language}/
  ├── index.html
  ├── keywords.txt
  └── images/
      ├── hero.png
      ├── linkedin.png
      ├── instagram.png
      └── og.png
```

## Related Documents

- `planning/plugin-architecture.md` -- Auto-discovery system and 3-market model
- `planning/automation.md` -- Content generation and ad automation strategy
- `marketeer-plugin/` -- Plugin source code (source of truth)
