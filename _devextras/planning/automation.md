# Content Generation & Ad Automation Strategy

**Goal**: Automate the creation, optimization, and distribution of marketing content through the Marketeer plugin API -- no standalone scripts needed.

## Architecture Overview

All automation flows through the Marketeer plugin's API endpoints on the Synaplan platform. The plugin uses Synaplan's existing AI providers (AiFacade) for content generation and image creation.

```
User / Admin / CI Script
        │
        ▼
  Marketeer Plugin API
  /api/v1/user/{id}/plugins/marketeer/...
        │
        ├── /campaigns/{id}/generate         → Landing page HTML
        ├── /campaigns/{id}/generate-keywords → Google Ads keyword list
        ├── /campaigns/{id}/generate-image    → Social images (hero, LinkedIn, Instagram, OG)
        ├── /campaigns/{id}/refine           → Iterate with follow-up prompts
        └── /campaigns/{id}/download         → ZIP package
        │
        ▼
  User Upload Directory (public on platform)
  {uploadDir}/{userPath}/marketeer/{campaign}/{lang}/
```

## 1. Content Generation (MVP -- Phase 1)

### Landing Page HTML

- **Endpoint**: `POST /campaigns/{id}/generate`
- **AI prompt**: System prompt instructs the model to produce a complete, self-contained HTML file with inline CSS, mobile-first responsive design, SEO meta tags, and OG tags.
- **Storage**: Saved as `{campaign}/{lang}/index.html` in the user's upload directory.
- **Refinement**: `POST /campaigns/{id}/refine` sends the existing HTML back to the AI with modification instructions.

### Keyword Lists

- **Endpoint**: `POST /campaigns/{id}/generate-keywords`
- **AI prompt**: Generates a mix of high-intent, informational, competitor-alternative, long-tail, and negative keywords.
- **Storage**: Saved as `{campaign}/{lang}/keywords.txt` (one keyword per line).
- **Output**: Ready for direct import into Google Ads or Bing Ads.

### Social Images

- **Endpoint**: `POST /campaigns/{id}/generate-image`
- **Types**: `hero` (1920x1080), `linkedin` (1200x627), `instagram` (1080x1080), `og` (1200x630).
- **AI prompt**: Clean, text-free marketing images using the user's configured image generation provider.
- **Storage**: Saved in `{campaign}/{lang}/images/`.

## 2. Ad Campaign Automation (Phase 2)

### Google Ads Integration

The plugin will integrate with the Google Ads API to:
1. Read generated `keywords.txt` for a campaign.
2. Create or update Ad Groups based on the campaign slug.
3. Create Responsive Search Ads using headlines and descriptions from the landing page.
4. Set destination URLs to the hosted landing page.
5. Configure basic bidding strategies.

**New endpoints** (Phase 2):
- `POST /campaigns/{id}/publish/google-ads` -- Sync keywords and create ad campaigns.
- `GET /campaigns/{id}/publish/google-ads/status` -- Check campaign status and metrics.

### LinkedIn Publishing

- `POST /campaigns/{id}/publish/linkedin` -- Share as a post with generated image and text.
- Requires LinkedIn OAuth token stored in plugin config.

### Instagram Publishing

- `POST /campaigns/{id}/publish/instagram` -- Upload image with pre-formatted caption.
- Requires Instagram Business API token.

## 3. Tracking & Analytics (Phase 2)

### Tracking Tags

Landing pages should include:
- Google Tag Manager container snippet.
- Google Ads conversion tracking tag.
- Facebook/Meta Pixel (optional).

These are injected by the template system based on tracking IDs stored in plugin config:
- `P_marketeer.gtm_id` -- GTM container ID
- `P_marketeer.gads_conversion_id` -- Google Ads conversion ID

### Conversion Events

| Event | Trigger |
|-------|---------|
| `page_view` | Landing page load |
| `click_cta` | Click on CTA button |
| `registration` | Successful signup on web.synaplan.com (cross-domain) |

### Feedback Loop (Future)

- Fetch conversion data from Google Ads API.
- Correlate with keywords and landing page variants.
- Auto-pause underperforming keywords.
- Surface metrics in the plugin dashboard.

## 4. Video & Rich Media (Phase 3)

### Screenmovie Integration

- Use Synaplan's existing screenmovie feature to record product demos.
- Associate recordings with campaigns.
- Embed in landing pages or publish separately.

### TikTok Publishing

- `POST /campaigns/{id}/publish/tiktok` -- Upload short video with landing page link.
- Requires TikTok API integration.

## Workflow Summary

### Manual (Phase 1 -- MVP)

1. Create campaign via API or UI (slug, title, topic, languages).
2. Generate landing page, keywords, and images via API.
3. Refine content with follow-up prompts until satisfied.
4. Download ZIP and deploy to static hosting, or serve directly from platform uploads.
5. Manually import `keywords.txt` into Google Ads.

### Semi-Automated (Phase 2)

1. Create campaign via UI.
2. Generate all content with one click.
3. Publish to LinkedIn and Instagram from the UI.
4. Sync keywords to Google Ads via API integration.
5. Monitor performance in the plugin dashboard.

### Fully Automated (Phase 3 -- Future)

1. Define campaign topic and languages.
2. Plugin generates all content, publishes to all channels, and sets up ad campaigns.
3. Feedback loop optimizes keywords and bids based on conversion data.
4. Record product videos and publish to TikTok.
