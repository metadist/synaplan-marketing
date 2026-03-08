<?php

declare(strict_types=1);

namespace Plugin\Marketeer\Controller;

use App\AI\Service\AiFacade;
use App\Entity\User;
use App\Repository\ConfigRepository;
use App\Service\ModelConfigService;
use App\Service\PluginDataService;
use OpenApi\Attributes as OA;
use Plugin\Marketeer\Service\AdCopyService;
use Plugin\Marketeer\Service\ComplianceService;
use Plugin\Marketeer\Service\ContentGenerator;
use Plugin\Marketeer\Service\GoogleAdsPlannerService;
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
        private AdCopyService $adCopyService,
        private GoogleAdsPlannerService $adsPlannerService,
        private ComplianceService $complianceService,
        private ConfigRepository $configRepository,
        private LoggerInterface $logger,
        private string $uploadDir,
    ) {
    }

    // =========================================================================
    // Setup & Configuration
    // =========================================================================

    #[Route('/setup-check', name: 'setup_check', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/setup-check',
        summary: 'Check plugin setup status',
        security: [['ApiKey' => []]],
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
        security: [['ApiKey' => []]],
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
        security: [['ApiKey' => []]],
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
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
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
        $allowedKeys = [
            'default_language', 'cta_url', 'brand_name',
            'privacy_policy_url', 'imprint_url', 'gtm_id', 'gads_conversion_id',
        ];
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

    // =========================================================================
    // Dashboard
    // =========================================================================

    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/dashboard',
        summary: 'Campaign overview dashboard',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Dashboard data')]
    public function dashboard(int $userId, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaigns = $this->pluginData->list($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN);
        $pages = $this->pluginData->list($userId, self::PLUGIN_NAME, self::DATA_TYPE_PAGE);

        $statusCounts = ['draft' => 0, 'active' => 0, 'paused' => 0, 'completed' => 0];
        $campaignSummaries = [];

        foreach ($campaigns as $slug => $campaign) {
            $status = $campaign['status'] ?? 'draft';
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

            $pageCount = 0;
            $prefix = $slug . '_';
            foreach ($pages as $key => $page) {
                if (str_starts_with($key, $prefix)) {
                    ++$pageCount;
                }
            }

            $adCopy = $this->adCopyService->listAdCopy($userId, $slug);
            $socialPosts = $this->adCopyService->listSocialPosts($userId, $slug);
            $adsCampaigns = $this->adsPlannerService->listForCampaign($userId, $slug);
            $files = $this->landingPageService->listCampaignFiles($userId, $slug);

            $campaignSummaries[] = [
                'id' => $slug,
                'title' => $campaign['title'] ?? $slug,
                'status' => $status,
                'languages' => $campaign['languages'] ?? [],
                'pages_count' => $pageCount,
                'ad_copy_count' => count($adCopy),
                'social_posts_count' => count($socialPosts),
                'ads_campaigns_count' => count($adsCampaigns),
                'files_count' => count($files),
                'platforms' => $campaign['platforms'] ?? [],
                'created_at' => $campaign['created_at'] ?? null,
                'updated_at' => $campaign['updated_at'] ?? null,
            ];
        }

        return $this->json([
            'success' => true,
            'overview' => [
                'total_campaigns' => count($campaigns),
                'status_breakdown' => $statusCounts,
                'total_pages' => count($pages),
            ],
            'campaigns' => $campaignSummaries,
        ]);
    }

    // =========================================================================
    // Campaign Planning (AI-assisted)
    // =========================================================================

    #[Route('/plan', name: 'plan_campaign', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/plan',
        summary: 'Generate a campaign plan from an idea',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['idea'],
            properties: [
                new OA\Property(property: 'idea', type: 'string', example: 'Promote our open-source AI platform to CTOs'),
                new OA\Property(property: 'language', type: 'string', example: 'en'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Generated campaign plan')]
    public function planCampaign(
        Request $request,
        int $userId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (empty($data['idea'])) {
            return $this->json(
                ['success' => false, 'error' => 'idea is required'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $config = $this->getPluginConfig($userId);
        $language = $data['language'] ?? $config['default_language'];

        try {
            $messages = $this->contentGenerator->buildCampaignPlanPrompt($data['idea'], $config, $language);
            $response = $this->callChat($userId, $messages, 0.7, 4000);
            $plan = $this->contentGenerator->parseJsonResponse($response['content']);

            return $this->json([
                'success' => true,
                'plan' => $plan,
                'model' => $response['model'] ?? null,
                'provider' => $response['provider'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Marketeer campaign planning failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Campaign planning failed: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // =========================================================================
    // Campaign CRUD
    // =========================================================================

    #[Route('/campaigns', name: 'campaigns_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns',
        summary: 'List all campaigns',
        security: [['ApiKey' => []]],
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
        security: [['ApiKey' => []]],
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
                new OA\Property(property: 'target_audience', type: 'string', nullable: true),
                new OA\Property(property: 'unique_selling_points', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                new OA\Property(property: 'platforms', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                new OA\Property(property: 'ctas', type: 'array', items: new OA\Items(type: 'object'), nullable: true),
                new OA\Property(property: 'tracking', type: 'object', nullable: true),
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

        $slug = preg_replace('/[^a-z0-9]/', '', strtolower(trim($data['slug'])));

        if ($this->pluginData->exists($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $slug)) {
            return $this->json(
                ['success' => false, 'error' => "Campaign '{$slug}' already exists"],
                Response::HTTP_CONFLICT,
            );
        }

        $config = $this->getPluginConfig($userId);
        $now = (new \DateTimeImmutable())->format('c');

        $campaign = [
            'title' => $data['title'],
            'topic' => $data['topic'],
            'languages' => $data['languages'] ?? [$config['default_language']],
            'cta_url' => $data['cta_url'] ?? $config['cta_url'],
            'target_audience' => $data['target_audience'] ?? '',
            'unique_selling_points' => $data['unique_selling_points'] ?? [],
            'platforms' => $data['platforms'] ?? ['google'],
            'ctas' => $data['ctas'] ?? [],
            'tracking' => $data['tracking'] ?? [
                'gtm_id' => $config['gtm_id'] ?? '',
                'gads_conversion_id' => $config['gads_conversion_id'] ?? '',
                'meta_pixel_id' => '',
            ],
            'status' => 'draft',
            'sort_order' => $this->pluginData->count($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->pluginData->set($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $slug, $campaign);
        $this->landingPageService->ensureCampaignDirectories($userId, $slug, $campaign['languages']);

        $this->logger->info('Marketeer campaign created', ['user_id' => $userId, 'slug' => $slug]);

        return $this->json([
            'success' => true,
            'campaign' => array_merge(['id' => $slug], $campaign),
        ], Response::HTTP_CREATED);
    }

    #[Route('/campaigns/{campaignId}', name: 'campaigns_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}',
        summary: 'Get full campaign details with all assets',
        security: [['ApiKey' => []]],
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

        $pages = $this->getCampaignPages($userId, $campaignId);
        $adCopy = $this->adCopyService->listAdCopy($userId, $campaignId);
        $socialPosts = $this->adCopyService->listSocialPosts($userId, $campaignId);
        $collaterals = $this->adCopyService->listCollaterals($userId, $campaignId);
        $adsCampaigns = $this->adsPlannerService->listForCampaign($userId, $campaignId);
        $files = $this->landingPageService->listCampaignFiles($userId, $campaignId);

        return $this->json([
            'success' => true,
            'campaign' => array_merge(['id' => $campaignId], $campaign),
            'pages' => $pages,
            'ad_copy' => $adCopy,
            'social_posts' => $socialPosts,
            'collaterals' => $collaterals,
            'ads_campaigns' => $adsCampaigns,
            'files' => $files,
        ]);
    }

    #[Route('/campaigns/{campaignId}', name: 'campaigns_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}',
        summary: 'Update campaign',
        security: [['ApiKey' => []]],
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
        $allowedFields = [
            'title', 'topic', 'languages', 'cta_url', 'status',
            'target_audience', 'unique_selling_points', 'platforms',
            'ctas', 'tracking', 'sort_order',
        ];

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
        summary: 'Delete campaign and all generated assets',
        security: [['ApiKey' => []]],
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

        $this->deleteCampaignPages($userId, $campaignId);
        $this->adCopyService->deleteAllForCampaign($userId, $campaignId);
        $this->adsPlannerService->deleteAllForCampaign($userId, $campaignId);

        $this->pluginData->delete($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $campaignId);
        $this->landingPageService->deleteCampaignDirectory($userId, $campaignId);

        $this->logger->info('Marketeer campaign deleted', ['user_id' => $userId, 'slug' => $campaignId]);

        return $this->json(['success' => true, 'message' => "Campaign '{$campaignId}' deleted"]);
    }

    // =========================================================================
    // Landing Page Generation
    // =========================================================================

    #[Route('/campaigns/{campaignId}/generate', name: 'generate_page', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/generate',
        summary: 'Generate landing page HTML via AI',
        security: [['ApiKey' => []]],
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

            $response = $this->callChat($userId, [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ], 0.7, 8000);

            $html = $this->contentGenerator->extractHtml($response['content']);

            $html = $this->injectComplianceSnippets($html, $campaign, $config, $language);

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

    #[Route('/campaigns/{campaignId}/pages/{language}', name: 'page_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/pages/{language}',
        summary: 'Get a specific generated page',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Page data')]
    public function getPage(
        int $userId,
        string $campaignId,
        string $language,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $pageKey = $campaignId . '_' . $language;
        $page = $this->pluginData->get($userId, self::PLUGIN_NAME, self::DATA_TYPE_PAGE, $pageKey);

        if ($page === null) {
            return $this->json(['success' => false, 'error' => 'Page not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'page' => array_merge(['key' => $pageKey], $page),
        ]);
    }

    #[Route('/campaigns/{campaignId}/pages/{language}', name: 'page_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/pages/{language}',
        summary: 'Delete a specific generated page',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Page deleted')]
    public function deletePage(
        int $userId,
        string $campaignId,
        string $language,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $pageKey = $campaignId . '_' . $language;
        $deleted = $this->pluginData->delete($userId, self::PLUGIN_NAME, self::DATA_TYPE_PAGE, $pageKey);

        return $this->json([
            'success' => true,
            'deleted' => $deleted,
        ]);
    }

    #[Route('/campaigns/{campaignId}/refine', name: 'refine', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/refine',
        summary: 'Refine generated content with follow-up prompt',
        security: [['ApiKey' => []]],
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

            $response = $this->callChat($userId, $messages, 0.7, 8000);

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

    // =========================================================================
    // Ad Copy & Social Posts
    // =========================================================================

    #[Route('/campaigns/{campaignId}/generate-ad-copy', name: 'generate_ad_copy', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/generate-ad-copy',
        summary: 'Generate ad copy for a platform (Google RSA, LinkedIn, Instagram, Discord)',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['platform'],
            properties: [
                new OA\Property(property: 'platform', type: 'string', enum: ['google', 'linkedin', 'instagram', 'discord'], example: 'google'),
                new OA\Property(property: 'language', type: 'string', example: 'en'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Generated ad copy')]
    public function generateAdCopy(
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
        $platform = $data['platform'] ?? 'google';
        $config = $this->getPluginConfig($userId);
        $language = $data['language'] ?? $config['default_language'];

        $allowedPlatforms = ['google', 'linkedin', 'instagram', 'discord'];
        if (!in_array($platform, $allowedPlatforms, true)) {
            return $this->json(
                ['success' => false, 'error' => 'Invalid platform. Allowed: ' . implode(', ', $allowedPlatforms)],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $campaign['cta_url'] = $campaign['cta_url'] ?? $config['cta_url'];
            $messages = $this->contentGenerator->buildAdCopyPrompt($campaign, $config, $language, $platform);
            $response = $this->callChat($userId, $messages, 0.6, 4000);
            $parsed = $this->contentGenerator->parseJsonResponse($response['content']);

            $parsed['generated_at'] = (new \DateTimeImmutable())->format('c');
            $parsed['model'] = $response['model'] ?? null;
            $parsed['provider'] = $response['provider'] ?? null;

            if ($platform === 'google') {
                $this->adCopyService->saveAdCopy($userId, $campaignId, $language, $parsed);
            } else {
                $this->adCopyService->saveSocialPost($userId, $campaignId, $platform, $language, $parsed);
            }

            $this->logger->info('Marketeer ad copy generated', [
                'user_id' => $userId,
                'campaign' => $campaignId,
                'platform' => $platform,
                'language' => $language,
            ]);

            return $this->json([
                'success' => true,
                'platform' => $platform,
                'language' => $language,
                'content' => $parsed,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Marketeer ad copy generation failed', [
                'user_id' => $userId,
                'campaign' => $campaignId,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Ad copy generation failed: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/campaigns/{campaignId}/ad-copy', name: 'list_ad_copy', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/ad-copy',
        summary: 'List all ad copy and social posts for a campaign',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Ad copy and social posts')]
    public function listAdCopy(
        int $userId,
        string $campaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'ad_copy' => $this->adCopyService->listAdCopy($userId, $campaignId),
            'social_posts' => $this->adCopyService->listSocialPosts($userId, $campaignId),
            'collaterals' => $this->adCopyService->listCollaterals($userId, $campaignId),
        ]);
    }

    #[Route('/campaigns/{campaignId}/ad-copy/{platform}/{language}', name: 'delete_ad_copy', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/ad-copy/{platform}/{language}',
        summary: 'Delete ad copy for a specific platform and language',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Ad copy deleted')]
    public function deleteAdCopy(
        int $userId,
        string $campaignId,
        string $platform,
        string $language,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if ($platform === 'google') {
            $deleted = $this->adCopyService->deleteAdCopy($userId, $campaignId, $language);
        } else {
            $deleted = $this->adCopyService->deleteSocialPost($userId, $campaignId, $platform, $language);
        }

        return $this->json(['success' => true, 'deleted' => $deleted]);
    }

    // =========================================================================
    // Keywords
    // =========================================================================

    #[Route('/campaigns/{campaignId}/generate-keywords', name: 'generate_keywords', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/generate-keywords',
        summary: 'Generate keyword list for Google Ads',
        security: [['ApiKey' => []]],
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
            $messages = $this->contentGenerator->buildKeywordPrompt($campaign, $config, $language, $count, $extraInstructions);
            $response = $this->callChat($userId, $messages, 0.5, 4000);
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

    // =========================================================================
    // Image / Collateral Generation
    // =========================================================================

    #[Route('/campaigns/{campaignId}/generate-image', name: 'generate_image', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/generate-image',
        summary: 'Generate marketing image (hero, social, banner, icon)',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            required: ['type'],
            properties: [
                new OA\Property(property: 'type', type: 'string', enum: ['hero', 'linkedin', 'instagram', 'og', 'icon', 'banner_wide', 'banner_rect', 'banner_sky'], example: 'hero'),
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

        $allowedTypes = ['hero', 'linkedin', 'instagram', 'og', 'icon', 'banner_wide', 'banner_rect', 'banner_sky'];
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

                $this->adCopyService->saveCollateral($userId, $campaignId, $imageType, $language, [
                    'file' => $filename,
                    'prompt' => $imagePrompt,
                    'provider' => $result['provider'] ?? null,
                ]);

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

    // =========================================================================
    // Google Ads Campaign Planning
    // =========================================================================

    #[Route('/campaigns/{campaignId}/ads-campaigns', name: 'ads_campaigns_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/ads-campaigns',
        summary: 'List planned Google Ads campaigns',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Ads campaign list')]
    public function listAdsCampaigns(
        int $userId,
        string $campaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaigns = $this->adsPlannerService->listForCampaign($userId, $campaignId);

        return $this->json([
            'success' => true,
            'ads_campaigns' => $campaigns,
            'count' => count($campaigns),
        ]);
    }

    #[Route('/campaigns/{campaignId}/ads-campaigns/generate', name: 'ads_campaigns_generate', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/ads-campaigns/generate',
        summary: 'AI-generate a complete Google Ads campaign structure',
        security: [['ApiKey' => []]],
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
    #[OA\Response(response: 201, description: 'Generated ads campaign')]
    public function generateAdsCampaign(
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
            $campaign['cta_url'] = $campaign['cta_url'] ?? $config['cta_url'];
            $messages = $this->contentGenerator->buildAdsCampaignStructurePrompt($campaign, $config, $language, $extraInstructions);
            $response = $this->callChat($userId, $messages, 0.6, 6000);
            $structure = $this->contentGenerator->parseJsonResponse($response['content']);

            $structure['language'] = $language;
            $structure['generated_at'] = (new \DateTimeImmutable())->format('c');
            $structure['model'] = $response['model'] ?? null;
            $structure['provider'] = $response['provider'] ?? null;

            $id = $this->adsPlannerService->create($userId, $campaignId, $structure);

            $this->logger->info('Marketeer Google Ads campaign generated', [
                'user_id' => $userId,
                'campaign' => $campaignId,
                'ads_campaign_id' => $id,
                'language' => $language,
            ]);

            return $this->json([
                'success' => true,
                'ads_campaign_id' => $id,
                'ads_campaign' => $structure,
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $this->logger->error('Marketeer ads campaign generation failed', [
                'user_id' => $userId,
                'campaign' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Ads campaign generation failed: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/campaigns/{campaignId}/ads-campaigns', name: 'ads_campaigns_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/ads-campaigns',
        summary: 'Manually create a Google Ads campaign plan',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 201, description: 'Ads campaign created')]
    public function createAdsCampaign(
        Request $request,
        int $userId,
        string $campaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (empty($data['campaign_name'])) {
            return $this->json(
                ['success' => false, 'error' => 'campaign_name is required'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $id = $this->adsPlannerService->create($userId, $campaignId, $data);

        return $this->json([
            'success' => true,
            'ads_campaign_id' => $id,
        ], Response::HTTP_CREATED);
    }

    #[Route('/campaigns/{campaignId}/ads-campaigns/{adsCampaignId}', name: 'ads_campaigns_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/ads-campaigns/{adsCampaignId}',
        summary: 'Get a specific ads campaign plan',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Ads campaign details')]
    public function getAdsCampaign(
        int $userId,
        string $campaignId,
        string $adsCampaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $adsCampaign = $this->adsPlannerService->get($userId, $campaignId, $adsCampaignId);
        if ($adsCampaign === null) {
            return $this->json(['success' => false, 'error' => 'Ads campaign not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'ads_campaign' => $adsCampaign,
        ]);
    }

    #[Route('/campaigns/{campaignId}/ads-campaigns/{adsCampaignId}', name: 'ads_campaigns_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/ads-campaigns/{adsCampaignId}',
        summary: 'Update an ads campaign plan',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Ads campaign updated')]
    public function updateAdsCampaign(
        Request $request,
        int $userId,
        string $campaignId,
        string $adsCampaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $updated = $this->adsPlannerService->update($userId, $campaignId, $adsCampaignId, $data);

        if ($updated === null) {
            return $this->json(['success' => false, 'error' => 'Ads campaign not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'ads_campaign' => $updated,
        ]);
    }

    #[Route('/campaigns/{campaignId}/ads-campaigns/{adsCampaignId}', name: 'ads_campaigns_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/ads-campaigns/{adsCampaignId}',
        summary: 'Delete an ads campaign plan',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Ads campaign deleted')]
    public function deleteAdsCampaign(
        int $userId,
        string $campaignId,
        string $adsCampaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $deleted = $this->adsPlannerService->delete($userId, $campaignId, $adsCampaignId);

        return $this->json(['success' => true, 'deleted' => $deleted]);
    }

    #[Route('/campaigns/{campaignId}/ads-campaigns/{adsCampaignId}/export-keywords', name: 'ads_campaigns_export_keywords', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/ads-campaigns/{adsCampaignId}/export-keywords',
        summary: 'Export keywords from ads campaign for Google Ads import',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Exported keywords')]
    public function exportAdsCampaignKeywords(
        int $userId,
        string $campaignId,
        string $adsCampaignId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $export = $this->adsPlannerService->exportKeywords($userId, $campaignId, $adsCampaignId);

        return $this->json([
            'success' => true,
            'keywords' => $export['keywords'],
            'negative_keywords' => $export['negative_keywords'],
            'total' => count($export['keywords']),
        ]);
    }

    // =========================================================================
    // Compliance & GDPR
    // =========================================================================

    #[Route('/campaigns/{campaignId}/compliance', name: 'compliance_check', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/compliance',
        summary: 'Quick GDPR/compliance check for campaign',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Compliance check results')]
    public function complianceCheck(
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

        $pages = $this->getCampaignPages($userId, $campaignId);

        $result = $this->complianceService->quickComplianceCheck($campaign, ['pages' => $pages]);

        return $this->json([
            'success' => true,
            'compliance' => $result,
        ]);
    }

    #[Route('/campaigns/{campaignId}/compliance/ai-review', name: 'compliance_ai_review', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/compliance/ai-review',
        summary: 'AI-powered deep compliance review',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'language', type: 'string', example: 'en'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'AI compliance review')]
    public function complianceAiReview(
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

        try {
            $messages = $this->contentGenerator->buildComplianceCheckPrompt($campaign, $config, $language);
            $response = $this->callChat($userId, $messages, 0.3, 4000);
            $review = $this->contentGenerator->parseJsonResponse($response['content']);

            return $this->json([
                'success' => true,
                'review' => $review,
                'model' => $response['model'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Marketeer compliance review failed', [
                'user_id' => $userId,
                'campaign' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Compliance review failed: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/compliance/cookie-snippet', name: 'compliance_cookie_snippet', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/compliance/cookie-snippet',
        summary: 'Get a GDPR-compliant cookie consent HTML snippet',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Parameter(name: 'language', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Cookie consent snippet')]
    public function cookieConsentSnippet(
        Request $request,
        int $userId,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $config = $this->getPluginConfig($userId);
        $language = $request->query->get('language', $config['default_language']);

        $snippet = $this->complianceService->generateCookieConsentSnippet($config, $language);
        $trackingSnippets = $this->complianceService->generateTrackingSnippets([
            'gtm_id' => $config['gtm_id'] ?? '',
            'gads_conversion_id' => $config['gads_conversion_id'] ?? '',
        ]);

        return $this->json([
            'success' => true,
            'cookie_consent_html' => $snippet,
            'tracking_html' => $trackingSnippets,
            'language' => $language,
        ]);
    }

    // =========================================================================
    // Pre-Launch Checklist
    // =========================================================================

    #[Route('/campaigns/{campaignId}/checklist', name: 'pre_launch_checklist', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/checklist',
        summary: 'AI-powered pre-launch readiness check',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Pre-launch checklist')]
    public function preLaunchChecklist(
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

        $config = $this->getPluginConfig($userId);
        $pages = $this->getCampaignPages($userId, $campaignId);
        $adCopy = $this->adCopyService->listAdCopy($userId, $campaignId);
        $socialPosts = $this->adCopyService->listSocialPosts($userId, $campaignId);
        $adsCampaigns = $this->adsPlannerService->listForCampaign($userId, $campaignId);
        $files = $this->landingPageService->listCampaignFiles($userId, $campaignId);

        $assets = [
            'pages' => $pages,
            'ad_copy_count' => count($adCopy),
            'social_posts_count' => count($socialPosts),
            'ads_campaigns_count' => count($adsCampaigns),
            'files' => $files,
            'languages_covered' => array_keys($pages),
            'platforms_with_content' => $this->getPlatformsWithContent($adCopy, $socialPosts),
        ];

        try {
            $messages = $this->contentGenerator->buildPreLaunchCheckPrompt($campaign, $config, $assets);
            $response = $this->callChat($userId, $messages, 0.3, 4000);
            $checklist = $this->contentGenerator->parseJsonResponse($response['content']);

            return $this->json([
                'success' => true,
                'checklist' => $checklist,
                'assets_summary' => $assets,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Marketeer checklist generation failed', [
                'user_id' => $userId,
                'campaign' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Checklist generation failed: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // =========================================================================
    // File Management
    // =========================================================================

    #[Route('/campaigns/{campaignId}/files', name: 'campaign_files', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/files',
        summary: 'List generated files for a campaign',
        security: [['ApiKey' => []]],
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

        return $this->json([
            'success' => true,
            'files' => $this->landingPageService->listCampaignFiles($userId, $campaignId),
        ]);
    }

    #[Route('/campaigns/{campaignId}/download', name: 'campaign_download', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/download',
        summary: 'Download campaign as ZIP file',
        security: [['ApiKey' => []]],
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

    // =========================================================================
    // Private Helpers
    // =========================================================================

    private function canAccessPlugin(?User $user, int $userId): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->getId() !== $userId) {
            return false;
        }

        return $this->configRepository->getValue($userId, self::CONFIG_GROUP, 'enabled') === '1';
    }

    /**
     * @return array{default_language: string, cta_url: string, brand_name: string, privacy_policy_url: string, imprint_url: string, gtm_id: string, gads_conversion_id: string}
     */
    private function getPluginConfig(int $userId): array
    {
        $defaults = [
            'default_language' => 'en',
            'cta_url' => 'https://web.synaplan.com',
            'brand_name' => 'Synaplan',
            'privacy_policy_url' => '',
            'imprint_url' => '',
            'gtm_id' => '',
            'gads_conversion_id' => '',
        ];

        $config = [];
        foreach ($defaults as $key => $default) {
            $config[$key] = $this->configRepository->getValue($userId, self::CONFIG_GROUP, $key) ?? $default;
        }

        return $config;
    }

    /**
     * @param array<int, array{role: string, content: string}> $messages
     * @return array<string, mixed>
     */
    private function callChat(int $userId, array $messages, float $temperature, int $maxTokens): array
    {
        $modelId = $this->modelConfigService->getDefaultModel('CHAT', $userId);
        $provider = $modelId ? $this->modelConfigService->getProviderForModel($modelId) : null;
        $modelName = $modelId ? $this->modelConfigService->getModelName($modelId) : null;

        return $this->aiFacade->chat(
            $messages,
            $userId,
            [
                'provider' => $provider,
                'model' => $modelName,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ],
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getCampaignPages(int $userId, string $campaignId): array
    {
        $pages = $this->pluginData->list($userId, self::PLUGIN_NAME, self::DATA_TYPE_PAGE);
        $campaignPages = [];
        $prefix = $campaignId . '_';

        foreach ($pages as $key => $pageData) {
            if (str_starts_with($key, $prefix)) {
                $lang = substr($key, strlen($prefix));
                $campaignPages[$lang] = $pageData;
            }
        }

        return $campaignPages;
    }

    private function deleteCampaignPages(int $userId, string $campaignId): void
    {
        $pages = $this->pluginData->list($userId, self::PLUGIN_NAME, self::DATA_TYPE_PAGE);
        $prefix = $campaignId . '_';

        foreach ($pages as $key => $pageData) {
            if (str_starts_with($key, $prefix)) {
                $this->pluginData->delete($userId, self::PLUGIN_NAME, self::DATA_TYPE_PAGE, $key);
            }
        }
    }

    /**
     * Inject cookie consent and tracking snippets into generated HTML.
     *
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     */
    private function injectComplianceSnippets(
        string $html,
        array $campaign,
        array $config,
        string $language,
    ): string {
        $hasTracking = !empty($campaign['tracking']['gtm_id'])
            || !empty($campaign['tracking']['gads_conversion_id']);

        if (!$hasTracking) {
            return $html;
        }

        $cookieSnippet = $this->complianceService->generateCookieConsentSnippet($config, $language);
        $trackingSnippet = $this->complianceService->generateTrackingSnippets($campaign['tracking']);

        $closingBody = '</body>';
        if (str_contains($html, $closingBody)) {
            $html = str_replace($closingBody, $trackingSnippet . "\n" . $cookieSnippet . "\n" . $closingBody, $html);
        }

        return $html;
    }

    /**
     * @return string[]
     */
    private function getPlatformsWithContent(array $adCopy, array $socialPosts): array
    {
        $platforms = [];

        if (!empty($adCopy)) {
            $platforms[] = 'google';
        }

        foreach ($socialPosts as $key => $post) {
            $platform = $post['platform'] ?? '';
            if ($platform !== '' && !in_array($platform, $platforms, true)) {
                $platforms[] = $platform;
            }
        }

        return $platforms;
    }
}
