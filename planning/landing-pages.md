# Landing Page Strategy & Status

**Goal**: Create an automated sales machine using targeted landing pages and keyword marketing.

## Strategy

1.  **Scalable Content Creation**:
    *   Use a standardized "Poster" template (`landing/AGENTS.md`).
    *   Create campaign-specific content in `plan.md`.
    *   Generate high-quality hero images using Synaplan's AI (Nano Banana 2).

2.  **Keyword Marketing**:
    *   Define keywords per campaign (`keywords.txt`).
    *   Automate ad campaign creation on Google Ads and Bing Ads.
    *   Target specific intent (e.g., "switch ai model", "avoid vendor lock-in").

3.  **Conversion Optimization**:
    *   Clear CTA: "Register" on `web.synaplan.com`.
    *   Mobile-first design for social media traffic.
    *   Fast loading times (static HTML).

4.  **Automation Loop**:
    *   Script to generate HTML from `plan.md` + Template (Future).
    *   Script to sync `keywords.txt` with Ad Platforms (Future).
    *   Analytics to measure conversion rate per page.

## Campaign Status

| Campaign | Language | Status | Plan | Keywords | Page |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `any-model` | `en` | **Ready** | [View](landing/any-model/plan.md) | [View](landing/any-model/en/keywords.txt) | [View](landing/any-model/en/index.html) |
| `any-model` | `de` | *Planned* | - | - | - |
| `rag-pipeline` | `en` | *Planned* | - | - | - |
| `local-ai` | `en` | *Planned* | - | - | - |

## Next Steps

1.  **Image Generation**: Integrate with Synaplan's image generation (Nano Banana 2) to replace placeholders.
2.  **Tracking**: Add Google Analytics 4 and Google Ads Conversion Tracking tags to the template.
3.  **Ad Scripts**: Develop Python/Node.js scripts to interact with Google Ads API using `keywords.txt`.
4.  **Scale**: Replicate `any-model` structure for `rag-pipeline` and `local-ai`.
