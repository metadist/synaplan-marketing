<?php

declare(strict_types=1);

namespace Plugin\Marketeer\Service;

/**
 * Builds AI prompts for landing page generation, keyword lists, and image descriptions.
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

    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     */
    public function buildLandingPagePrompt(array $campaign, array $config, string $language): string
    {
        $langName = self::LANGUAGE_NAMES[$language] ?? $language;
        $brandName = $config['brand_name'] ?? 'Synaplan';
        $ctaUrl = $campaign['cta_url'] ?? $config['cta_url'] ?? 'https://web.synaplan.com';

        return <<<PROMPT
You are an expert marketing copywriter and web developer. Generate a complete, production-ready landing page as a single HTML file.

Requirements:
- Language: {$langName}
- Brand: {$brandName}
- CTA URL: {$ctaUrl}
- The page must be a self-contained HTML file (inline CSS, no external dependencies except Google Fonts)
- Mobile-first responsive design
- Fast loading (no heavy JS frameworks)
- Professional, modern, conversion-optimized layout
- Include: hero section with headline & subheadline, key benefits section, features with icons (use Unicode or SVG), testimonial/social proof placeholder, clear CTA buttons linking to the registration URL
- Use a clean color scheme appropriate for a tech/SaaS product
- Include Open Graph meta tags for social sharing
- Include a <title> and <meta description> optimized for SEO

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

    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
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
            default => '1920x1080 pixels (hero banner)',
        };

        return "Create a professional, modern marketing image for {$brandName}. "
            . "Theme: {$title}. "
            . "Style: Clean, tech-forward design with gradients and abstract shapes. "
            . "Dimensions: {$dimensions}. "
            . "Do NOT include any text in the image — text will be overlaid separately.";
    }

    /**
     * @param array<string, mixed>      $campaign
     * @param array<string, mixed>      $config
     * @param array<string, mixed>|null $existingPage
     *
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
        $langName = self::LANGUAGE_NAMES[$language] ?? $language;

        if ($target === 'html' && $existingPage !== null) {
            return [
                ['role' => 'system', 'content' => $this->buildLandingPagePrompt($campaign, $config, $language)],
                ['role' => 'assistant', 'content' => $existingPage['html'] ?? ''],
                ['role' => 'user', 'content' => "Please modify the landing page with these changes: {$refinementPrompt}\n\nOutput ONLY the complete updated HTML. No markdown, no explanations."],
            ];
        }

        return $this->buildKeywordPrompt($campaign, $config, $language, 50, $refinementPrompt);
    }

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
}
