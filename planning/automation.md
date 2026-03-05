# Automated Sales Machine Strategy

**Goal**: Automate the creation, optimization, and scaling of landing pages and ad campaigns.

## 1. Content Automation

*   **Template Engine**: Use a simple templating system (e.g., Python Jinja2 or Node.js EJS) to generate `index.html` from `plan.md` metadata.
*   **Image Generation**:
    *   Script to call Synaplan's "Nano Banana 2" (TheHive/OpenAI via API) to generate hero images based on `plan.md` keywords.
    *   Automatically resize and optimize images for web (WebP).
*   **Content Injection**:
    *   Inject `keywords.txt` into meta tags and page content for SEO relevance.
    *   Inject tracking IDs dynamically.

## 2. Ad Campaign Automation

*   **Google Ads Script / API**:
    *   Read `keywords.txt` from each campaign directory.
    *   Create/Update Ad Groups based on directory name (e.g., "Campaign: Any Model").
    *   Create Responsive Search Ads using headlines from `plan.md` (Hero Headline, Sub-headline).
    *   Set destination URL to the specific landing page (e.g., `https://marketing.synaplan.com/landing/any-model/en/`).
*   **Bing Ads Script**: Similar logic using Microsoft Advertising API.

## 3. Tracking & Analytics

*   **Global Tag (GTM)**: Implement Google Tag Manager container on all landing pages.
*   **Conversion Events**:
    *   `view_page`: Page load.
    *   `click_cta`: Click on "Get Started" / "Register".
    *   `submit_form`: Successful registration on `web.synaplan.com` (requires cross-domain tracking).
*   **Feedback Loop**:
    *   Script to fetch conversion data from Google Ads API.
    *   Correlate conversions with specific keywords and landing page variants.
    *   Automatically pause underperforming keywords/ads.

## 4. Workflow

1.  **Define**: Create `landing/{campaign}/plan.md` and `keywords.txt`.
2.  **Generate**: Run `make generate-landing` (hypothetical command) to build HTML and images.
3.  **Deploy**: Push to static hosting (e.g., GitHub Pages, Vercel, or S3).
4.  **Advertise**: Run `make sync-ads` to update Google/Bing campaigns.
5.  **Optimize**: Review weekly automated reports on CPA (Cost Per Acquisition) and ROAS.
