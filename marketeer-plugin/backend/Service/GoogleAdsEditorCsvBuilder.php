<?php

declare(strict_types=1);

namespace Plugin\Marketeer\Service;

/**
 * Builds a Google Ads Editor-compatible TSV from a campaign data array.
 *
 * Produces tab-separated values matching the native format that Google Ads
 * Editor uses for export/import. Includes: campaign row, ad group rows,
 * keyword rows (with match types), negative keywords (campaign + ad group),
 * and Responsive Search Ad rows with up to 15 headlines and 4 descriptions.
 *
 * @see https://support.google.com/google-ads/editor/answer/57747 CSV column reference
 * @see https://support.google.com/google-ads/editor/answer/56368 Prepare a CSV file
 */
final class GoogleAdsEditorCsvBuilder
{
    private const COL_CAMPAIGN = 0;
    private const COL_CAMPAIGN_TYPE = 1;
    private const COL_BUDGET = 2;
    private const COL_BUDGET_TYPE = 3;
    private const COL_NETWORKS = 4;
    private const COL_LANGUAGES = 5;
    private const COL_BID_STRATEGY_TYPE = 6;
    private const COL_CAMPAIGN_STATUS = 7;
    private const COL_AD_GROUP = 8;
    private const COL_AD_GROUP_STATUS = 9;
    private const COL_KEYWORD = 10;
    private const COL_TYPE = 11;
    private const COL_HEADLINE_START = 12;
    private const COL_DESCRIPTION_START = 27;
    private const COL_FINAL_URL = 31;
    private const COL_PATH_1 = 32;
    private const COL_PATH_2 = 33;
    private const COL_STATUS = 34;

    private const HEADERS = [
        'Campaign',
        'Campaign Type',
        'Budget',
        'Budget type',
        'Networks',
        'Languages',
        'Bid Strategy Type',
        'Campaign Status',
        'Ad Group',
        'Ad Group Status',
        'Keyword',
        'Type',
        'Headline 1',
        'Headline 2',
        'Headline 3',
        'Headline 4',
        'Headline 5',
        'Headline 6',
        'Headline 7',
        'Headline 8',
        'Headline 9',
        'Headline 10',
        'Headline 11',
        'Headline 12',
        'Headline 13',
        'Headline 14',
        'Headline 15',
        'Description 1',
        'Description 2',
        'Description 3',
        'Description 4',
        'Final URL',
        'Path 1',
        'Path 2',
        'Status',
    ];

    private const CAMPAIGN_TYPE_MAP = [
        'search' => 'Search',
        'display' => 'Display',
        'performance_max' => 'Performance Max',
        'shopping' => 'Shopping',
        'video' => 'Video',
    ];

    /**
     * Build a complete Google Ads Editor TSV string from campaign data.
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
        $rawType = strtolower((string) ($adsCampaign['campaign_type'] ?? 'search'));
        $campaignType = self::CAMPAIGN_TYPE_MAP[$rawType] ?? 'Search';

        $rows = [self::HEADERS];

        $rows[] = self::campaignRow($campName, $campaignType, $budget, $bidding, $language);

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

        $lines = array_map([self::class, 'encodeTsvRow'], $rows);
        $tsv = implode("\r\n", $lines) . "\r\n";

        return "\xEF\xBB\xBF" . $tsv;
    }

    /**
     * @return string[]
     */
    private static function campaignRow(string $name, string $type, string $budget, string $bidding, string $lang): array
    {
        $row = self::emptyRow();
        $row[self::COL_CAMPAIGN] = $name;
        $row[self::COL_CAMPAIGN_TYPE] = $type;
        $row[self::COL_BUDGET] = $budget;
        $row[self::COL_BUDGET_TYPE] = 'Daily';
        $row[self::COL_NETWORKS] = 'Google Search;Search Partners';
        $row[self::COL_LANGUAGES] = $lang;
        $row[self::COL_BID_STRATEGY_TYPE] = $bidding;
        $row[self::COL_CAMPAIGN_STATUS] = 'Paused';

        return $row;
    }

    /**
     * @return string[]
     */
    private static function adGroupRow(string $campName, string $groupName): array
    {
        $row = self::emptyRow();
        $row[self::COL_CAMPAIGN] = $campName;
        $row[self::COL_AD_GROUP] = $groupName;
        $row[self::COL_AD_GROUP_STATUS] = 'Enabled';

        return $row;
    }

    /**
     * @return string[]
     */
    private static function keywordRow(string $campName, string $groupName, string $keyword, string $matchType): array
    {
        $row = self::emptyRow();
        $row[self::COL_CAMPAIGN] = $campName;
        $row[self::COL_AD_GROUP] = $groupName;
        $row[self::COL_KEYWORD] = $keyword;
        $row[self::COL_TYPE] = ucfirst(strtolower($matchType));
        $row[self::COL_STATUS] = 'Enabled';

        return $row;
    }

    /**
     * @return string[]
     */
    private static function negativeKeywordRow(string $campName, string $groupName, string $keyword): array
    {
        $row = self::emptyRow();
        $row[self::COL_CAMPAIGN] = $campName;
        $row[self::COL_AD_GROUP] = $groupName;
        $row[self::COL_KEYWORD] = $keyword;
        $row[self::COL_TYPE] = 'Negative';

        return $row;
    }

    /**
     * @return string[]
     */
    private static function campaignNegativeRow(string $campName, string $keyword): array
    {
        $row = self::emptyRow();
        $row[self::COL_CAMPAIGN] = $campName;
        $row[self::COL_KEYWORD] = $keyword;
        $row[self::COL_TYPE] = 'Campaign negative';

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
        $row[self::COL_CAMPAIGN] = $campName;
        $row[self::COL_AD_GROUP] = $groupName;

        $headlineCount = min(15, count($headlines));
        for ($i = 0; $i < $headlineCount; $i++) {
            $row[self::COL_HEADLINE_START + $i] = (string) $headlines[$i];
        }

        $descCount = min(4, count($descriptions));
        for ($i = 0; $i < $descCount; $i++) {
            $row[self::COL_DESCRIPTION_START + $i] = (string) $descriptions[$i];
        }

        $row[self::COL_FINAL_URL] = $finalUrl;
        $row[self::COL_PATH_1] = (string) ($displayPath[0] ?? '');
        $row[self::COL_PATH_2] = (string) ($displayPath[1] ?? '');
        $row[self::COL_STATUS] = 'Enabled';

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
     * Encode a row as tab-separated values.
     * Fields containing tabs, newlines, or double quotes are enclosed per RFC 4180.
     *
     * @param string[] $fields
     */
    private static function encodeTsvRow(array $fields): string
    {
        $encoded = [];
        foreach ($fields as $field) {
            $field = (string) $field;
            if (str_contains($field, "\t") || str_contains($field, "\n") || str_contains($field, "\r") || str_contains($field, '"')) {
                $encoded[] = '"' . str_replace('"', '""', $field) . '"';
            } else {
                $encoded[] = $field;
            }
        }

        return implode("\t", $encoded);
    }
}
