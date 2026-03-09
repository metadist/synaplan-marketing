<?php

declare(strict_types=1);

namespace Plugin\Marketeer\Service;

/**
 * Builds AI prompts for landing pages, ad copy, social posts, keyword lists,
 * image descriptions, compliance checks, and campaign planning.
 */
final readonly class ContentGenerator
{
    private const LANGUAGE_NAMES = [
        'en' => 'English',
        'de' => 'German',
        'fr' => 'French',
        'es' => 'Spanish',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'tr' => 'Turkish',
        'zh' => 'Chinese',
        'ja' => 'Japanese',
        'ar' => 'Arabic',
    ];

    private const PLATFORM_SPECS = [
        'google' => [
            'headline_max' => 30,
            'headline_count' => 15,
            'description_max' => 90,
            'description_count' => 4,
        ],
        'linkedin' => [
            'post_max' => 3000,
            'headline_max' => 150,
        ],
        'instagram' => [
            'caption_max' => 2200,
            'hashtag_count' => 30,
        ],
        'facebook' => [
            'post_max' => 63206,
            'headline_max' => 40,
        ],
    ];

    // --- Landing Page Prompts ---

    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     */
    public function buildLandingPagePrompt(array $campaign, array $config, string $language): string
    {
        $langName = self::LANGUAGE_NAMES[$language] ?? $language;
        $brandName = $config['brand_name'] ?? 'Synaplan';
        $ctaUrl = $campaign['cta_url'] ?? $config['cta_url'] ?? 'https://web.synaplan.com';
        $privacyUrl = $config['privacy_policy_url'] ?? '#';
        $imprintUrl = $config['imprint_url'] ?? '#';
        $accent = $campaign['accent_color'] ?? $config['default_accent_color'] ?? '#00b79d';
        $logoUrl = $campaign['brand_logo_url'] ?? $config['default_brand_logo_url'] ?? '';
        $colorScheme = $campaign['color_scheme'] ?? $config['default_color_scheme'] ?? 'dark backgrounds (#111) with vibrant accent';

        $ctaButtons = '';
        if (!empty($campaign['ctas'])) {
            $hasModal = !empty($campaign['modal_content']);
            $ctaButtons = "\n\nCTA buttons inside the card, stacked vertically with 0.6rem gap:\n";
            foreach ($campaign['ctas'] as $i => $cta) {
                if ($i === 0 && $hasModal) {
                    $ctaButtons .= "- PRIMARY: <button class=\"action-btn\" onclick=\"document.getElementById('overlay').classList.add('open')\">{$cta['label']}</button> — opens the overlay modal\n";
                } elseif ($i === 0) {
                    $ctaButtons .= "- PRIMARY: <a href=\"{$cta['url']}\" class=\"action-btn\">{$cta['label']}</a>\n";
                } else {
                    $ctaButtons .= "- SECONDARY: <a href=\"{$cta['url']}\" style=\"color:{$accent};font-size:0.85rem;text-decoration:underline;font-weight:600\">{$cta['label']}</a>\n";
                }
            }
        } else {
            $ctaButtons = "\n\nSingle CTA: <a href=\"{$ctaUrl}\" class=\"action-btn\">Learn More</a>";
        }

        $uspList = '';
        if (!empty($campaign['unique_selling_points'])) {
            $uspList = "\nKey selling points (use 2-3 in the subheadline): " . implode(' · ', $campaign['unique_selling_points']);
        }

        $modalSection = '';
        if (!empty($campaign['modal_content'])) {
            $mc = addslashes($campaign['modal_content']);
            $modalSection = <<<MODAL

OVERLAY MODAL — add this structure AFTER the card, BEFORE the cookie bar:
<div id="overlay" class="overlay" onclick="this.classList.remove('open')">
  <div class="overlay-card" onclick="event.stopPropagation()">
    <button class="close-btn" onclick="document.getElementById('overlay').classList.remove('open')">✕</button>
    <div class="overlay-body">{$mc}</div>
  </div>
</div>

Overlay CSS:
.overlay{{position:fixed;inset:0;background:rgba(0,0,0,0.85);backdrop-filter:blur(5px);z-index:100;display:flex;justify-content:center;align-items:center;opacity:0;pointer-events:none;transition:opacity .3s;cursor:pointer}}
.overlay.open{{opacity:1;pointer-events:all}}
.overlay-card{{background:#fff;color:#000;width:100%;max-width:500px;max-height:80vh;border-radius:20px;overflow-y:auto;padding:2rem;transform:scale(.9);opacity:0;transition:transform .3s,opacity .3s;cursor:default;position:relative}}
.overlay.open .overlay-card{{transform:scale(1);opacity:1}}
.close-btn{{position:absolute;top:1rem;right:1rem;background:none;border:none;font-size:1.5rem;cursor:pointer;color:#666;z-index:1}}
.overlay-body h3{{font-size:.8rem;text-transform:uppercase;letter-spacing:.1em;color:#999;margin:1.5rem 0 .5rem}}
.overlay-body p,.overlay-body li{{line-height:1.75;font-size:.95rem}}
.overlay-body ul{{list-style:none;padding:0}}.overlay-body li{{padding-left:1.2rem;position:relative;margin-bottom:.5rem}}.overlay-body li::before{{content:'•';color:{$accent};position:absolute;left:0;font-weight:bold}}
Add ESC key listener: document.addEventListener('keydown',function(e){{if(e.key==='Escape')document.getElementById('overlay').classList.remove('open')}});
MODAL;
        }

        $logoSection = '';
        if (!empty($logoUrl)) {
            $logoSection = "\n9. Inside the card, at the very top (above the hero text), add the brand logo: <img src=\"{$logoUrl}\" alt=\"{$brandName} Logo\" style=\"max-height:40px; margin-bottom:1rem; z-index:10; position:relative;\">";
        }

        return <<<PROMPT
Generate a single-file HTML landing page. Language: {$langName}. Brand: {$brandName}. Accent color: {$accent}. Color scheme: {$colorScheme}.

DESIGN — follow this EXACT layout:
1. html,body: margin:0, padding:0, box-sizing:border-box. Body: min-height:100vh, display:flex, flex-direction:column, align-items:center, justify-content:center, padding:20px, background:#111, font-family:'Inter',sans-serif, color:#f2f2f2. Do NOT use height:100%.
2. ONE centered card (class "poster-container"): max-width:468px, width:100%, min-height:600px, background:#f2f2f2, border:8px solid #fff, border-radius:5px, box-shadow:0 20px 50px rgba(0,0,0,0.5), overflow:hidden, display:flex, flex-direction:column, position:relative.
3. Background layer inside card (class "buzzword-bg"): position:absolute, inset:0, z-index:1, overflow:hidden, pointer-events:none. Contains 6 div rows (.buzz-row) with scrolling tech buzzwords. Each row: position:absolute, white-space:nowrap, font-weight:800, text-transform:uppercase, letter-spacing:0.05em. Text colors: #fff, #e6e6e6, #d9d9d9. Each row at different top% (5,18,34,52,68,82), different font-sizes (1.2-2.4rem), animated with @keyframes scroll-left / scroll-right at 22-35s. Duplicate the text in each row for seamless loop.
4. Content layer (class "poster-content"): position:relative, z-index:10, flex:1, display:flex, flex-direction:column, justify-content:center, align-items:center, padding:2.5rem, text-align:center. Contains hero-text div (flex:1 centered) and footer-block div (flex-shrink:0).
5. hero-text: headline in 'Playfair Display' italic 900 (font-size:clamp(2rem,7vw,2.85rem), line-height:1.05, color:#000), subheadline (font-size:1.09rem, color:#222, max-width:340px), thin divider (40px × 3px, background:{$accent}), small tagline (0.81rem, uppercase, letter-spacing:0.2em, color:#555).
6. footer-block: credits line (0.6rem, uppercase, letter-spacing:0.15em, color:#999) then CTA buttons.
7. action-btn style: background:{$accent}, color:#fff, border:2px solid darker-accent, padding:0.9rem 2.2rem, font-weight:700, text-transform:uppercase, letter-spacing:0.15em, border-radius:4px, font-size:1rem, text-decoration:none, display:inline-block, animation:gentle-float 3s ease-in-out infinite, box-shadow:0 4px 15px rgba(0,0,0,0.2). @keyframes gentle-float: 0%,100% translateY(0), 50% translateY(-5px). Hover: animation paused, transform:scale(1.05).
8. Below the card: div.imprint with two links (Privacy → {$privacyUrl}, Imprint → {$imprintUrl}), font-size:0.75rem, color:#666, a:hover color:#fff.{$logoSection}
{$modalSection}
COOKIE CONSENT — div#cookiebar AFTER all content, BEFORE </body>:
- Style: background:#1a1a2e, padding:16px 24px, text-align:center, font-size:13px, color:#fff, display:flex, align-items:center, justify-content:center, flex-wrap:wrap, gap:12px.
- Inner: <span> with cookie text + <a href="{$privacyUrl}" style="color:{$accent}">Privacy Policy</a>.
- Two buttons: "Essential only" (background:transparent, border:1px solid #fff, color:#fff, padding:8px 16px, border-radius:6px, cursor:pointer, font-size:13px) and "Accept all" (background:{$accent}, border:none, color:#fff, padding:8px 16px, border-radius:6px, cursor:pointer, font-weight:600, font-size:13px).
- "Essential only" onclick: document.getElementById('cookiebar').style.display='none'
- "Accept all" onclick: document.getElementById('cookiebar').style.display='none'
- IMPORTANT: use INLINE onclick handlers directly on each button. Do NOT rely on localStorage or external scripts. The onclick MUST directly set style.display='none'.

TECHNICAL:
- Self-contained HTML, inline <style>.
- Google Fonts: Inter (300,400,600,700,900) and Playfair Display (italic 700, italic 900).
- <meta charset="UTF-8">, <meta viewport>, <title>, <meta description>, OG + Twitter Card tags.
- Buzzword text: 30-40 topic-relevant terms, duplicated per row for seamless loop.{$ctaButtons}{$uspList}

Output ONLY the complete HTML. No markdown. Start with <!DOCTYPE html>.
PROMPT;
    }

    /**
     * @param array<string, mixed> $campaign
     */
    public function buildLandingPageRequest(array $campaign, string $language, ?string $extraInstructions): string
    {
        $langName = self::LANGUAGE_NAMES[$language] ?? $language;
        $msg = "Create a landing page for this campaign:\n\n";
        $msg .= "Title: {$campaign['title']}\n";
        $msg .= "Topic/Angle: {$campaign['topic']}\n";
        $msg .= "Language: {$langName}\n";

        if ($extraInstructions) {
            $msg .= "\nAdditional instructions: {$extraInstructions}\n";
        }

        return $msg;
    }

    // --- Ad Copy Prompts ---

    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     * @return array<int, array{role: string, content: string}>
     */
    public function buildAdCopyPrompt(
        array $campaign,
        array $config,
        string $language,
        string $platform,
    ): array {
        $langName = self::LANGUAGE_NAMES[$language] ?? $language;
        $brandName = $config['brand_name'] ?? 'Synaplan';
        $specs = self::PLATFORM_SPECS[$platform] ?? self::PLATFORM_SPECS['google'];

        if ($platform === 'google') {
            return $this->buildGoogleAdsCopyPrompt($campaign, $config, $langName, $brandName, $specs);
        }

        if ($platform === 'linkedin') {
            return $this->buildLinkedInPostPrompt($campaign, $config, $langName, $brandName);
        }

        if ($platform === 'instagram') {
            return $this->buildInstagramPostPrompt($campaign, $config, $langName, $brandName);
        }

        return $this->buildFacebookPostPrompt($campaign, $config, $langName, $brandName);
    }

    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     * @param array<string, int>   $specs
     * @return array<int, array{role: string, content: string}>
     */
    private function buildGoogleAdsCopyPrompt(
        array $campaign,
        array $config,
        string $langName,
        string $brandName,
        array $specs,
    ): array {
        $headlineMax = $specs['headline_max'];
        $headlineCount = $specs['headline_count'];
        $descMax = $specs['description_max'];
        $descCount = $specs['description_count'];

        $systemPrompt = <<<PROMPT
You are a Google Ads specialist. Generate Responsive Search Ad (RSA) copy.

Strict requirements:
- {$headlineCount} headlines, each MAX {$headlineMax} characters (including spaces)
- {$descCount} descriptions, each MAX {$descMax} characters (including spaces)
- Include the brand name "{$brandName}" in at least 3 headlines
- Use power words: free, proven, exclusive, instant, guaranteed
- Include at least 2 headlines with numbers/statistics
- Include at least 1 question-style headline
- Include at least 1 headline with a CTA verb (Get, Try, Start, Discover)

Output as valid JSON only (no markdown):
{
  "headlines": ["headline1", "headline2", ...],
  "descriptions": ["desc1", "desc2", ...],
  "sitelink_suggestions": [
    {"title": "...", "description": "..."}
  ]
}

Language: {$langName}
PROMPT;

        $userMessage = "Generate Google Ads RSA copy for:\n\n";
        $userMessage .= "Product: {$brandName}\n";
        $userMessage .= "Campaign: {$campaign['title']}\n";
        $userMessage .= "Angle: {$campaign['topic']}\n";

        if (!empty($campaign['unique_selling_points'])) {
            $userMessage .= "USPs: " . implode(', ', $campaign['unique_selling_points']) . "\n";
        }
        if (!empty($campaign['target_audience'])) {
            $userMessage .= "Audience: {$campaign['target_audience']}\n";
        }

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ];
    }

    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     * @return array<int, array{role: string, content: string}>
     */
    private function buildLinkedInPostPrompt(
        array $campaign,
        array $config,
        string $langName,
        string $brandName,
    ): array {
        $systemPrompt = <<<PROMPT
You are a LinkedIn marketing expert. Create a compelling LinkedIn post.

Requirements:
- Professional tone, thought-leadership style
- Hook in the first line (stops the scroll)
- Short paragraphs (1-2 sentences each)
- Use line breaks for readability
- Include a clear CTA at the end
- 5-10 relevant hashtags
- Max 3000 characters total
- Do NOT use emojis excessively (max 3-4 total)

Output as valid JSON only (no markdown):
{
  "post_text": "The full post text...",
  "hashtags": ["#hashtag1", "#hashtag2"],
  "hook_line": "The attention-grabbing first line",
  "cta_text": "The call-to-action text"
}

Language: {$langName}
PROMPT;

        $userMessage = "Create a LinkedIn post for:\n\n";
        $userMessage .= "Product: {$brandName}\n";
        $userMessage .= "Campaign: {$campaign['title']}\n";
        $userMessage .= "Angle: {$campaign['topic']}\n";
        $userMessage .= "Landing page: {$campaign['cta_url']}\n";
        if (!empty($campaign['target_audience'])) {
            $userMessage .= "Target audience: {$campaign['target_audience']}\n";
        }
        if (!empty($campaign['unique_selling_points'])) {
            $userMessage .= "USPs: " . implode(', ', $campaign['unique_selling_points']) . "\n";
        }

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ];
    }

    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     * @return array<int, array{role: string, content: string}>
     */
    private function buildInstagramPostPrompt(
        array $campaign,
        array $config,
        string $langName,
        string $brandName,
    ): array {
        $systemPrompt = <<<PROMPT
You are an Instagram marketing expert. Create an engaging Instagram post.

Requirements:
- Conversational, inspiring tone
- Start with a hook or bold statement
- Use emojis strategically (not excessive)
- Include a clear CTA (link in bio, DM us, etc.)
- 20-30 highly relevant hashtags (mix of popular and niche)
- Max 2200 characters for caption
- Suggest an image concept that would pair well

Output as valid JSON only (no markdown):
{
  "caption": "The full caption text with emojis...",
  "hashtags": ["#hashtag1", "#hashtag2"],
  "image_concept": "Brief description of ideal accompanying image",
  "cta_text": "The call-to-action",
  "story_text": "Short text for Instagram Story (max 100 chars)"
}

Language: {$langName}
PROMPT;

        $userMessage = "Create an Instagram post for:\n\n";
        $userMessage .= "Product: {$brandName}\n";
        $userMessage .= "Campaign: {$campaign['title']}\n";
        $userMessage .= "Angle: {$campaign['topic']}\n";
        if (!empty($campaign['target_audience'])) {
            $userMessage .= "Target audience: {$campaign['target_audience']}\n";
        }
        if (!empty($campaign['unique_selling_points'])) {
            $userMessage .= "USPs: " . implode(', ', $campaign['unique_selling_points']) . "\n";
        }

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ];
    }

    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     * @return array<int, array{role: string, content: string}>
     */
    private function buildFacebookPostPrompt(
        array $campaign,
        array $config,
        string $langName,
        string $brandName,
    ): array {
        $systemPrompt = <<<PROMPT
You are a Facebook / Meta marketing expert. Create a compelling Facebook post.

Requirements:
- Engaging, conversational tone that encourages interaction (likes, comments, shares)
- Strong hook in the first 1-2 lines (visible before "See more")
- Keep the main message under 400 characters for best engagement (full text can be longer)
- Include a clear CTA with link
- 3-5 relevant hashtags (Facebook uses fewer than Instagram)
- Suggest an image/video concept that pairs well
- Write a short headline for a link preview card (max 40 characters)

Output as valid JSON only (no markdown):
{
  "post_text": "The full Facebook post text...",
  "headline": "Link preview headline (max 40 chars)",
  "link_description": "Link preview description (max 125 chars)",
  "hashtags": ["#hashtag1", "#hashtag2"],
  "image_concept": "Brief description of ideal accompanying image or video",
  "cta_text": "The call-to-action text",
  "best_posting_time": "Suggested posting time (e.g., Tuesday 10am)"
}

Language: {$langName}
PROMPT;

        $userMessage = "Create a Facebook post for:\n\n";
        $userMessage .= "Product: {$brandName}\n";
        $userMessage .= "Campaign: {$campaign['title']}\n";
        $userMessage .= "Angle: {$campaign['topic']}\n";
        $userMessage .= "Link: {$campaign['cta_url']}\n";
        if (!empty($campaign['target_audience'])) {
            $userMessage .= "Target audience: {$campaign['target_audience']}\n";
        }
        if (!empty($campaign['unique_selling_points'])) {
            $userMessage .= "USPs: " . implode(', ', $campaign['unique_selling_points']) . "\n";
        }

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ];
    }

    // --- Keyword Prompts ---

    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     * @return array<int, array{role: string, content: string}>
     */
    public function buildKeywordPrompt(
        array $campaign,
        array $config,
        string $language,
        int $count,
        ?string $extraInstructions,
    ): array {
        $langName = self::LANGUAGE_NAMES[$language] ?? $language;
        $brandName = $config['brand_name'] ?? 'Synaplan';

        $systemPrompt = <<<PROMPT
You are a Google Ads keyword research expert. Generate keyword lists optimized for paid search campaigns.

Output format: Return ONLY a plain text list with one keyword per line. No numbering, no categories, no explanations.
Group similar keywords together but do not add group headers.

Include a mix of:
- High-intent keywords (people ready to buy/sign up)
- Informational keywords (people researching the topic)
- Competitor alternative keywords (e.g., "X alternative")
- Long-tail keywords (3-5 word phrases)
- Negative keywords (prefix with a minus sign: -keyword)

Language: {$langName}
PROMPT;

        $userMessage = "Generate {$count} Google Ads keywords for:\n\n";
        $userMessage .= "Product: {$brandName}\n";
        $userMessage .= "Campaign: {$campaign['title']}\n";
        $userMessage .= "Angle: {$campaign['topic']}\n";
        $userMessage .= "Language: {$langName}\n";

        if ($extraInstructions) {
            $userMessage .= "\nExtra: {$extraInstructions}\n";
        }

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ];
    }

    // --- Google Ads Campaign Structure ---

    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     * @return array<int, array{role: string, content: string}>
     */
    public function buildAdsCampaignStructurePrompt(
        array $campaign,
        array $config,
        string $language,
        ?string $extraInstructions,
    ): array {
        $langName = self::LANGUAGE_NAMES[$language] ?? $language;
        $brandName = $config['brand_name'] ?? 'Synaplan';
        $ctaUrl = $campaign['cta_url'] ?? $config['cta_url'] ?? 'https://web.synaplan.com';

        $systemPrompt = <<<PROMPT
You are a Google Ads campaign strategist. Generate a complete campaign structure.

Output as valid JSON only (no markdown):
{
  "campaign_name": "Campaign name",
  "campaign_type": "Search",
  "bidding_strategy": "Maximize Conversions",
  "daily_budget_suggestion": 50,
  "target_locations": ["Country/Region"],
  "ad_groups": [
    {
      "name": "Ad Group Name",
      "theme": "Brief theme description",
      "keywords": [
        {"keyword": "keyword text", "match_type": "phrase"},
        {"keyword": "keyword text", "match_type": "exact"},
        {"keyword": "keyword text", "match_type": "broad"}
      ],
      "negative_keywords": ["negative1", "negative2"],
      "ads": [
        {
          "headlines": ["h1 (max 30 chars)", "h2", "h3"],
          "descriptions": ["desc1 (max 90 chars)", "desc2"],
          "final_url": "{$ctaUrl}",
          "display_path": ["path1", "path2"]
        }
      ]
    }
  ],
  "campaign_negative_keywords": ["free trial competitor", "jobs"],
  "extensions_suggestions": {
    "sitelinks": [{"title": "...", "url": "...", "description": "..."}],
    "callouts": ["callout1", "callout2"],
    "structured_snippets": {"header": "Types", "values": ["val1", "val2"]}
  },
  "best_practices_notes": ["tip1", "tip2"]
}

Create 3-5 tightly themed ad groups. Each ad group should have 10-20 keywords with mixed match types, and 2-3 ad variations.

Language: {$langName}
PROMPT;

        $userMessage = "Generate a Google Ads campaign structure for:\n\n";
        $userMessage .= "Product: {$brandName}\n";
        $userMessage .= "Campaign: {$campaign['title']}\n";
        $userMessage .= "Angle: {$campaign['topic']}\n";
        $userMessage .= "Landing page: {$ctaUrl}\n";
        $userMessage .= "Language: {$langName}\n";

        if (!empty($campaign['target_audience'])) {
            $userMessage .= "Target audience: {$campaign['target_audience']}\n";
        }
        if (!empty($campaign['unique_selling_points'])) {
            $userMessage .= "USPs: " . implode(', ', $campaign['unique_selling_points']) . "\n";
        }
        if ($extraInstructions) {
            $userMessage .= "\nAdditional instructions: {$extraInstructions}\n";
        }

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ];
    }

    // --- Image Prompts ---

    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     */
    public function buildImagePrompt(array $campaign, array $config, string $imageType): string
    {
        $brandName = $config['brand_name'] ?? 'Synaplan';
        $title = $campaign['title'] ?? 'AI Knowledge Management';
        $accent = $campaign['accent_color'] ?? $config['default_accent_color'] ?? '#00b79d';
        $colorScheme = $campaign['color_scheme'] ?? $config['default_color_scheme'] ?? 'dark backgrounds with vibrant accent';

        $dimensions = match ($imageType) {
            'linkedin' => '1200x627 pixels (LinkedIn recommended)',
            'instagram' => '1080x1080 pixels (square, Instagram feed)',
            'og' => '1200x630 pixels (Open Graph / social sharing)',
            'icon' => '512x512 pixels (app icon / favicon)',
            'banner_wide' => '728x90 pixels (leaderboard banner ad)',
            'banner_rect' => '300x250 pixels (medium rectangle banner ad)',
            'banner_sky' => '160x600 pixels (wide skyscraper banner ad)',
            default => '1920x1080 pixels (hero banner)',
        };

        $style = match ($imageType) {
            'icon' => 'Simple, recognizable icon design with bold shapes. Single focal element on clean background.',
            'banner_wide', 'banner_rect', 'banner_sky' => 'Clean advertising banner. Abstract tech pattern, professional. No text — text will be overlaid.',
            default => 'Clean, tech-forward design with gradients and abstract shapes.',
        };

        return "Create a professional marketing image for {$brandName}. "
            . "Theme: {$title}. "
            . "Style: {$style} Color palette: {$colorScheme}, using {$accent} as the main accent color. "
            . "Dimensions: {$dimensions}. "
            . "Do NOT include any text in the image — text will be overlaid separately.";
    }

    // --- Video Prompts ---

    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     */
    public function buildVideoPrompt(
        array $campaign,
        array $config,
        ?string $userDescription = null,
    ): string {
        $brandName = $config['brand_name'] ?? 'Synaplan';
        $title = $campaign['title'] ?? 'AI Knowledge Management';
        $accent = $campaign['accent_color'] ?? $config['default_accent_color'] ?? '#00b79d';
        $colorScheme = $campaign['color_scheme'] ?? $config['default_color_scheme'] ?? 'dark backgrounds with vibrant accent';

        if ($userDescription !== null && trim($userDescription) !== '') {
            return "Create a short professional marketing video clip for {$brandName}. "
                . "Campaign: {$title}. "
                . "Color palette: {$colorScheme}, accent color {$accent}. "
                . "Description: {$userDescription}";
        }

        $topic = $campaign['topic'] ?? $title;
        $usps = '';
        if (!empty($campaign['unique_selling_points'])) {
            $usps = ' Highlight: ' . implode(', ', array_slice($campaign['unique_selling_points'], 0, 2)) . '.';
        }

        return "Create a short cinematic marketing video clip for {$brandName}. "
            . "Theme: {$topic}.{$usps} "
            . "Style: Smooth motion graphics with abstract tech visuals, clean transitions, "
            . "professional corporate feel. "
            . "Color palette: {$colorScheme}, using {$accent} as the accent color. "
            . "No text overlays or voiceover — purely visual.";
    }

    // --- Compliance / GDPR ---

    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     * @return array<int, array{role: string, content: string}>
     */
    public function buildComplianceCheckPrompt(
        array $campaign,
        array $config,
        string $language,
    ): array {
        $langName = self::LANGUAGE_NAMES[$language] ?? $language;
        $brandName = $config['brand_name'] ?? 'Synaplan';

        $systemPrompt = <<<PROMPT
You are a digital marketing compliance expert specializing in GDPR, ePrivacy, and advertising regulations.

Analyze the campaign and provide a compliance checklist. Output as valid JSON only (no markdown):
{
  "overall_risk": "low|medium|high",
  "checklist": [
    {
      "category": "GDPR|ePrivacy|Advertising|Accessibility",
      "item": "Description of the check",
      "status": "pass|warning|fail|manual_check",
      "recommendation": "What to do",
      "regulation": "Which regulation this relates to (e.g., GDPR Art. 6, ePrivacy Directive)"
    }
  ],
  "required_legal_pages": [
    {"page": "Privacy Policy", "required": true, "reason": "GDPR Art. 13/14"},
    {"page": "Imprint", "required": true, "reason": "TMG §5 (Germany) / similar local law"},
    {"page": "Cookie Policy", "required": true, "reason": "ePrivacy Directive"}
  ],
  "tracking_recommendations": {
    "consent_required": true,
    "legitimate_interest_possible": ["analytics without user ID"],
    "always_needs_consent": ["Google Ads remarketing", "Meta Pixel", "cross-site tracking"]
  },
  "google_ads_policy_notes": [
    "Specific Google Ads policy considerations for this campaign"
  ],
  "data_processing_notes": "Summary of data processing activities and legal basis"
}

Language for output: {$langName}
PROMPT;

        $campaignJson = json_encode($campaign, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $userMessage = "Check compliance for this marketing campaign:\n\n";
        $userMessage .= "Brand: {$brandName}\n";
        $userMessage .= "Campaign data:\n{$campaignJson}\n";
        $userMessage .= "Target markets: EU (GDPR applies)\n";

        $platforms = $campaign['platforms'] ?? ['google'];
        $userMessage .= "Advertising platforms: " . implode(', ', $platforms) . "\n";

        if (!empty($campaign['tracking'])) {
            $userMessage .= "Tracking configured: " . json_encode($campaign['tracking']) . "\n";
        }

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ];
    }

    // --- Campaign Planning ---

    /**
     * @param array<string, mixed> $config
     * @return array<int, array{role: string, content: string}>
     */
    public function buildCampaignPlanPrompt(
        string $idea,
        array $config,
        string $language,
    ): array {
        $langName = self::LANGUAGE_NAMES[$language] ?? $language;
        $brandName = $config['brand_name'] ?? 'Synaplan';

        $systemPrompt = <<<PROMPT
You are a senior marketing strategist. Given a campaign idea, create a comprehensive campaign plan.

Output as valid JSON only (no markdown):
{
  "campaign_name": "Suggested slug-friendly name",
  "title": "Campaign headline",
  "topic": "Detailed campaign angle/positioning (2-3 sentences)",
  "target_audience": "Who this campaign targets",
  "unique_selling_points": ["USP 1", "USP 2", "USP 3"],
  "recommended_platforms": ["google", "linkedin", "instagram", "facebook"],
  "languages": ["en"],
  "messaging_framework": {
    "primary_message": "The one thing we want the audience to remember",
    "supporting_messages": ["msg1", "msg2", "msg3"],
    "tone": "Professional yet approachable"
  },
  "content_plan": [
    {"asset": "Landing page", "priority": "high", "notes": "..."},
    {"asset": "Google Ads", "priority": "high", "notes": "..."},
    {"asset": "LinkedIn post", "priority": "medium", "notes": "..."},
    {"asset": "Instagram post", "priority": "medium", "notes": "..."}
  ],
  "budget_suggestion": {
    "monthly_total": 1000,
    "breakdown": {"google_ads": 600, "linkedin_ads": 300, "content_creation": 100}
  },
  "timeline": [
    {"week": 1, "tasks": ["Create landing page", "Set up tracking"]},
    {"week": 2, "tasks": ["Launch Google Ads", "Publish LinkedIn post"]}
  ],
  "kpis": [
    {"metric": "Click-through rate", "target": ">2%", "platform": "Google Ads"},
    {"metric": "Conversion rate", "target": ">3%", "platform": "Landing page"}
  ],
  "cta_suggestions": [
    {"type": "register", "label": "Start Free Trial", "url_path": "/register"},
    {"type": "email", "label": "Contact Sales", "url_path": "mailto:sales@example.com"}
  ]
}

Language: {$langName}
PROMPT;

        $userMessage = "Create a campaign plan for:\n\n";
        $userMessage .= "Brand: {$brandName}\n";
        $userMessage .= "Idea: {$idea}\n";

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ];
    }

    // --- Pre-Launch Checklist ---

    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     * @param array<string, mixed> $assets
     * @return array<int, array{role: string, content: string}>
     */
    public function buildPreLaunchCheckPrompt(
        array $campaign,
        array $config,
        array $assets,
    ): array {
        $brandName = $config['brand_name'] ?? 'Synaplan';
        $systemPrompt = <<<PROMPT
You are a marketing launch manager. Review the campaign assets and generate a pre-launch checklist.

Output as valid JSON only (no markdown):
{
  "ready_to_launch": true|false,
  "score": 85,
  "checklist": [
    {
      "category": "Content|Technical|Legal|Analytics|Distribution",
      "item": "Check description",
      "status": "done|missing|warning",
      "details": "Specific finding or recommendation"
    }
  ],
  "blocking_issues": ["List of issues that MUST be resolved before launch"],
  "recommendations": ["Nice-to-have improvements"],
  "estimated_review_time": "How long a human review should take"
}
PROMPT;

        $assetsJson = json_encode($assets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $campaignJson = json_encode($campaign, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $userMessage = "Pre-launch review for campaign:\n\n";
        $userMessage .= "Brand: {$brandName}\n";
        $userMessage .= "Campaign:\n{$campaignJson}\n\n";
        $userMessage .= "Available assets:\n{$assetsJson}\n";

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ];
    }

    // --- Refinement ---

    /**
     * @param array<string, mixed>      $campaign
     * @param array<string, mixed>      $config
     * @param array<string, mixed>|null $existingPage
     * @return array<int, array{role: string, content: string}>
     */
    public function buildRefineMessages(
        array $campaign,
        array $config,
        string $language,
        string $target,
        string $refinementPrompt,
        ?array $existingPage,
    ): array {
        if ($target === 'html' && $existingPage !== null) {
            return [
                ['role' => 'system', 'content' => $this->buildLandingPagePrompt($campaign, $config, $language)],
                ['role' => 'assistant', 'content' => $existingPage['html'] ?? ''],
                ['role' => 'user', 'content' => "Please modify the landing page with these changes: {$refinementPrompt}\n\nOutput ONLY the complete updated HTML. No markdown, no explanations."],
            ];
        }

        return $this->buildKeywordPrompt($campaign, $config, $language, 50, $refinementPrompt);
    }

    // --- Response Parsing ---

    public function extractHtml(string $response): string
    {
        $response = trim($response);

        if (str_starts_with($response, '```')) {
            $response = preg_replace('/^```(?:html)?\s*/i', '', $response);
            $response = preg_replace('/\s*```$/', '', $response);
        }

        $response = trim($response);

        if (!str_starts_with($response, '<!DOCTYPE') && !str_starts_with($response, '<html')) {
            $docStart = stripos($response, '<!DOCTYPE');
            if ($docStart === false) {
                $docStart = stripos($response, '<html');
            }
            if ($docStart !== false) {
                $response = substr($response, $docStart);
            }
        }

        $response = mb_convert_encoding($response, 'UTF-8', 'UTF-8');

        return $response;
    }

    /**
     * @return string[]
     */
    public function parseKeywordResponse(string $response): array
    {
        $response = trim($response);

        if (str_starts_with($response, '```')) {
            $response = preg_replace('/^```[a-z]*\s*/i', '', $response);
            $response = preg_replace('/\s*```$/', '', $response);
        }

        $lines = explode("\n", $response);
        $keywords = [];

        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^\d+[\.\)]\s*/', '', $line);
            $line = trim($line, '- ');

            if ($line !== '' && !str_starts_with($line, '#') && !str_starts_with($line, '//')) {
                $keywords[] = $line;
            }
        }

        return array_values(array_unique($keywords));
    }

    /**
     * @return array<string, mixed>
     */
    public function parseJsonResponse(string $response): array
    {
        $response = trim($response);

        if (str_starts_with($response, '```')) {
            $response = preg_replace('/^```(?:json)?\s*/i', '', $response);
            $response = preg_replace('/\s*```$/', '', $response);
        }

        $response = trim($response);

        $jsonStart = strpos($response, '{');
        if ($jsonStart !== false && $jsonStart > 0) {
            $response = substr($response, $jsonStart);
        }

        $lastBrace = strrpos($response, '}');
        if ($lastBrace !== false) {
            $response = substr($response, 0, $lastBrace + 1);
        }

        $decoded = json_decode($response, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            $repaired = $this->repairJson($response);
            $decoded = json_decode($repaired, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                return ['raw_response' => $response, '_parse_error' => json_last_error_msg()];
            }
        }

        return $decoded;
    }

    private function repairJson(string $json): string
    {
        $json = preg_replace('/,\s*([}\]])/', '$1', $json);
        $json = preg_replace('/\(([^)]*)\)/', '[$1]', $json);
        $json = str_replace(["\t", "\r"], ['  ', ''], $json);

        return $json;
    }
}
