<?php

declare(strict_types=1);

namespace Plugin\Marketeer\Service;

use App\Service\PluginDataService;

/**
 * Manages Google Ads campaign planning structures via PluginDataService.
 *
 * This is a planning/management tool — it stores campaign structures
 * (ad groups, keywords, ads) that can be exported for manual import
 * or future API integration with Google Ads.
 *
 * Data type: ads_campaign
 * Key pattern: {campaignSlug}_{adsCampaignId}
 */
final readonly class GoogleAdsPlannerService
{
    private const PLUGIN_NAME = 'marketeer';
    private const DATA_TYPE = 'ads_campaign';
    private const DEFAULT_EU_POLITICAL_ADS = false;
    private const MAX_HEADLINE_LENGTH = 30;

    public function __construct(
        private PluginDataService $pluginData,
    ) {
    }

    /**
     * @param array<string, mixed> $campaignData
     */
    public function create(int $userId, string $campaignSlug, array $campaignData): string
    {
        $id = $this->generateId();
        $key = "{$campaignSlug}_{$id}";

        $data = array_merge($this->normalizeCampaignData($campaignData), [
            'id' => $id,
            'campaign_slug' => $campaignSlug,
            'status' => $campaignData['status'] ?? 'draft',
            'created_at' => (new \DateTimeImmutable())->format('c'),
            'updated_at' => (new \DateTimeImmutable())->format('c'),
        ]);

        $this->pluginData->set($userId, self::PLUGIN_NAME, self::DATA_TYPE, $key, $data);

        return $id;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(int $userId, string $campaignSlug, string $adsCampaignId): ?array
    {
        return $this->pluginData->get(
            $userId,
            self::PLUGIN_NAME,
            self::DATA_TYPE,
            "{$campaignSlug}_{$adsCampaignId}",
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function listForCampaign(int $userId, string $campaignSlug): array
    {
        $all = $this->pluginData->list($userId, self::PLUGIN_NAME, self::DATA_TYPE);
        $prefix = $campaignSlug . '_';
        $result = [];

        foreach ($all as $key => $data) {
            if (str_starts_with($key, $prefix)) {
                $result[$key] = $data;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $updates
     * @return array<string, mixed>|null
     */
    public function update(
        int $userId,
        string $campaignSlug,
        string $adsCampaignId,
        array $updates,
    ): ?array {
        $key = "{$campaignSlug}_{$adsCampaignId}";
        $existing = $this->pluginData->get($userId, self::PLUGIN_NAME, self::DATA_TYPE, $key);

        if ($existing === null) {
            return null;
        }

        $allowedFields = [
            'campaign_name', 'campaign_type', 'bidding_strategy',
            'daily_budget_suggestion', 'target_locations', 'ad_groups',
            'campaign_negative_keywords', 'extensions_suggestions',
            'best_practices_notes', 'status', 'language',
            'contains_eu_political_advertising',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $updates)) {
                $existing[$field] = $updates[$field];
            }
        }

        $existing = $this->normalizeCampaignData($existing);

        $existing['updated_at'] = (new \DateTimeImmutable())->format('c');

        $this->pluginData->set($userId, self::PLUGIN_NAME, self::DATA_TYPE, $key, $existing);

        return $existing;
    }

    public function delete(int $userId, string $campaignSlug, string $adsCampaignId): bool
    {
        return $this->pluginData->delete(
            $userId,
            self::PLUGIN_NAME,
            self::DATA_TYPE,
            "{$campaignSlug}_{$adsCampaignId}",
        );
    }

    /**
     * Delete all ads campaigns for a specific language within a marketing campaign.
     */
    public function deleteForLanguage(int $userId, string $campaignSlug, string $language): int
    {
        $all = $this->listForCampaign($userId, $campaignSlug);
        $deleted = 0;

        foreach ($all as $key => $data) {
            if (($data['language'] ?? '') === $language) {
                $this->pluginData->delete($userId, self::PLUGIN_NAME, self::DATA_TYPE, $key);
                ++$deleted;
            }
        }

        return $deleted;
    }

    /**
     * Delete all ads campaigns for a marketing campaign.
     */
    public function deleteAllForCampaign(int $userId, string $campaignSlug): int
    {
        $all = $this->listForCampaign($userId, $campaignSlug);
        $deleted = 0;

        foreach (array_keys($all) as $key) {
            $this->pluginData->delete($userId, self::PLUGIN_NAME, self::DATA_TYPE, $key);
            ++$deleted;
        }

        return $deleted;
    }

    /**
     * Export a campaign structure as a flat keyword list for Google Ads import.
     *
     * @return array{keywords: string[], negative_keywords: string[]}
     */
    public function exportKeywords(int $userId, string $campaignSlug, string $adsCampaignId): array
    {
        $campaign = $this->get($userId, $campaignSlug, $adsCampaignId);
        if ($campaign === null) {
            return ['keywords' => [], 'negative_keywords' => []];
        }

        $keywords = [];
        $negatives = [];

        foreach ($campaign['ad_groups'] ?? [] as $group) {
            foreach ($group['keywords'] ?? [] as $kw) {
                $keyword = is_array($kw) ? ($kw['keyword'] ?? '') : (string) $kw;
                $matchType = is_array($kw) ? ($kw['match_type'] ?? 'broad') : 'broad';

                if ($keyword === '') {
                    continue;
                }

                $formatted = match ($matchType) {
                    'exact' => "[{$keyword}]",
                    'phrase' => "\"{$keyword}\"",
                    default => $keyword,
                };

                $keywords[] = $formatted;
            }

            foreach ($group['negative_keywords'] ?? [] as $neg) {
                $negatives[] = is_array($neg) ? ($neg['keyword'] ?? $neg) : (string) $neg;
            }
        }

        foreach ($campaign['campaign_negative_keywords'] ?? [] as $neg) {
            $negatives[] = is_array($neg) ? ($neg['keyword'] ?? $neg) : (string) $neg;
        }

        return [
            'keywords' => array_values(array_unique($keywords)),
            'negative_keywords' => array_values(array_unique($negatives)),
        ];
    }

    private function generateId(): string
    {
        return substr(bin2hex(random_bytes(6)), 0, 12);
    }

    /**
     * @param array<string, mixed> $campaignData
     * @return array<string, mixed>
     */
    private function normalizeCampaignData(array $campaignData): array
    {
        if (!array_key_exists('contains_eu_political_advertising', $campaignData)) {
            $campaignData['contains_eu_political_advertising'] = self::DEFAULT_EU_POLITICAL_ADS;
        }

        $campaignData['contains_eu_political_advertising'] = (bool) $campaignData['contains_eu_political_advertising'];
        $this->validateCampaignData($campaignData);

        return $campaignData;
    }

    /**
     * @param array<string, mixed> $campaignData
     */
    private function validateCampaignData(array $campaignData): void
    {
        foreach ($campaignData['ad_groups'] ?? [] as $groupIndex => $group) {
            if (!is_array($group)) {
                continue;
            }

            foreach ($group['ads'] ?? [] as $adIndex => $ad) {
                if (!is_array($ad)) {
                    continue;
                }

                foreach ($ad['headlines'] ?? [] as $headlineIndex => $headline) {
                    $headlineText = (string) $headline;
                    if (mb_strlen($headlineText) <= self::MAX_HEADLINE_LENGTH) {
                        continue;
                    }

                    throw new \InvalidArgumentException(sprintf(
                        'Headline %d in ad %d of ad group %d exceeds %d characters (%d): "%s"',
                        $headlineIndex + 1,
                        $adIndex + 1,
                        $groupIndex + 1,
                        self::MAX_HEADLINE_LENGTH,
                        mb_strlen($headlineText),
                        $headlineText,
                    ));
                }
            }
        }
    }
}
