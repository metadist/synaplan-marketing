# Landing Page Strategy & Status

**Goal**: Create an automated sales machine using targeted landing pages and keyword marketing, ultimately evolving into a standalone Synaplan Plugin.

## Architectural Decision: The "Marketer" Plugin

We are transitioning from a manual script-based approach to a fully integrated **Synaplan Plugin** (similar to the `synaplan-sortx` architecture). This plugin will be rolled out to `synaplan-platform` and offered as a premium feature to other users.

**Key Architectural Pillars:**
1. **Web Interface**: A well-formed UI to configure campaigns, define topics, and modify content using prompts.
2. **API Integration**: Uses the live Synaplan API (`web.synaplan.com`). Users will save their Synaplan API key in the plugin configuration.
3. **Storage & Hosting**: Generated assets (HTML, texts, images) are saved directly to the user's public upload directory (mounted storage space on the platform).
4. **Multi-Language Support**: Data for different languages will be saved in dedicated subdirectories within the campaign folder.
5. **Export**: Users can download a complete ZIP file of their generated landing pages.

---

## Step-by-Step Development Plan

### Phase 1: MVP Release (Foundation & Generation)
*Focus: Manual proof-of-concept and core plugin infrastructure.*

1. **Manual Prototyping**: Create the first 3-4 landing pages manually within the Cursor workspace using prompting and manual adjustments to perfect the template and output quality.
2. **Plugin Skeleton**: Set up the basic plugin architecture (following the non-invasive Synaplan plugin model).
3. **Configuration UI**: Build the web interface to input the Synaplan API key, define the landing page topic, and configure basic settings.
4. **Asset Generation**: 
   - Integrate with Synaplan API to generate HTML copy, texts, and images via prompts.
   - Generate keyword lists for Google Ads.
   - Generate specific image formats for social sharing (LinkedIn, Instagram).
5. **Storage & Export**:
   - Save generated files into the user's public upload directory, structured by language (e.g., `/en/`, `/de/`).
   - Implement the "Download ZIP" functionality for the entire landing page package.

### Phase 2: Release 2 (Publishing & Distribution)
*Focus: Pushing content to external channels.*

1. **"Add Publication" Workflows**: Build the UI and logic to distribute the generated content.
2. **LinkedIn Integration**: Share the landing page directly to LinkedIn as a post with the generated social images and text.
3. **Instagram Integration**: Upload generated images to Instagram, including the landing page URL in the bio/post.
4. **Google Ads Management (Complex)**: 
   - Integrate with the Google Ads API.
   - Automatically sync the generated keyword lists.
   - Set up basic ad campaigns pointing to the hosted landing pages.

### Phase 3: Final Release (Advanced Media & Video)
*Focus: Rich media and video platforms.*

1. **Short Video Integration**: Incorporate video content into the marketing workflow.
2. **Screenmovie Feature**: Integrate the existing screenmovie option already added to Synaplan.
3. **TikTok Publishing**: Allow users to record/generate a video of a feature and publish it directly to TikTok to drive traffic to the landing pages.

---

## Campaign Status (Manual Prototyping Phase)

| Campaign | Language | Status | Plan | Keywords | Page |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `any-model` | `en` | **Ready** | [View](landing/any-model/plan.md) | [View](landing/any-model/en/keywords.txt) | [View](landing/any-model/en/index.html) |
| `any-model` | `de` | *Planned* | - | - | - |
| `rag-pipeline` | `en` | *Planned* | - | - | - |
| `local-ai` | `en` | *Planned* | - | - | - |

## Immediate Next Steps

1. **Refine Manual Pages**: Finish the first 3-4 manual pages in the workspace to finalize the prompt structure and HTML templates.
2. **Initialize Plugin Repo**: Create the plugin skeleton (e.g., `marketing-plugin/`) following the SortX architecture guidelines.
3. **Build Config UI**: Develop the settings page to store the Synaplan API key and default user preferences.