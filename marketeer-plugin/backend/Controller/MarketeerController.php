<?php

declare(strict_types=1);

namespace Plugin\Marketeer\Controller;

use App\AI\Service\AiFacade;
use App\Entity\User;
use App\Repository\ConfigRepository;
use App\Service\ModelConfigService;
use App\Service\PluginDataService;
use OpenApi\Attributes as OA;
use Plugin\Marketeer\Service\ContentGenerator;
use Plugin\Marketeer\Service\LandingPageService;
use Plugin\Marketeer\Service\MarketeerInstallService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/v1/user/{userId}/plugins/marketeer', name: 'api_plugin_marketeer_')]
#[OA\Tag(name: 'Marketeer Plugin')]
class MarketeerController extends AbstractController
{
    private const PLUGIN_NAME = 'marketeer';
    private const CONFIG_GROUP = 'P_marketeer';
    private const DATA_TYPE_CAMPAIGN = 'campaign';
    private const DATA_TYPE_PAGE = 'page';

    public function __construct(
        private AiFacade $aiFacade,
        private ModelConfigService $modelConfigService,
        private PluginDataService $pluginData,
        private MarketeerInstallService $installService,
        private ContentGenerator $contentGenerator,
        private LandingPageService $landingPageService,
        private ConfigRepository $configRepository,
        private LoggerInterface $logger,
        private string $uploadDir,
    ) {
    }

    #[Route('/setup-check', name: 'setup_check', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/setup-check',
        summary: 'Check plugin setup status',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Setup status')]
    public function setupCheck(int $userId, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaignCount = $this->pluginData->count($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN);
        $config = $this->getPluginConfig($userId);

        return $this->json([
            'success' => true,
            'status' => 'ready',
            'checks' => [
                'plugin_installed' => true,
                'has_campaigns' => $campaignCount > 0,
            ],
            'campaigns_count' => $campaignCount,
            'config' => $config,
        ]);
    }

    #[Route('/setup', name: 'setup', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/setup',
        summary: 'Initialize plugin with example campaign',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Setup result')]
    public function setup(int $userId, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $hadCampaigns = $this->pluginData->count($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN) > 0;
        $this->installService->seedDefaults($userId);
        $count = $this->pluginData->count($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN);

        return $this->json([
            'success' => true,
            'message' => $hadCampaigns
                ? 'Campaigns already exist, no changes made'
                : "Plugin initialized with {$count} example campaign(s)",
            'campaigns_count' => $count,
        ]);
    }

