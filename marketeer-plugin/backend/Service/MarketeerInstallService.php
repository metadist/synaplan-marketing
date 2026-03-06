<?php

declare(strict_types=1);

namespace Plugin\Marketeer\Service;

use App\Service\PluginDataService;

/**
 * Seeds default data when the Marketeer plugin is installed for a user.
 */
final readonly class MarketeerInstallService
{
    private const PLUGIN_NAME = 'marketeer';
    private const DATA_TYPE_CAMPAIGN = 'campaign';

    public function __construct(
        private PluginDataService $pluginData,
    ) {
    }

    public function seedDefaults(int $userId): void
    {
        if ($this->pluginData->count($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN) > 0) {
            return;
        }

        $this->pluginData->set($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, 'any-model', [
            'title' => 'Use Any AI Model — No Vendor Lock-in',
            'topic' => 'Synaplan allows switching between AI models (OpenAI, Anthropic, Ollama, Gemini, Groq) freely. '
                . 'Highlight freedom from vendor lock-in, cost optimization, and privacy with local models.',
            'languages' => ['en', 'de'],
            'cta_url' => 'https://web.synaplan.com',
            'status' => 'draft',
            'sort_order' => 0,
            'created_at' => (new \DateTimeImmutable())->format('c'),
            'updated_at' => (new \DateTimeImmutable())->format('c'),
        ]);
    }

    public function userHasCampaigns(int $userId): bool
    {
        return $this->pluginData->count($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN) > 0;
    }
}
