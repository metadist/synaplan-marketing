# Landing Page Campaign Rules

This directory contains landing page campaigns for Synaplan marketing.
Each subdirectory represents a campaign (e.g., `any-model`).

## Campaign Structure

For each campaign directory (e.g., `any-model/`), the following structure is REQUIRED:

1.  **`plan.md`**:
    *   Contains the planning details for the campaign.
    *   Must define the target audience, value proposition, and key messaging.
    *   Must list the target languages (e.g., `en`, `de`).

2.  **Language Subdirectories** (e.g., `en/`, `de/`):
    *   **`index.html`**: The landing page file.
        *   Must be mobile-ready and vertical.
        *   Must follow the "Poster" style (see `synaplan-marketing/jobs/` for reference).
        *   Must include Open Graph and Twitter card meta tags.
        *   Must have a clear Call to Action (CTA) pointing to `https://web.synaplan.com/register`.
        *   Must include the "Synaplan" branding and mascot.
    *   **`keywords.txt`**:
        *   A list of keywords for marketing (Google Ads, Bing Ads).
        *   One keyword per line.

## Landing Page Guidelines

*   **Style**: Vertical, mobile-first, "Poster" aesthetic.
*   **Images**: High-quality images (generated via Synaplan's "Nano Banana 2" or equivalent high-quality model).
*   **Content**:
    *   Hero Headline: Catchy, relevant to the campaign.
    *   Sub-headline: Explains the value proposition.
    *   Buzzwords: Background animated buzzwords relevant to the topic.
    *   Body: 3-4 sections explaining the benefits (What, Why, How).
    *   CTA: "Get Started", "Sign Up Free", etc.
*   **Tracking**: Ensure analytics/tracking scripts are included (to be defined).

## Automation & Workflow

1.  **Plan**: Create `plan.md`.
2.  **Keywords**: Research and populate `keywords.txt`.
3.  **Content**: Draft content for `index.html`.
4.  **Image**: Generate/Select a hero image.
5.  **Build**: Create `index.html`.
6.  **Deploy**: (Process to be defined - likely static hosting or copy to webroot).