    #[Route('/config', name: 'config_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/config',
        summary: 'Get plugin configuration',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Current configuration')]
    public function getConfig(int $userId, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'config' => $this->getPluginConfig($userId),
        ]);
    }

    #[Route('/config', name: 'config_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/v1/user/{userId}/plugins/marketeer/config',
        summary: 'Update plugin configuration',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'default_language', type: 'string', example: 'en'),
                new OA\Property(property: 'cta_url', type: 'string', example: 'https://web.synaplan.com'),
                new OA\Property(property: 'brand_name', type: 'string', example: 'Synaplan'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Config updated')]
    public function updateConfig(
        Request $request,
        int $userId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $allowedKeys = ['default_language', 'cta_url', 'brand_name'];
        $updated = [];

        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $data)) {
                $this->configRepository->setValue($userId, self::CONFIG_GROUP, $key, (string) $data[$key]);
                $updated[] = $key;
            }
        }

        return $this->json([
            'success' => true,
            'message' => 'Configuration updated',
            'updated' => $updated,
            'config' => $this->getPluginConfig($userId),
        ]);
    }

    // --- Campaign CRUD ---

    #[Route('/campaigns', name: 'campaigns_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns',
        summary: 'List all campaigns',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Campaign list')]
    public function listCampaigns(int $userId, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaigns = $this->pluginData->list($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN);

        $result = [];
        foreach ($campaigns as $key => $data) {
            $result[] = array_merge(['id' => $key], $data);
        }

        usort($result, fn (array $a, array $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        return $this->json([
            'success' => true,
            'campaigns' => $result,
            'count' => count($result),
        ]);
    }

    #[Route('/campaigns', name: 'campaigns_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns',
        summary: 'Create a new campaign',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['slug', 'title', 'topic'],
            properties: [
                new OA\Property(property: 'slug', type: 'string', example: 'any-model'),
                new OA\Property(property: 'title', type: 'string', example: 'Use Any AI Model'),
                new OA\Property(property: 'topic', type: 'string', example: 'Synaplan lets you switch AI models freely'),
                new OA\Property(property: 'languages', type: 'array', items: new OA\Items(type: 'string'), example: '["en","de"]'),
                new OA\Property(property: 'cta_url', type: 'string', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Campaign created')]
    public function createCampaign(
        Request $request,
        int $userId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['slug']) || empty($data['title']) || empty($data['topic'])) {
            return $this->json(
                ['success' => false, 'error' => 'slug, title, and topic are required'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower(trim($data['slug'])));

        if ($this->pluginData->exists($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $slug)) {
            return $this->json(
                ['success' => false, 'error' => "Campaign '{$slug}' already exists"],
                Response::HTTP_CONFLICT,
            );
        }

        $config = $this->getPluginConfig($userId);
        $campaign = [
            'title' => $data['title'],
            'topic' => $data['topic'],
            'languages' => $data['languages'] ?? [$config['default_language']],
            'cta_url' => $data['cta_url'] ?? $config['cta_url'],
            'status' => 'draft',
            'sort_order' => $this->pluginData->count($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN),
            'created_at' => (new \DateTimeImmutable())->format('c'),
            'updated_at' => (new \DateTimeImmutable())->format('c'),
        ];

        $this->pluginData->set($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $slug, $campaign);

        $this->landingPageService->ensureCampaignDirectories($userId, $slug, $campaign['languages']);

        $this->logger->info('Marketeer campaign created', [
            'user_id' => $userId,
            'slug' => $slug,
        ]);

        return $this->json([
            'success' => true,
            'campaign' => array_merge(['id' => $slug], $campaign),
        ], Response::HTTP_CREATED);
    }

    #[Route('/campaigns/{campaignId}', name: 'campaigns_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}',
        summary: 'Get campaign details with generated pages',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Campaign details')]
    public function getCampaign(
        int $userId,
        string $campaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaign = $this->pluginData->get($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $campaignId);
        if ($campaign === null) {
            return $this->json(['success' => false, 'error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        }

        $pages = $this->pluginData->list($userId, self::PLUGIN_NAME, self::DATA_TYPE_PAGE);
        $campaignPages = [];
        $prefix = $campaignId . '_';
        foreach ($pages as $key => $pageData) {
            if (str_starts_with($key, $prefix)) {
                $lang = substr($key, strlen($prefix));
                $campaignPages[$lang] = $pageData;
            }
        }

        $files = $this->landingPageService->listCampaignFiles($userId, $campaignId);

        return $this->json([
            'success' => true,
            'campaign' => array_merge(['id' => $campaignId], $campaign),
            'pages' => $campaignPages,
            'files' => $files,
        ]);
    }

    #[Route('/campaigns/{campaignId}', name: 'campaigns_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}',
        summary: 'Update campaign',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Campaign updated')]
    public function updateCampaign(
        Request $request,
        int $userId,
        string $campaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaign = $this->pluginData->get($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $campaignId);
        if ($campaign === null) {
            return $this->json(['success' => false, 'error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $allowedFields = ['title', 'topic', 'languages', 'cta_url', 'status'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $campaign[$field] = $data[$field];
            }
        }
        $campaign['updated_at'] = (new \DateTimeImmutable())->format('c');

        $this->pluginData->set($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $campaignId, $campaign);

        if (isset($data['languages'])) {
            $this->landingPageService->ensureCampaignDirectories($userId, $campaignId, $data['languages']);
        }

        return $this->json([
            'success' => true,
            'campaign' => array_merge(['id' => $campaignId], $campaign),
        ]);
    }

    #[Route('/campaigns/{campaignId}', name: 'campaigns_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}',
        summary: 'Delete campaign and all generated files',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Campaign deleted')]
    public function deleteCampaign(
        int $userId,
        string $campaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaign = $this->pluginData->get($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $campaignId);
        if ($campaign === null) {
            return $this->json(['success' => false, 'error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        }

        $pages = $this->pluginData->list($userId, self::PLUGIN_NAME, self::DATA_TYPE_PAGE);
        $prefix = $campaignId . '_';
        foreach ($pages as $key => $pageData) {
            if (str_starts_with($key, $prefix)) {
                $this->pluginData->delete($userId, self::PLUGIN_NAME, self::DATA_TYPE_PAGE, $key);
            }
        }

        $this->pluginData->delete($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $campaignId);
        $this->landingPageService->deleteCampaignDirectory($userId, $campaignId);

        $this->logger->info('Marketeer campaign deleted', [
            'user_id' => $userId,
            'slug' => $campaignId,
        ]);

        return $this->json(['success' => true, 'message' => "Campaign '{$campaignId}' deleted"]);
    }

    // --- Generation Endpoints ---

    #[Route('/campaigns/{campaignId}/generate', name: 'generate_page', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/generate',
        summary: 'Generate landing page HTML via AI',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'language', type: 'string', example: 'en'),
                new OA\Property(property: 'extra_instructions', type: 'string', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Generated landing page')]
    public function generatePage(
        Request $request,
        int $userId,
        string $campaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaign = $this->pluginData->get($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $campaignId);
        if ($campaign === null) {
            return $this->json(['success' => false, 'error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $config = $this->getPluginConfig($userId);
        $language = $data['language'] ?? $config['default_language'];
        $extraInstructions = $data['extra_instructions'] ?? null;

        try {
            $systemPrompt = $this->contentGenerator->buildLandingPagePrompt($campaign, $config, $language);
            $userMessage = $this->contentGenerator->buildLandingPageRequest($campaign, $language, $extraInstructions);

            $modelId = $this->modelConfigService->getDefaultModel('CHAT', $userId);
            $provider = $modelId ? $this->modelConfigService->getProviderForModel($modelId) : null;
            $modelName = $modelId ? $this->modelConfigService->getModelName($modelId) : null;

            $response = $this->aiFacade->chat(
                [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                $userId,
                [
                    'provider' => $provider,
                    'model' => $modelName,
                    'temperature' => 0.7,
                    'max_tokens' => 8000,
                ],
            );

            $html = $this->contentGenerator->extractHtml($response['content']);

            $pageKey = $campaignId . '_' . $language;
            $pageData = [
                'language' => $language,
                'html' => $html,
                'generated_at' => (new \DateTimeImmutable())->format('c'),
                'model' => $response['model'] ?? null,
                'provider' => $response['provider'] ?? null,
            ];
            $this->pluginData->set($userId, self::PLUGIN_NAME, self::DATA_TYPE_PAGE, $pageKey, $pageData);

            $this->landingPageService->saveHtmlFile($userId, $campaignId, $language, $html);

            $this->logger->info('Marketeer landing page generated', [
                'user_id' => $userId,
                'campaign' => $campaignId,
                'language' => $language,
            ]);

            return $this->json([
                'success' => true,
                'page' => $pageData,
                'file' => "marketeer/{$campaignId}/{$language}/index.html",
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Marketeer page generation failed', [
                'user_id' => $userId,
                'campaign' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Page generation failed: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/campaigns/{campaignId}/generate-keywords', name: 'generate_keywords', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/generate-keywords',
        summary: 'Generate keyword list for Google Ads',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'language', type: 'string', example: 'en'),
                new OA\Property(property: 'count', type: 'integer', example: 50),
                new OA\Property(property: 'extra_instructions', type: 'string', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Generated keywords')]
    public function generateKeywords(
        Request $request,
        int $userId,
        string $campaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaign = $this->pluginData->get($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $campaignId);
        if ($campaign === null) {
            return $this->json(['success' => false, 'error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $config = $this->getPluginConfig($userId);
        $language = $data['language'] ?? $config['default_language'];
        $count = $data['count'] ?? 50;
        $extraInstructions = $data['extra_instructions'] ?? null;

        try {
            $prompt = $this->contentGenerator->buildKeywordPrompt($campaign, $config, $language, $count, $extraInstructions);

            $modelId = $this->modelConfigService->getDefaultModel('CHAT', $userId);
            $provider = $modelId ? $this->modelConfigService->getProviderForModel($modelId) : null;
            $modelName = $modelId ? $this->modelConfigService->getModelName($modelId) : null;

            $response = $this->aiFacade->chat(
                $prompt,
                $userId,
                [
                    'provider' => $provider,
                    'model' => $modelName,
                    'temperature' => 0.5,
                    'max_tokens' => 4000,
                ],
            );

            $keywords = $this->contentGenerator->parseKeywordResponse($response['content']);

            $this->landingPageService->saveKeywordsFile($userId, $campaignId, $language, $keywords);

            $this->logger->info('Marketeer keywords generated', [
                'user_id' => $userId,
                'campaign' => $campaignId,
                'language' => $language,
                'count' => count($keywords),
            ]);

            return $this->json([
                'success' => true,
                'keywords' => $keywords,
                'count' => count($keywords),
                'file' => "marketeer/{$campaignId}/{$language}/keywords.txt",
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Marketeer keyword generation failed', [
                'user_id' => $userId,
                'campaign' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Keyword generation failed: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/campaigns/{campaignId}/generate-image', name: 'generate_image', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/generate-image',
        summary: 'Generate social sharing image',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            required: ['type'],
            properties: [
                new OA\Property(property: 'type', type: 'string', enum: ['hero', 'linkedin', 'instagram', 'og'], example: 'hero'),
                new OA\Property(property: 'prompt', type: 'string', nullable: true),
                new OA\Property(property: 'language', type: 'string', example: 'en'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Image generated')]
    public function generateImage(
        Request $request,
        int $userId,
        string $campaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaign = $this->pluginData->get($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $campaignId);
        if ($campaign === null) {
            return $this->json(['success' => false, 'error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $imageType = $data['type'] ?? 'hero';
        $config = $this->getPluginConfig($userId);
        $language = $data['language'] ?? $config['default_language'];

        $allowedTypes = ['hero', 'linkedin', 'instagram', 'og'];
        if (!in_array($imageType, $allowedTypes, true)) {
            return $this->json(
                ['success' => false, 'error' => 'Invalid image type. Allowed: ' . implode(', ', $allowedTypes)],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $imagePrompt = $data['prompt']
                ?? $this->contentGenerator->buildImagePrompt($campaign, $config, $imageType);

            $result = $this->aiFacade->generateImage($imagePrompt, $userId);

            if (!empty($result['images'])) {
                $imageData = $result['images'][0];
                $filename = $this->landingPageService->saveImageFile(
                    $userId,
                    $campaignId,
                    $language,
                    $imageType,
                    $imageData,
                );

                $this->logger->info('Marketeer image generated', [
                    'user_id' => $userId,
                    'campaign' => $campaignId,
                    'type' => $imageType,
                    'filename' => $filename,
                ]);

                return $this->json([
                    'success' => true,
                    'file' => $filename,
                    'type' => $imageType,
                    'provider' => $result['provider'] ?? null,
                ]);
            }

            return $this->json([
                'success' => false,
                'error' => 'No image returned by provider',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            $this->logger->error('Marketeer image generation failed', [
                'user_id' => $userId,
                'campaign' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Image generation failed: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/campaigns/{campaignId}/refine', name: 'refine', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/refine',
        summary: 'Refine generated content with follow-up prompt',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['prompt'],
            properties: [
                new OA\Property(property: 'prompt', type: 'string', example: 'Make the hero section more dramatic'),
                new OA\Property(property: 'language', type: 'string', example: 'en'),
                new OA\Property(property: 'target', type: 'string', enum: ['html', 'keywords'], example: 'html'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Refined content')]
    public function refine(
        Request $request,
        int $userId,
        string $campaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaign = $this->pluginData->get($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $campaignId);
        if ($campaign === null) {
            return $this->json(['success' => false, 'error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (empty($data['prompt'])) {
            return $this->json(
                ['success' => false, 'error' => 'prompt is required'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $config = $this->getPluginConfig($userId);
        $language = $data['language'] ?? $config['default_language'];
        $target = $data['target'] ?? 'html';
        $refinementPrompt = $data['prompt'];

        $pageKey = $campaignId . '_' . $language;
        $existingPage = $this->pluginData->get($userId, self::PLUGIN_NAME, self::DATA_TYPE_PAGE, $pageKey);

        if ($existingPage === null && $target === 'html') {
            return $this->json(
                ['success' => false, 'error' => 'No existing page for this language. Generate first.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $messages = $this->contentGenerator->buildRefineMessages(
                $campaign,
                $config,
                $language,
                $target,
                $refinementPrompt,
                $existingPage,
            );

            $modelId = $this->modelConfigService->getDefaultModel('CHAT', $userId);
            $provider = $modelId ? $this->modelConfigService->getProviderForModel($modelId) : null;
            $modelName = $modelId ? $this->modelConfigService->getModelName($modelId) : null;

            $response = $this->aiFacade->chat(
                $messages,
                $userId,
                [
                    'provider' => $provider,
                    'model' => $modelName,
                    'temperature' => 0.7,
                    'max_tokens' => 8000,
                ],
            );

            if ($target === 'html') {
                $html = $this->contentGenerator->extractHtml($response['content']);
                $pageData = [
                    'language' => $language,
                    'html' => $html,
                    'generated_at' => (new \DateTimeImmutable())->format('c'),
                    'refined_with' => $refinementPrompt,
                    'model' => $response['model'] ?? null,
                    'provider' => $response['provider'] ?? null,
                ];
                $this->pluginData->set($userId, self::PLUGIN_NAME, self::DATA_TYPE_PAGE, $pageKey, $pageData);
                $this->landingPageService->saveHtmlFile($userId, $campaignId, $language, $html);

                return $this->json([
                    'success' => true,
                    'page' => $pageData,
                    'file' => "marketeer/{$campaignId}/{$language}/index.html",
                ]);
            }

            $keywords = $this->contentGenerator->parseKeywordResponse($response['content']);
            $this->landingPageService->saveKeywordsFile($userId, $campaignId, $language, $keywords);

            return $this->json([
                'success' => true,
                'keywords' => $keywords,
                'count' => count($keywords),
                'file' => "marketeer/{$campaignId}/{$language}/keywords.txt",
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Marketeer refinement failed', [
                'user_id' => $userId,
                'campaign' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Refinement failed: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // --- File Management ---

    #[Route('/campaigns/{campaignId}/files', name: 'campaign_files', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/files',
        summary: 'List generated files for a campaign',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'File list')]
    public function listFiles(
        int $userId,
        string $campaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaign = $this->pluginData->get($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $campaignId);
        if ($campaign === null) {
            return $this->json(['success' => false, 'error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        }

        $files = $this->landingPageService->listCampaignFiles($userId, $campaignId);

        return $this->json([
            'success' => true,
            'files' => $files,
        ]);
    }

    #[Route('/campaigns/{campaignId}/download', name: 'campaign_download', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/download',
        summary: 'Download campaign as ZIP file',
        security: [['Bearer' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'ZIP file download')]
    public function downloadZip(
        int $userId,
        string $campaignId,
        #[CurrentUser] ?User $user,
    ): Response {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaign = $this->pluginData->get($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $campaignId);
        if ($campaign === null) {
            return $this->json(['success' => false, 'error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        }

        $campaignDir = $this->landingPageService->getCampaignDir($userId, $campaignId);
        if (!is_dir($campaignDir)) {
            return $this->json(
                ['success' => false, 'error' => 'No files generated yet'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $zipPath = $this->landingPageService->createZip($userId, $campaignId);

        $response = new StreamedResponse(function () use ($zipPath): void {
            readfile($zipPath);
            @unlink($zipPath);
        });

        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$campaignId}.zip\"");

        return $response;
    }

    private function canAccessPlugin(?User $user, int $userId): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->getId() === $userId;
    }

    /**
     * @return array{default_language: string, cta_url: string, brand_name: string}
     */
    private function getPluginConfig(int $userId): array
    {
        $defaults = [
            'default_language' => 'en',
            'cta_url' => 'https://web.synaplan.com',
            'brand_name' => 'Synaplan',
        ];

        return [
            'default_language' => $this->configRepository->getValue($userId, self::CONFIG_GROUP, 'default_language')
                ?? $defaults['default_language'],
            'cta_url' => $this->configRepository->getValue($userId, self::CONFIG_GROUP, 'cta_url')
                ?? $defaults['cta_url'],
            'brand_name' => $this->configRepository->getValue($userId, self::CONFIG_GROUP, 'brand_name')
                ?? $defaults['brand_name'],
        ];
    }
}
