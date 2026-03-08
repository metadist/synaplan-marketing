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
        'discord' => [
            'message_max' => 2000,
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

        $ctaSection = '';
        if (!empty($campaign['ctas'])) {
            $ctaSection = "\n\nCall-to-Action buttons to include:\n";
            foreach ($campaign['ctas'] as $cta) {
                $ctaSection .= "- \"{$cta['label']}\" → {$cta['url']} (style: {$cta['type']})\n";
            }
        } else {
            $ctaSection = "\n\nPrimary CTA: Link to {$ctaUrl} with a compelling button text.";
        }

        $audienceSection = '';
        if (!empty($campaign['target_audience'])) {
            $audienceSection = "\n\nTarget audience: {$campaign['target_audience']}";
        }

        $uspSection = '';
        if (!empty($campaign['unique_selling_points'])) {
            $usps = implode(', ', $campaign['unique_selling_points']);
            $uspSection = "\n\nKey selling points to emphasize: {$usps}";
        }

        $trackingSection = '';
        if (!empty($campaign['tracking'])) {
            $tracking = $campaign['tracking'];
            if (!empty($tracking['gtm_id'])) {
                $trackingSection .= "\n\nInclude Google Tag Manager snippet with container ID: {$tracking['gtm_id']}";
            }
            if (!empty($tracking['gads_conversion_id'])) {
                $trackingSection .= "\nInclude Google Ads conversion tracking with ID: {$tracking['gads_conversion_id']}";
            }
        }

        return <<<PROMPT
You are an expert marketing copywriter and web developer. Generate a complete, production-ready landing page as a single HTML file.

Requirements:
- Language: {$langName}
- Brand: {$brandName}
- The page must be a self-contained HTML file (inline CSS, no external dependencies except Google Fonts)
- Mobile-first responsive design with smooth animations
- Fast loading (no heavy JS frameworks)
- Professional, modern, conversion-optimized layout
- Include: hero section with compelling headline & subheadline, key benefits section (3-4 benefits with icons), features section with visual hierarchy, social proof / trust signals section, FAQ section, clear CTA buttons
- Use a sophisticated color scheme: dark backgrounds (#0a0a0a, #1a1a2e) with vibrant accent (#00b79d or similar teal/green)
- Include Open Graph meta tags for social sharing (og:title, og:description, og:image, og:type)
- Include Twitter Card meta tags
- Include proper <title> and <meta description> optimized for SEO
- Add schema.org structured data (JSON-LD) for the product/service
- Include a cookie consent banner placeholder (simple dismissible bar)
- Add smooth scroll behavior and subtle fade-in animations via CSS
- Footer with privacy policy link, imprint link, and copyright{$ctaSection}{$audienceSection}{$uspSection}{$trackingSection}

Output ONLY the complete HTML. No markdown, no explanations, no code fences. Start with <!DOCTYPE html> and end with </html>.
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

        return $this->buildDiscordPostPrompt($campaign, $config, $langName, $brandName);
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
    private function buildDiscordPostPrompt(
        array $campaign,
        array $config,
        string $langName,
        string $brandName,
    ): array {
        $systemPrompt = <<<PROMPT
You are a community marketing expert for Discord. Create an engaging announcement.

Requirements:
- Casual, developer-friendly tone
- Use Discord markdown formatting (**bold**, *italic*, `code`, > quotes)
- Include a rich embed structure suggestion
- Max 2000 characters for the message
- Include a clear CTA with link
- Suggest channel categories where this would fit (#announcements, #general, etc.)

Output as valid JSON only (no markdown):
{
  "message": "The Discord message with markdown...",
  "embed": {
    "title": "Embed title",
    "description": "Embed description",
    "color": "#hex_color",
    "fields": [{"name": "field", "value": "value", "inline": true}]
  },
  "suggested_channels": ["#announcements", "#general"],
  "ping_suggestion": "Optional role/everyone ping recommendation"
}

Language: {$langName}
PROMPT;

        $userMessage = "Create a Discord announcement for:\n\n";
        $userMessage .= "Product: {$brandName}\n";
        $userMessage .= "Campaign: {$campaign['title']}\n";
        $userMessage .= "Angle: {$campaign['topic']}\n";
        $userMessage .= "Link: {$campaign['cta_url']}\n";

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
            . "Style: {$style} "
            . "Dimensions: {$dimensions}. "
            . "Do NOT include any text in the image — text will be overlaid separately.";
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
  "recommended_platforms": ["google", "linkedin", "instagram", "discord"],
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
            return ['raw_response' => $response, '_parse_error' => json_last_error_msg()];
        }

        return $decoded;
    }
}
