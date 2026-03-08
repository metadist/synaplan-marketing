<?php

declare(strict_types=1);

namespace Plugin\Marketeer\Service;

use App\Service\PluginDataService;

/**
 * Manages ad copy and social media post storage via PluginDataService.
 *
 * Data types in plugin_data:
 *   - ad_copy: Google Ads RSA copy (headlines, descriptions, sitelinks)
 *   - social_post: Social media posts (LinkedIn, Instagram, Discord)
 *   - collateral: Banner text, icon descriptions, misc marketing assets
 */
final readonly class AdCopyService
{
    private const PLUGIN_NAME = 'marketeer';
    private const TYPE_AD_COPY = 'ad_copy';
    private const TYPE_SOCIAL_POST = 'social_post';
    private const TYPE_COLLATERAL = 'collateral';

    public function __construct(
        private PluginDataService $pluginData,
    ) {
    }

    // --- Ad Copy (Google Ads RSA) ---

    /**
     * @param array<string, mixed> $adCopyData
     */
    public function saveAdCopy(
        int $userId,
        string $campaignSlug,
        string $language,
        array $adCopyData,
    ): string {
        $key = $this->adCopyKey($campaignSlug, $language);
        $data = array_merge($adCopyData, [
            'campaign_slug' => $campaignSlug,
            'platform' => 'google',
            'language' => $language,
            'updated_at' => (new \DateTimeImmutable())->format('c'),
        ]);

        $this->pluginData->set($userId, self::PLUGIN_NAME, self::TYPE_AD_COPY, $key, $data);

        return $key;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAdCopy(int $userId, string $campaignSlug, string $language): ?array
    {
        return $this->pluginData->get(
            $userId,
            self::PLUGIN_NAME,
            self::TYPE_AD_COPY,
            $this->adCopyKey($campaignSlug, $language),
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function listAdCopy(int $userId, string $campaignSlug): array
    {
        $all = $this->pluginData->list($userId, self::PLUGIN_NAME, self::TYPE_AD_COPY);
        $prefix = $campaignSlug . '_google_';

        return array_filter($all, fn (mixed $v, string $k) => str_starts_with($k, $prefix), ARRAY_FILTER_USE_BOTH);
    }

    public function deleteAdCopy(int $userId, string $campaignSlug, string $language): bool
    {
        return $this->pluginData->delete(
            $userId,
            self::PLUGIN_NAME,
            self::TYPE_AD_COPY,
            $this->adCopyKey($campaignSlug, $language),
        );
    }

    // --- Social Posts (LinkedIn, Instagram, Discord) ---

    /**
     * @param array<string, mixed> $postData
     */
    public function saveSocialPost(
        int $userId,
        string $campaignSlug,
        string $platform,
        string $language,
        array $postData,
    ): string {
        $key = $this->socialPostKey($campaignSlug, $platform, $language);
        $data = array_merge($postData, [
            'campaign_slug' => $campaignSlug,
            'platform' => $platform,
            'language' => $language,
            'updated_at' => (new \DateTimeImmutable())->format('c'),
        ]);

        $this->pluginData->set($userId, self::PLUGIN_NAME, self::TYPE_SOCIAL_POST, $key, $data);

        return $key;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSocialPost(
        int $userId,
        string $campaignSlug,
        string $platform,
        string $language,
    ): ?array {
        return $this->pluginData->get(
            $userId,
            self::PLUGIN_NAME,
            self::TYPE_SOCIAL_POST,
            $this->socialPostKey($campaignSlug, $platform, $language),
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function listSocialPosts(int $userId, string $campaignSlug): array
    {
        $all = $this->pluginData->list($userId, self::PLUGIN_NAME, self::TYPE_SOCIAL_POST);
        $prefix = $campaignSlug . '_';

        return array_filter($all, fn (mixed $v, string $k) => str_starts_with($k, $prefix), ARRAY_FILTER_USE_BOTH);
    }

    public function deleteSocialPost(
        int $userId,
        string $campaignSlug,
        string $platform,
        string $language,
    ): bool {
        return $this->pluginData->delete(
            $userId,
            self::PLUGIN_NAME,
            self::TYPE_SOCIAL_POST,
            $this->socialPostKey($campaignSlug, $platform, $language),
        );
    }

    // --- Collaterals (banners, icons, misc) ---

    /**
     * @param array<string, mixed> $collateralData
     */
    public function saveCollateral(
        int $userId,
        string $campaignSlug,
        string $collateralType,
        string $language,
        array $collateralData,
    ): string {
        $key = "{$campaignSlug}_{$collateralType}_{$language}";
        $data = array_merge($collateralData, [
            'campaign_slug' => $campaignSlug,
            'type' => $collateralType,
            'language' => $language,
            'updated_at' => (new \DateTimeImmutable())->format('c'),
        ]);

        $this->pluginData->set($userId, self::PLUGIN_NAME, self::TYPE_COLLATERAL, $key, $data);

        return $key;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function listCollaterals(int $userId, string $campaignSlug): array
    {
        $all = $this->pluginData->list($userId, self::PLUGIN_NAME, self::TYPE_COLLATERAL);
        $prefix = $campaignSlug . '_';

        return array_filter($all, fn (mixed $v, string $k) => str_starts_with($k, $prefix), ARRAY_FILTER_USE_BOTH);
    }

    public function deleteCollateral(int $userId, string $key): bool
    {
        return $this->pluginData->delete($userId, self::PLUGIN_NAME, self::TYPE_COLLATERAL, $key);
    }

    /**
     * Delete all ad copy, social posts, and collaterals for a campaign.
     */
    public function deleteAllForCampaign(int $userId, string $campaignSlug): int
    {
        $deleted = 0;

        foreach ([self::TYPE_AD_COPY, self::TYPE_SOCIAL_POST, self::TYPE_COLLATERAL] as $type) {
            $all = $this->pluginData->list($userId, self::PLUGIN_NAME, $type);
            $prefix = $campaignSlug . '_';
            foreach ($all as $key => $data) {
                if (str_starts_with($key, $prefix)) {
                    $this->pluginData->delete($userId, self::PLUGIN_NAME, $type, $key);
                    ++$deleted;
                }
            }
        }

        return $deleted;
    }

    private function adCopyKey(string $campaignSlug, string $language): string
    {
        return "{$campaignSlug}_google_{$language}";
    }

    private function socialPostKey(string $campaignSlug, string $platform, string $language): string
    {
        return "{$campaignSlug}_{$platform}_{$language}";
    }
}
