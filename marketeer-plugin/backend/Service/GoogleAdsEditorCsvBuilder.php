<?php

declare(strict_types=1);

namespace Plugin\Marketeer\Service;

/**
 * Builds a Google Ads Editor-compatible CSV from a campaign data array.
 *
 * Produces a single CSV per language that can be imported directly into
 * Google Ads Editor without errors. Includes: campaign row, ad group rows,
 * keyword rows (with match types), negative keywords (campaign + ad group),
 * and Responsive Search Ad rows with headlines, descriptions, and paths.
 *
 * @see https://support.google.com/google-ads/editor/answer/57747 CSV column reference
 * @see https://support.google.com/google-ads/editor/answer/56368 Prepare a CSV file
 */
final class GoogleAdsEditorCsvBuilder
{
    private const HEADERS = [
        'Campaign',
        'Campaign Type',
        'Budget',
        'Bid Strategy Type',
        'Networks',
        'Languages',
        'Campaign Status',
        'Ad Group',
        'Ad Group Status',
        'Keyword',
        'Type',
        'Headline 1',
        'Headline 2',
        'Headline 3',
        'Description 1',
        'Description 2',
        'Final URL',
        'Path 1',
        'Path 2',
        'Status',
    ];

    /**
     * Build a complete Google Ads Editor CSV string from campaign data.
     *
     * @param array<string, mixed> $adsCampaign Campaign data array with keys:
     *                                          campaign_name, campaign_type, bidding_strategy, daily_budget_suggestion,
     *                                          language, ad_groups (each with name/ad_group_name, keywords, negative_keywords, ads),
     *                                          campaign_negative_keywords
     */
    public static function build(array $adsCampaign): string
    {
        $campName = (string) ($adsCampaign['campaign_name'] ?? 'Unnamed Campaign');
        $budget = (string) ($adsCampaign['daily_budget_suggestion'] ?? '');
        $bidding = (string) ($adsCampaign['bidding_strategy'] ?? 'Maximize Conversions');
        $language = (string) ($adsCampaign['language'] ?? 'en');

        $rows = [self::HEADERS];

        $rows[] = self::campaignRow($campName, $budget, $bidding, $language);

        foreach ($adsCampaign['ad_groups'] ?? [] as $group) {
            $groupName = (string) ($group['name'] ?? $group['ad_group_name'] ?? 'Default');

            $rows[] = self::adGroupRow($campName, $groupName);

            foreach ($group['keywords'] ?? [] as $kw) {
                $keyword = is_array($kw) ? ($kw['keyword'] ?? '') : (string) $kw;
                $matchType = is_array($kw) ? ($kw['match_type'] ?? 'broad') : 'broad';
                if ('' !== $keyword) {
                    $rows[] = self::keywordRow($campName, $groupName, $keyword, $matchType);
                }
            }

            foreach ($group['negative_keywords'] ?? [] as $neg) {
                $negKeyword = is_array($neg) ? ($neg['keyword'] ?? (string) $neg) : (string) $neg;
                if ('' !== $negKeyword) {
                    $rows[] = self::negativeKeywordRow($campName, $groupName, $negKeyword);
                }
            }

            foreach ($group['ads'] ?? [] as $ad) {
                $rows[] = self::adRow($campName, $groupName, $ad);
            }
        }

        foreach ($adsCampaign['campaign_negative_keywords'] ?? [] as $neg) {
            $negKeyword = is_array($neg) ? ($neg['keyword'] ?? (string) $neg) : (string) $neg;
            if ('' !== $negKeyword) {
                $rows[] = self::campaignNegativeRow($campName, $negKeyword);
            }
        }

        $lines = array_map([self::class, 'encodeCsvRow'], $rows);

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * @return string[]
     */
    private static function campaignRow(string $name, string $budget, string $bidding, string $lang): array
    {
        $row = self::emptyRow();
        $row[0] = $name;                                       // Campaign
        $row[1] = 'Search';                                    // Campaign Type
        $row[2] = $budget;                                     // Budget
        $row[3] = $bidding;                                    // Bid Strategy Type
        $row[4] = 'Google Search;Search Partners';             // Networks
        $row[5] = $lang;                                       // Languages
        $row[6] = 'Paused';                                    // Campaign Status

        return $row;
    }

    /**
     * @return string[]
     */
    private static function adGroupRow(string $campName, string $groupName): array
    {
        $row = self::emptyRow();
        $row[0] = $campName;     // Campaign
        $row[7] = $groupName;    // Ad Group
        $row[8] = 'Enabled';    // Ad Group Status

        return $row;
    }

    /**
     * @return string[]
     */
    private static function keywordRow(string $campName, string $groupName, string $keyword, string $matchType): array
    {
        $row = self::emptyRow();
        $row[0] = $campName;                  // Campaign
        $row[7] = $groupName;                 // Ad Group
        $row[9] = $keyword;                   // Keyword
        $row[10] = ucfirst(strtolower($matchType)); // Type (Broad/Phrase/Exact)
        $row[19] = 'Enabled';                // Status

        return $row;
    }

    /**
     * @return string[]
     */
    private static function negativeKeywordRow(string $campName, string $groupName, string $keyword): array
    {
        $row = self::emptyRow();
        $row[0] = $campName;       // Campaign
        $row[7] = $groupName;      // Ad Group
        $row[9] = $keyword;        // Keyword
        $row[10] = 'Negative';    // Type

        return $row;
    }

    /**
     * @return string[]
     */
    private static function campaignNegativeRow(string $campName, string $keyword): array
    {
        $row = self::emptyRow();
        $row[0] = $campName;               // Campaign
        $row[9] = $keyword;                // Keyword
        $row[10] = 'Campaign negative';   // Type

        return $row;
    }

    /**
     * @param array<string, mixed> $ad
     *
     * @return string[]
     */
    private static function adRow(string $campName, string $groupName, array $ad): array
    {
        $headlines = $ad['headlines'] ?? [];
        $descriptions = $ad['descriptions'] ?? [];
        $finalUrl = (string) ($ad['final_url'] ?? $ad['finalUrl'] ?? '');
        $displayPath = $ad['display_path'] ?? [];

        $row = self::emptyRow();
        $row[0] = $campName;                     // Campaign
        $row[7] = $groupName;                    // Ad Group
        $row[11] = (string) ($headlines[0] ?? '');  // Headline 1
        $row[12] = (string) ($headlines[1] ?? '');  // Headline 2
        $row[13] = (string) ($headlines[2] ?? '');  // Headline 3
        $row[14] = (string) ($descriptions[0] ?? ''); // Description 1
        $row[15] = (string) ($descriptions[1] ?? ''); // Description 2
        $row[16] = $finalUrl;                    // Final URL
        $row[17] = (string) ($displayPath[0] ?? ''); // Path 1
        $row[18] = (string) ($displayPath[1] ?? ''); // Path 2
        $row[19] = 'Enabled';                   // Status

        return $row;
    }

    /**
     * @return string[]
     */
    private static function emptyRow(): array
    {
        return array_fill(0, count(self::HEADERS), '');
    }

    /**
     * @param string[] $fields
     */
    private static function encodeCsvRow(array $fields): string
    {
        $handle = fopen('php://temp', 'r+');
        assert(false !== $handle);
        fputcsv($handle, $fields);
        rewind($handle);
        $line = stream_get_contents($handle);
        fclose($handle);

        return rtrim((string) $line, "\n\r");
    }
}
