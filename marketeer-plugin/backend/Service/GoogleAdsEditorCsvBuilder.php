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
    private const MAX_HEADLINE_LENGTH = 30;
    private const COL_CAMPAIGN = 0;
    private const COL_CAMPAIGN_TYPE = 1;
    private const COL_BUDGET = 2;
    private const COL_BUDGET_TYPE = 3;
    private const COL_NETWORKS = 4;
    private const COL_LANGUAGES = 5;
    private const COL_BID_STRATEGY_TYPE = 6;
    private const COL_CAMPAIGN_STATUS = 7;
    private const COL_AD_GROUP = 8;
    private const COL_AD_GROUP_TYPE = 9;
    private const COL_MAX_CPC = 10;
    private const COL_AD_GROUP_STATUS = 11;
    private const COL_KEYWORD = 12;
    private const COL_CRITERION_TYPE = 13;
    private const COL_HEADLINE_START = 14;
    private const COL_DESCRIPTION_START = 29;
    private const COL_FINAL_URL = 33;
    private const COL_PATH_1 = 34;
    private const COL_PATH_2 = 35;
    private const COL_LINK_TEXT = 36;
    private const COL_DESC_LINE_1 = 37;
    private const COL_DESC_LINE_2 = 38;
    private const COL_CALLOUT_TEXT = 39;
    private const COL_HEADER = 40;
    private const COL_SNIPPET_VALUES = 41;
    private const COL_EU_POLITICAL_ADS = 42;
    private const COL_STATUS = 43;

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
        'Ad Group Type',
        'Max CPC',
        'Ad Group Status',
        'Keyword',
        'Criterion Type',
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
        'Link Text',
        'Description Line 1',
        'Description Line 2',
        'Callout text',
        'Header',
        'Snippet Values',
        'Contains EU political advertising',
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
        $containsEuPoliticalAds = (bool) ($adsCampaign['contains_eu_political_advertising'] ?? false);

        $rows = [self::HEADERS];

        $rows[] = self::campaignRow($campName, $campaignType, $budget, $bidding, $language, $containsEuPoliticalAds);

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

        $extensions = $adsCampaign['extensions_suggestions'] ?? [];
        if (!empty($extensions['sitelinks'])) {
            foreach ($extensions['sitelinks'] as $sitelink) {
                $rows[] = self::sitelinkRow($campName, $sitelink);
            }
        }
        if (!empty($extensions['callouts'])) {
            foreach ($extensions['callouts'] as $callout) {
                $rows[] = self::calloutRow($campName, (string) $callout);
            }
        }
        if (!empty($extensions['structured_snippets']) && !empty($extensions['structured_snippets']['header'])) {
            $header = (string) $extensions['structured_snippets']['header'];
            $values = $extensions['structured_snippets']['values'] ?? [];
            if (!empty($values)) {
                $rows[] = self::structuredSnippetRow($campName, $header, $values);
            }
        }

        $fp = fopen('php://temp', 'r+');
        if ($fp === false) {
            return '';
        }

        // Write UTF-8 BOM for Excel/Google Ads Editor compatibility
        fwrite($fp, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            fputcsv($fp, $row, ',', '"', "\\");
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv !== false ? $csv : '';
    }

    /**
     * @return string[]
     */
    private static function campaignRow(
        string $name,
        string $type,
        string $budget,
        string $bidding,
        string $lang,
        bool $containsEuPoliticalAds,
    ): array
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
        $row[self::COL_EU_POLITICAL_ADS] = self::formatEuPoliticalAdsDeclaration($containsEuPoliticalAds);

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
        $row[self::COL_AD_GROUP_TYPE] = 'Default';
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
        $row[self::COL_KEYWORD] = self::formatKeywordWithMatchType($keyword, $matchType);
        $row[self::COL_CRITERION_TYPE] = ucfirst(strtolower($matchType));
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
        $row[self::COL_CRITERION_TYPE] = 'Negative';

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
        $row[self::COL_CRITERION_TYPE] = 'Campaign negative';

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
            $headline = (string) $headlines[$i];
            if (mb_strlen($headline) > self::MAX_HEADLINE_LENGTH) {
                throw new \InvalidArgumentException(sprintf(
                    'Headline %d exceeds %d characters (%d): "%s"',
                    $i + 1,
                    self::MAX_HEADLINE_LENGTH,
                    mb_strlen($headline),
                    $headline,
                ));
            }

            $row[self::COL_HEADLINE_START + $i] = $headline;
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
     * Format keyword text with match type notation so it matches the Criterion Type column.
     * Broad = plain text, Phrase = "quoted", Exact = [bracketed].
     */
    private static function formatKeywordWithMatchType(string $keyword, string $matchType): string
    {
        return $keyword;
    }

    private static function formatEuPoliticalAdsDeclaration(bool $containsEuPoliticalAds): string
    {
        return $containsEuPoliticalAds ? 'Yes' : 'No';
    }

    /**
     * @return string[]
     */
    private static function emptyRow(): array
    {
        return array_fill(0, count(self::HEADERS), '');
    }

    /**
     * @param array<string, mixed> $sitelink
     *
     * @return string[]
     */
    private static function sitelinkRow(string $campName, array $sitelink): array
    {
        $row = self::emptyRow();
        $row[self::COL_CAMPAIGN] = $campName;
        $row[self::COL_LINK_TEXT] = (string) ($sitelink['title'] ?? '');
        $row[self::COL_FINAL_URL] = (string) ($sitelink['url'] ?? '');
        $row[self::COL_DESC_LINE_1] = (string) ($sitelink['description_1'] ?? $sitelink['description'] ?? '');
        $row[self::COL_DESC_LINE_2] = (string) ($sitelink['description_2'] ?? '');
        $row[self::COL_STATUS] = 'Enabled';

        return $row;
    }

    /**
     * @return string[]
     */
    private static function calloutRow(string $campName, string $callout): array
    {
        $row = self::emptyRow();
        $row[self::COL_CAMPAIGN] = $campName;
        $row[self::COL_CALLOUT_TEXT] = $callout;
        $row[self::COL_STATUS] = 'Enabled';

        return $row;
    }

    /**
     * @param string[] $values
     *
     * @return string[]
     */
    private static function structuredSnippetRow(string $campName, string $header, array $values): array
    {
        $row = self::emptyRow();
        $row[self::COL_CAMPAIGN] = $campName;
        $row[self::COL_HEADER] = $header;
        $row[self::COL_SNIPPET_VALUES] = implode(';', $values);
        $row[self::COL_STATUS] = 'Enabled';

        return $row;
    }
}
