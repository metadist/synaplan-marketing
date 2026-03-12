<?php

declare(strict_types=1);

namespace Plugin\Marketeer\Controller;

use App\AI\Service\AiFacade;
use App\Entity\PluginData;
use App\Entity\User;
use App\Repository\ConfigRepository;
use App\Repository\PluginDataRepository;
use App\Service\TokenService;
use App\Service\ModelConfigService;
use App\Service\PluginDataService;
use App\Service\Exception\RateLimitExceededException;
use App\Service\RateLimitService;
use OpenApi\Attributes as OA;
use Plugin\Marketeer\Service\AdCopyService;
use Plugin\Marketeer\Service\ComplianceService;
use Plugin\Marketeer\Service\ContentGenerator;
use Plugin\Marketeer\Service\GoogleAdsEditorCsvBuilder;
use Plugin\Marketeer\Service\GoogleAdsPlannerService;
use Plugin\Marketeer\Service\LandingPageService;
use Plugin\Marketeer\Service\MarketeerInstallService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
    private const DATA_TYPE_PUBLIC_PAGE = 'public_page';

    public function __construct(
        private AiFacade $aiFacade,
        private ModelConfigService $modelConfigService,
        private PluginDataService $pluginData,
        private PluginDataRepository $pluginDataRepository,
        private MarketeerInstallService $installService,
        private ContentGenerator $contentGenerator,
        private LandingPageService $landingPageService,
        private AdCopyService $adCopyService,
        private GoogleAdsPlannerService $adsPlannerService,
        private ComplianceService $complianceService,
        private ConfigRepository $configRepository,
        private TokenService $tokenService,
        private RateLimitService $rateLimitService,
        private LoggerInterface $logger,
        #[Autowire('%app.upload_dir%')] private string $uploadDir,
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
            'default_accent_color', 'default_brand_logo_url', 'default_color_scheme',
            'default_background_style', 'default_background_image_url',
            'default_background_image_position', 'default_hero_text_align',
            'default_background_overlay_opacity',
            'default_background_color', 'default_background_secondary_color',
            'default_background_image_size', 'default_background_icon_url',
            'default_background_icon_position', 'default_background_icon_size_percent',
            'default_background_icon_opacity', 'default_background_motion_intensity',
        ];
        $updated = [];

        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $data)) {
                $this->configRepository->setValue($userId, self::CONFIG_GROUP, $key, (string) $data[$key]);
                $updated[] = $key;
            }
        }

        $promptKeys = ['landing_page_prompt', 'image_prompt', 'video_prompt'];
        foreach ($promptKeys as $promptKey) {
            if (array_key_exists($promptKey, $data)) {
                $prompt = trim((string) $data[$promptKey]);
                if ($prompt === '') {
                    $this->pluginData->delete($userId, self::PLUGIN_NAME, 'config', $promptKey);
                } else {
                    $this->pluginData->set($userId, self::PLUGIN_NAME, 'config', $promptKey, ['prompt' => $prompt]);
                }
                $updated[] = $promptKey;
            }
        }

        return $this->json([
            'success' => true,
            'message' => 'Configuration updated',
            'updated' => $updated,
            'config' => $this->getPluginConfig($userId),
        ]);
    }

    #[Route('/config/default-prompt', name: 'config_default_prompt', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/config/default-prompt',
        summary: 'Get the built-in default landing page prompt template',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Default prompt')]
    public function getDefaultPrompt(int $userId, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'prompt' => $this->contentGenerator->getDefaultLandingPagePromptTemplate(),
        ]);
    }

    #[Route('/config/default-media-prompts', name: 'config_default_media_prompts', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/user/{userId}/plugins/marketeer/config/default-media-prompts',
        summary: 'Get the built-in default image and video prompt templates',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Default media prompts')]
    public function getDefaultMediaPrompts(int $userId, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'image_prompt' => $this->contentGenerator->getDefaultImagePromptTemplate(),
            'video_prompt' => $this->contentGenerator->getDefaultVideoPromptTemplate(),
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
            $response = $this->callChat($user, $messages, 0.7, 4000);
            $plan = $this->contentGenerator->parseJsonResponse($response['content']);

            return $this->json([
                'success' => true,
                'plan' => $plan,
                'model' => $response['model'] ?? null,
                'provider' => $response['provider'] ?? null,
            ]);
        } catch (RateLimitExceededException $e) {
            return $this->rateLimitExceededResponse($user, $e);
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
                new OA\Property(property: 'background_style', type: 'string', nullable: true, example: 'parallax'),
                new OA\Property(property: 'background_color', type: 'string', nullable: true, example: '#111111'),
                new OA\Property(property: 'background_secondary_color', type: 'string', nullable: true, example: '#1f2937'),
                new OA\Property(property: 'background_image_url', type: 'string', nullable: true),
                new OA\Property(property: 'background_image_position', type: 'string', nullable: true, example: 'center top'),
                new OA\Property(property: 'background_image_size', type: 'string', nullable: true, example: 'cover'),
                new OA\Property(property: 'background_icon_url', type: 'string', nullable: true),
                new OA\Property(property: 'background_icon_position', type: 'string', nullable: true, example: 'right top'),
                new OA\Property(property: 'background_icon_size_percent', type: 'number', format: 'float', nullable: true, example: 20),
                new OA\Property(property: 'background_icon_opacity', type: 'number', format: 'float', nullable: true, example: 0.35),
                new OA\Property(property: 'background_motion_intensity', type: 'string', nullable: true, example: 'medium'),
                new OA\Property(property: 'hero_text_align', type: 'string', nullable: true, example: 'left'),
                new OA\Property(property: 'background_overlay_opacity', type: 'number', format: 'float', nullable: true, example: 0.5),
                new OA\Property(property: 'style_prompt', type: 'string', nullable: true, example: 'Use a cinematic image cover hero with readable overlay'),
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
            'cta_url' => $data['cta_url'] ?? (!empty($data['ctas'][0]['url']) ? $data['ctas'][0]['url'] : $config['cta_url']),
            'target_audience' => $data['target_audience'] ?? '',
            'unique_selling_points' => $data['unique_selling_points'] ?? [],
            'platforms' => $data['platforms'] ?? ['google'],
            'ctas' => $data['ctas'] ?? [],
            'accent_color' => $data['accent_color'] ?? $config['default_accent_color'] ?? '#00b79d',
            'brand_logo_url' => $data['brand_logo_url'] ?? $config['default_brand_logo_url'] ?? '',
            'color_scheme' => $data['color_scheme'] ?? $config['default_color_scheme'] ?? 'dark backgrounds (#111) with vibrant accent',
            'background_style' => $data['background_style'] ?? $config['default_background_style'] ?? 'parallax',
            'background_color' => $data['background_color'] ?? $config['default_background_color'] ?? '#111111',
            'background_secondary_color' => $data['background_secondary_color'] ?? $config['default_background_secondary_color'] ?? '#1f2937',
            'background_image_url' => $data['background_image_url'] ?? $config['default_background_image_url'] ?? '',
            'background_image_position' => $data['background_image_position'] ?? $config['default_background_image_position'] ?? 'center center',
            'background_image_size' => $data['background_image_size'] ?? $config['default_background_image_size'] ?? 'cover',
            'background_icon_url' => $data['background_icon_url'] ?? $config['default_background_icon_url'] ?? '',
            'background_icon_position' => $data['background_icon_position'] ?? $config['default_background_icon_position'] ?? 'center center',
            'background_icon_size_percent' => $data['background_icon_size_percent'] ?? $config['default_background_icon_size_percent'] ?? 20,
            'background_icon_opacity' => $data['background_icon_opacity'] ?? $config['default_background_icon_opacity'] ?? 0.35,
            'background_motion_intensity' => $data['background_motion_intensity'] ?? $config['default_background_motion_intensity'] ?? 'medium',
            'hero_text_align' => $data['hero_text_align'] ?? $config['default_hero_text_align'] ?? 'center',
            'background_overlay_opacity' => $data['background_overlay_opacity'] ?? $config['default_background_overlay_opacity'] ?? 0.48,
            'style_prompt' => $data['style_prompt'] ?? $this->getDefaultStylePrompt(
                (string) ($data['background_style'] ?? $config['default_background_style'] ?? 'parallax'),
            ),
            'modal_content' => $data['modal_content'] ?? '',
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

        $data = [
            'success' => true,
            'campaign' => array_merge(['id' => $campaignId], $campaign),
            'pages' => $pages,
            'published_pages' => $this->getPublishedPagesForCampaign($userId, $campaignId),
            'ad_copy' => $adCopy,
            'social_posts' => $socialPosts,
            'collaterals' => $collaterals,
            'ads_campaigns' => $adsCampaigns,
            'files' => $files,
        ];

        $json = json_encode($data, \JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_AMP | \JSON_HEX_QUOT | \JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to encode campaign data: ' . json_last_error_msg(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return JsonResponse::fromJsonString($json);
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
            'title', 'topic', 'languages', 'cta_url', 'cta_urls', 'status',
            'target_audience', 'unique_selling_points', 'platforms',
            'ctas', 'tracking', 'sort_order', 'accent_color', 'modal_content',
            'brand_logo_url', 'color_scheme', 'image_style', 'image_style_notes',
            'background_style', 'background_image_url', 'background_image_position',
            'background_color', 'background_secondary_color',
            'background_image_size', 'background_icon_url', 'background_icon_position',
            'background_icon_size_percent', 'background_icon_opacity', 'background_motion_intensity',
            'hero_text_align', 'background_overlay_opacity', 'style_prompt',
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
        if (($extraInstructions === null || trim((string) $extraInstructions) === '') && !empty($campaign['style_prompt'])) {
            $extraInstructions = (string) $campaign['style_prompt'];
        }
        $campaign = $this->applyLanguageSpecificCtaUrl($campaign, $config, $language);

        $ogCollateral = $this->adCopyService->getCollateral($userId, $campaignId, 'og', $language);
        if (null !== $ogCollateral && !empty($ogCollateral['file'])) {
            $campaign['og_image_url'] = 'images/og.png';
        }

        try {
            $systemPrompt = $this->contentGenerator->buildLandingPagePrompt($campaign, $config, $language);
            $userMessage = $this->contentGenerator->buildLandingPageRequest($campaign, $language, $extraInstructions);

            $response = $this->callChat($user, [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ], 0.7, 8000);

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
        } catch (RateLimitExceededException $e) {
            return $this->rateLimitExceededResponse($user, $e);
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

    #[Route('/campaigns/{campaignId}/pages/{language}/publish', name: 'page_publish', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/pages/{language}/publish',
        summary: 'Publish a landing page with a public slug URL',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'slug', type: 'string', nullable: true, example: 'my-campaign-de'),
                new OA\Property(property: 'active', type: 'boolean', nullable: true, example: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Page published')]
    public function publishPage(
        Request $request,
        int $userId,
        string $campaignId,
        string $language,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaign = $this->pluginData->get($userId, self::PLUGIN_NAME, self::DATA_TYPE_CAMPAIGN, $campaignId);
        if ($campaign === null) {
            return $this->json(['success' => false, 'error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        }

        $pageKey = $campaignId . '_' . $language;
        $page = $this->pluginData->get($userId, self::PLUGIN_NAME, self::DATA_TYPE_PAGE, $pageKey);
        if ($page === null) {
            return $this->json(
                ['success' => false, 'error' => 'Page not found for language. Generate page first.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $requestedSlug = isset($data['slug']) ? trim((string) $data['slug']) : '';
        $active = !array_key_exists('active', $data) || (bool) $data['active'];

        $publishedEntries = $this->pluginData->listWithKeys($userId, self::PLUGIN_NAME, self::DATA_TYPE_PUBLIC_PAGE);
        $existingKey = null;
        $existingData = null;
        foreach ($publishedEntries as $entry) {
            $entryData = $entry['data'] ?? [];
            if (($entryData['campaign_id'] ?? '') === $campaignId && ($entryData['language'] ?? '') === $language) {
                $existingKey = $entry['key'];
                $existingData = $entryData;
                break;
            }
        }

        $slug = $requestedSlug;
        if ($slug === '') {
            $slug = (string) ($existingData['slug'] ?? $this->generatePublicSlug($campaignId, $language));
        }
        if (!preg_match('/^[a-z0-9-]{3,120}$/', $slug)) {
            return $this->json(
                ['success' => false, 'error' => 'Invalid slug. Use 3-120 chars: a-z, 0-9, hyphen.'],
                Response::HTTP_BAD_REQUEST,
            );
        }
        $slug = strtolower($slug);

        $ignoreId = null;
        if ($existingKey !== null) {
            $existingRecord = $this->pluginDataRepository->findOneByKey($userId, self::PLUGIN_NAME, self::DATA_TYPE_PUBLIC_PAGE, $existingKey);
            $ignoreId = $existingRecord?->getId();
        }
        $slug = $this->ensureUniquePublicSlug($slug, $ignoreId);

        $now = (new \DateTimeImmutable())->format('c');
        $publicData = [
            'slug' => $slug,
            'campaign_id' => $campaignId,
            'language' => $language,
            'title' => $campaign['title'] ?? $campaignId,
            'is_active' => $active,
            'published_at' => $existingData['published_at'] ?? $now,
            'updated_at' => $now,
            'view_count' => (int) ($existingData['view_count'] ?? 0),
            'last_accessed_at' => $existingData['last_accessed_at'] ?? null,
        ];

        $recordKey = $existingKey ?? ('pub_' . bin2hex(random_bytes(8)));
        $this->pluginData->set($userId, self::PLUGIN_NAME, self::DATA_TYPE_PUBLIC_PAGE, $recordKey, $publicData);

        return $this->json([
            'success' => true,
            'published' => array_merge(['key' => $recordKey], $publicData),
            'public_url' => '/api/v1/marketeer/public/' . $slug,
            'absolute_public_url' => rtrim($request->getSchemeAndHttpHost(), '/') . '/api/v1/marketeer/public/' . $slug,
        ]);
    }

    #[Route('/campaigns/{campaignId}/pages/{language}/publish', name: 'page_unpublish', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/pages/{language}/publish',
        summary: 'Unpublish a landing page slug URL',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\Response(response: 200, description: 'Page unpublished')]
    public function unpublishPage(
        int $userId,
        string $campaignId,
        string $language,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $publishedEntries = $this->pluginData->listWithKeys($userId, self::PLUGIN_NAME, self::DATA_TYPE_PUBLIC_PAGE);
        $deleted = false;
        foreach ($publishedEntries as $entry) {
            $entryData = $entry['data'] ?? [];
            if (($entryData['campaign_id'] ?? '') === $campaignId && ($entryData['language'] ?? '') === $language) {
                $this->pluginData->delete($userId, self::PLUGIN_NAME, self::DATA_TYPE_PUBLIC_PAGE, $entry['key']);
                $deleted = true;
            }
        }

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
        $campaign = $this->applyLanguageSpecificCtaUrl($campaign, $config, $language);

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

            $response = $this->callChat($user, $messages, 0.7, 8000);

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
        } catch (RateLimitExceededException $e) {
            return $this->rateLimitExceededResponse($user, $e);
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
        summary: 'Generate ad copy for a platform (Google RSA, LinkedIn, Instagram, Facebook)',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['platform'],
            properties: [
                new OA\Property(property: 'platform', type: 'string', enum: ['google', 'linkedin', 'instagram', 'facebook'], example: 'google'),
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
        $campaign = $this->applyLanguageSpecificCtaUrl($campaign, $config, $language);

        $allowedPlatforms = ['google', 'linkedin', 'instagram', 'facebook'];
        if (!in_array($platform, $allowedPlatforms, true)) {
            return $this->json(
                ['success' => false, 'error' => 'Invalid platform. Allowed: ' . implode(', ', $allowedPlatforms)],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $campaign['cta_url'] = $campaign['cta_url'] ?? $config['cta_url'];
            $messages = $this->contentGenerator->buildAdCopyPrompt($campaign, $config, $language, $platform);
            $response = $this->callChat($user, $messages, 0.6, 4000);
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
        } catch (RateLimitExceededException $e) {
            return $this->rateLimitExceededResponse($user, $e);
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
            $response = $this->callChat($user, $messages, 0.5, 4000);
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
        } catch (RateLimitExceededException $e) {
            return $this->rateLimitExceededResponse($user, $e);
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

            $result = $this->generateImageForUser($user, $imagePrompt);

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
        } catch (RateLimitExceededException $e) {
            return $this->rateLimitExceededResponse($user, $e);
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
    // Video Generation
    // =========================================================================

    #[Route('/campaigns/{campaignId}/generate-video', name: 'generate_video', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/generate-video',
        summary: 'Generate a short promotional video clip (optional collateral)',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'description', type: 'string', example: 'A sleek motion-graphics intro showing the brand logo with teal accents'),
                new OA\Property(property: 'language', type: 'string', example: 'en'),
                new OA\Property(property: 'duration', type: 'integer', enum: [4, 6, 8], example: 6),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Video generated')]
    public function generateVideo(
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
        $duration = $data['duration'] ?? 6;
        $description = $data['description'] ?? null;

        if (!in_array($duration, [4, 6, 8], true)) {
            return $this->json(
                ['success' => false, 'error' => 'Invalid duration. Allowed: 4, 6, 8 seconds'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $prompt = $this->contentGenerator->buildVideoPrompt($campaign, $config, $description);

            $result = $this->generateVideoForUser($user, $prompt, [
                'duration' => $duration,
            ]);

            if (empty($result['videos'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'No video returned by provider',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $videoData = $result['videos'][0];
            $filename = $this->landingPageService->saveVideoFile(
                $userId,
                $campaignId,
                $language,
                'promo',
                $videoData,
            );

            $this->adCopyService->saveCollateral($userId, $campaignId, 'video_promo', $language, [
                'file' => $filename,
                'prompt' => $prompt,
                'duration' => $duration,
                'provider' => $result['provider'] ?? null,
            ]);

            $this->logger->info('Marketeer video generated', [
                'user_id' => $userId,
                'campaign' => $campaignId,
                'duration' => $duration,
                'filename' => $filename,
            ]);

            return $this->json([
                'success' => true,
                'file' => $filename,
                'type' => 'video_promo',
                'duration' => $duration,
                'provider' => $result['provider'] ?? null,
            ]);
        } catch (RateLimitExceededException $e) {
            return $this->rateLimitExceededResponse($user, $e);
        } catch (\Throwable $e) {
            $this->logger->error('Marketeer video generation failed', [
                'user_id' => $userId,
                'campaign' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Video generation failed: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/campaigns/{campaignId}/share-video', name: 'share_video', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/user/{userId}/plugins/marketeer/campaigns/{campaignId}/share-video',
        summary: 'Copy a video from one language to all other campaign languages',
        security: [['ApiKey' => []]],
        tags: ['Marketeer Plugin']
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            required: ['source_language'],
            properties: [
                new OA\Property(property: 'source_language', type: 'string', example: 'en'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Video shared to all languages')]
    public function shareVideo(
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
        $sourceLang = $data['source_language'] ?? null;
        $targetLangs = $campaign['languages'] ?? ['en'];

        if ($sourceLang === null || !in_array($sourceLang, $targetLangs, true)) {
            return $this->json(['success' => false, 'error' => 'Invalid source language'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $copied = $this->landingPageService->copyVideoToLanguages($userId, $campaignId, $sourceLang, $targetLangs);

            $sourceCollateral = $this->adCopyService->getCollateral($userId, $campaignId, 'video_promo', $sourceLang);
            if ($sourceCollateral !== null) {
                foreach ($targetLangs as $lang) {
                    if ($lang === $sourceLang) {
                        continue;
                    }
                    $copy = $sourceCollateral;
                    $copy['language'] = $lang;
                    $copy['shared_from'] = $sourceLang;
                    $this->adCopyService->saveCollateral($userId, $campaignId, 'video_promo', $lang, $copy);
                }
            }

            return $this->json([
                'success' => true,
                'copied' => $copied,
                'source' => $sourceLang,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to share video: ' . $e->getMessage(),
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
        $campaign = $this->applyLanguageSpecificCtaUrl($campaign, $config, $language);

        try {
            $messages = $this->contentGenerator->buildAdsCampaignStructurePrompt($campaign, $config, $language, $extraInstructions);
            $response = $this->callChat($user, $messages, 0.6, 6000);
            $structure = $this->contentGenerator->parseJsonResponse($response['content']);

            $structure['language'] = $language;
            $structure['generated_at'] = (new \DateTimeImmutable())->format('c');
            $structure['model'] = $response['model'] ?? null;
            $structure['provider'] = $response['provider'] ?? null;

            $this->adsPlannerService->deleteForLanguage($userId, $campaignId, $language);
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
        } catch (RateLimitExceededException $e) {
            return $this->rateLimitExceededResponse($user, $e);
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

        $config = $this->getPluginConfig($userId);
        $pages = $this->getCampaignPages($userId, $campaignId);

        $result = $this->complianceService->quickComplianceCheck($campaign, ['pages' => $pages], $config);

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
            $response = $this->callChat($user, $messages, 0.3, 4000);
            $review = $this->contentGenerator->parseJsonResponse($response['content']);

            return $this->json([
                'success' => true,
                'review' => $review,
                'model' => $response['model'] ?? null,
            ]);
        } catch (RateLimitExceededException $e) {
            return $this->rateLimitExceededResponse($user, $e);
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
            $response = $this->callChat($user, $messages, 0.3, 4000);
            $checklist = $this->contentGenerator->parseJsonResponse($response['content']);

            return $this->json([
                'success' => true,
                'checklist' => $checklist,
                'assets_summary' => $assets,
            ]);
        } catch (RateLimitExceededException $e) {
            return $this->rateLimitExceededResponse($user, $e);
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

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);

        $slug = $campaignId;

        $meta = $campaign;
        unset($meta['modal_content']);
        $zip->addFromString("{$slug}/campaign.json", json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $adCopies = $this->adCopyService->listAdCopy($userId, $campaignId);
        $socialPosts = $this->adCopyService->listSocialPosts($userId, $campaignId);

        foreach ($adCopies as $data) {
            $lang = $data['language'] ?? 'unknown';
            $zip->addFromString(
                "{$slug}/ad-copy/google_{$lang}.json",
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            );
        }

        foreach ($socialPosts as $data) {
            $platform = $data['platform'] ?? 'unknown';
            $lang = $data['language'] ?? 'unknown';
            $zip->addFromString(
                "{$slug}/ad-copy/{$platform}_{$lang}.json",
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            );
        }

        $adsCampaigns = $this->adsPlannerService->listForCampaign($userId, $campaignId);
        foreach ($adsCampaigns as $adsCamp) {
            $adsId = $adsCamp['id'] ?? 'unknown';
            $adsLang = $adsCamp['language'] ?? 'all';
            $adsName = preg_replace('/[^a-z0-9_-]/i', '_', $adsCamp['campaign_name'] ?? $adsId);

            $zip->addFromString(
                "{$slug}/google-ads/{$adsName}_{$adsLang}.json",
                json_encode($adsCamp, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            );

            $export = $this->adsPlannerService->exportKeywords($userId, $campaignId, $adsId);
            if (!empty($export['keywords'])) {
                $csvLines = ["Campaign,Ad Group,Keyword,Match Type"];
                $campName = $adsCamp['campaign_name'] ?? $adsId;

                foreach ($adsCamp['ad_groups'] ?? [] as $group) {
                    $groupName = $group['name'] ?? $group['ad_group_name'] ?? 'Default';
                    foreach ($group['keywords'] ?? [] as $kw) {
                        $keyword = is_array($kw) ? ($kw['keyword'] ?? '') : (string) $kw;
                        $matchType = is_array($kw) ? ($kw['match_type'] ?? 'Broad') : 'Broad';
                        if ($keyword === '') {
                            continue;
                        }
                        $csvLines[] = $this->csvRow([$campName, $groupName, $keyword, ucfirst($matchType)]);
                    }
                }

                $zip->addFromString(
                    "{$slug}/google-ads/{$adsName}_{$adsLang}_keywords.csv",
                    implode("\n", $csvLines) . "\n",
                );
            }

            if (!empty($export['negative_keywords'])) {
                $negLines = ["Campaign,Keyword"];
                $campName = $adsCamp['campaign_name'] ?? $adsId;
                foreach ($export['negative_keywords'] as $neg) {
                    $negLines[] = $this->csvRow([$campName, $neg]);
                }
                $zip->addFromString(
                    "{$slug}/google-ads/{$adsName}_{$adsLang}_negative_keywords.csv",
                    implode("\n", $negLines) . "\n",
                );
            }

            $this->addGoogleAdsEditorCsv($zip, $slug, $adsCamp);
        }

        $zip->close();

        $response = new StreamedResponse(function () use ($zipPath): void {
            readfile($zipPath);
            @unlink($zipPath);
        });

        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$campaignId}.zip\"");

        return $response;
    }

    /**
     * @param string[] $fields
     */
    private function csvRow(array $fields): string
    {
        return implode(',', array_map(
            fn (string $f) => '"' . str_replace('"', '""', $f) . '"',
            $fields,
        ));
    }

    /**
     * @param array<string, mixed> $adsCamp
     */
    private function addGoogleAdsEditorCsv(\ZipArchive $zip, string $slug, array $adsCamp): void
    {
        $csv = GoogleAdsEditorCsvBuilder::build($adsCamp);
        if ($csv === '') {
            return;
        }

        $adsId = $adsCamp['id'] ?? 'unknown';
        $adsLang = $adsCamp['language'] ?? 'all';
        $adsName = preg_replace('/[^a-z0-9_-]/i', '_', $adsCamp['campaign_name'] ?? $adsId);

        $zip->addFromString(
            "{$slug}/google-ads/{$adsName}_{$adsLang}_google-ads-editor.csv",
            $csv,
        );
    }

    #[Route('/campaigns/{campaignId}/file/{filePath}', name: 'campaign_file_serve', requirements: ['filePath' => '.+'], methods: ['GET'])]
    public function serveFile(
        int $userId,
        string $campaignId,
        string $filePath,
        Request $request,
        #[CurrentUser] ?User $user,
    ): Response {
        $refreshedAccessToken = null;
        if (!$this->canAccessPlugin($user, $userId)) {
            $refreshResult = $this->tryRefreshUserSession($request, $userId);
            $user = $refreshResult['user'] ?? null;
            $refreshedAccessToken = $refreshResult['access_token'] ?? null;
        }

        if (!$this->canAccessPlugin($user, $userId)) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $campaignDir = $this->landingPageService->getCampaignDir($userId, $campaignId);
        $fullPath = $campaignDir . '/' . $filePath;
        $realBase = realpath($campaignDir);
        $realFile = realpath($fullPath);

        if ($realBase === false || $realFile === false || !str_starts_with($realFile, $realBase)) {
            return $this->json(['success' => false, 'error' => 'File not found'], Response::HTTP_NOT_FOUND);
        }

        $mimeTypes = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml', 'mp4' => 'video/mp4', 'html' => 'text/html', 'txt' => 'text/plain', 'css' => 'text/css', 'js' => 'application/javascript'];
        $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';

        $response = new Response(file_get_contents($realFile), 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=3600',
        ]);

        if (is_string($refreshedAccessToken) && $refreshedAccessToken !== '') {
            $response->headers->setCookie($this->tokenService->createAccessCookie($refreshedAccessToken));
        }

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
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function applyLanguageSpecificCtaUrl(array $campaign, array $config, string $language): array
    {
        $effectiveCtaUrl = (string) ($campaign['cta_url'] ?? $config['cta_url'] ?? '');
        $langCtaUrl = $campaign['cta_urls'][$language] ?? null;
        if (is_string($langCtaUrl) && trim($langCtaUrl) !== '') {
            $effectiveCtaUrl = trim($langCtaUrl);
        }

        if ($effectiveCtaUrl === '') {
            return $campaign;
        }

        $campaign['cta_url'] = $effectiveCtaUrl;

        if (isset($campaign['ctas'][0]) && is_array($campaign['ctas'][0])) {
            $campaign['ctas'][0]['url'] = $effectiveCtaUrl;
        }

        return $campaign;
    }

    /**
     * @return array{user: ?User, access_token: ?string}
     */
    private function tryRefreshUserSession(Request $request, int $expectedUserId): array
    {
        $refreshToken = $request->cookies->get(TokenService::REFRESH_COOKIE);
        if (!is_string($refreshToken) || $refreshToken === '') {
            return ['user' => null, 'access_token' => null];
        }

        $result = $this->tokenService->refreshTokens($refreshToken);
        $user = $result['user'] ?? null;
        if (!$user instanceof User || $user->getId() !== $expectedUserId) {
            return ['user' => null, 'access_token' => null];
        }

        return [
            'user' => $user,
            'access_token' => is_string($result['access_token'] ?? null) ? $result['access_token'] : null,
        ];
    }

    /**
     * @return array{
     *     default_language: string,
     *     cta_url: string,
     *     brand_name: string,
     *     privacy_policy_url: string,
     *     imprint_url: string,
     *     gtm_id: string,
     *     gads_conversion_id: string,
     *     default_accent_color: string,
     *     default_brand_logo_url: string,
     *     default_color_scheme: string,
     *     default_background_style: string,
     *     default_background_color: string,
     *     default_background_secondary_color: string,
     *     default_background_image_url: string,
     *     default_background_image_position: string,
     *     default_background_image_size: string,
     *     default_background_icon_url: string,
     *     default_background_icon_position: string,
     *     default_background_icon_size_percent: string,
     *     default_background_icon_opacity: string,
     *     default_background_motion_intensity: string,
     *     default_hero_text_align: string,
     *     default_background_overlay_opacity: string
     * }
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
            'default_accent_color' => '#00b79d',
            'default_brand_logo_url' => '',
            'default_color_scheme' => 'dark backgrounds (#111) with vibrant accent',
            'default_background_style' => 'parallax',
            'default_background_color' => '#111111',
            'default_background_secondary_color' => '#1f2937',
            'default_background_image_url' => '',
            'default_background_image_position' => 'center center',
            'default_background_image_size' => 'cover',
            'default_background_icon_url' => '',
            'default_background_icon_position' => 'center center',
            'default_background_icon_size_percent' => '20',
            'default_background_icon_opacity' => '0.35',
            'default_background_motion_intensity' => 'medium',
            'default_hero_text_align' => 'center',
            'default_background_overlay_opacity' => '0.48',
        ];

        $config = [];
        foreach ($defaults as $key => $default) {
            $config[$key] = $this->configRepository->getValue($userId, self::CONFIG_GROUP, $key) ?? $default;
        }

        $promptKeys = ['landing_page_prompt', 'image_prompt', 'video_prompt'];
        foreach ($promptKeys as $promptKey) {
            $promptData = $this->pluginData->get($userId, self::PLUGIN_NAME, 'config', $promptKey);
            $config[$promptKey] = $promptData['prompt'] ?? '';
        }

        return $config;
    }

    /**
     * @param array<int, array{role: string, content: string}> $messages
     * @return array<string, mixed>
     */
    private function callChat(User $user, array $messages, float $temperature, int $maxTokens): array
    {
        $this->assertRateLimit($user, 'MESSAGES');

        $userId = (int) $user->getId();
        $modelId = $this->modelConfigService->getDefaultModel('CHAT', $userId);
        $provider = $modelId ? $this->modelConfigService->getProviderForModel($modelId) : null;
        $modelName = $modelId ? $this->modelConfigService->getModelName($modelId) : null;

        $response = $this->aiFacade->chat(
            $messages,
            $userId,
            [
                'provider' => $provider,
                'model' => $modelName,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ],
        );

        $inputText = implode("\n\n", array_map(
            static fn (array $message): string => sprintf(
                '%s: %s',
                (string) ($message['role'] ?? 'user'),
                (string) ($message['content'] ?? '')
            ),
            $messages
        ));

        $this->rateLimitService->recordUsage($user, 'MESSAGES', [
            'provider' => $response['provider'] ?? $provider ?? '',
            'model' => $response['model'] ?? $modelName ?? '',
            'input_text' => $inputText,
            'response_text' => (string) ($response['content'] ?? ''),
            'source' => 'MARKETEER',
        ]);

        return $response;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function generateImageForUser(User $user, string $prompt, array $options = []): array
    {
        $this->assertRateLimit($user, 'IMAGES');

        $userId = (int) $user->getId();
        if (empty($options['provider']) || empty($options['model'])) {
            $modelId = $this->modelConfigService->getDefaultModel('TEXT2PIC', $userId);
            if ($modelId) {
                $options['provider'] ??= $this->modelConfigService->getProviderForModel($modelId);
                $options['model'] ??= $this->modelConfigService->getModelName($modelId);
            }
        }

        $result = $this->aiFacade->generateImage((string) $prompt, $userId, $options);

        $this->rateLimitService->recordUsage($user, 'IMAGES', [
            'provider' => $result['provider'] ?? '',
            'model' => $result['model'] ?? '',
            'input_text' => $prompt,
            'source' => 'MARKETEER',
        ]);

        return $result;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function generateVideoForUser(User $user, string $prompt, array $options = []): array
    {
        $this->assertRateLimit($user, 'VIDEOS');

        $userId = (int) $user->getId();
        if (empty($options['provider']) || empty($options['model'])) {
            $modelId = $this->modelConfigService->getDefaultModel('TEXT2VID', $userId);
            if ($modelId) {
                $options['provider'] ??= $this->modelConfigService->getProviderForModel($modelId);
                $options['model'] ??= $this->modelConfigService->getModelName($modelId);
            }
        }

        $result = $this->aiFacade->generateVideo((string) $prompt, $userId, $options);

        $this->rateLimitService->recordUsage($user, 'VIDEOS', [
            'provider' => $result['provider'] ?? '',
            'model' => $result['model'] ?? '',
            'input_text' => $prompt,
            'source' => 'MARKETEER',
        ]);

        return $result;
    }

    private function assertRateLimit(User $user, string $action): void
    {
        $check = $this->rateLimitService->checkLimit($user, $action);
        if (($check['allowed'] ?? true) !== true) {
            throw new RateLimitExceededException($action, (int) ($check['used'] ?? 0), (int) ($check['limit'] ?? 0));
        }
    }

    private function rateLimitExceededResponse(User $user, RateLimitExceededException $exception): JsonResponse
    {
        $message = $exception->getMessage();
        if (!preg_match('/Rate limit exceeded for ([A-Z_]+)\. Used: (\d+)\/(\d+)/', $message, $matches)) {
            return $this->json([
                'success' => false,
                'code' => 'rate_limit_exceeded',
                'error' => $message,
                'upgrade_url' => '/subscription',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return $this->json([
            'success' => false,
            'code' => 'rate_limit_exceeded',
            'error' => $message,
            'action' => $matches[1],
            'used' => (int) $matches[2],
            'limit' => (int) $matches[3],
            'current_level' => $user->getRateLimitLevel(),
            'upgrade_url' => '/subscription',
        ], Response::HTTP_TOO_MANY_REQUESTS);
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

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getPublishedPagesForCampaign(int $userId, string $campaignId): array
    {
        $entries = $this->pluginData->listWithKeys($userId, self::PLUGIN_NAME, self::DATA_TYPE_PUBLIC_PAGE);
        $result = [];
        foreach ($entries as $entry) {
            $data = $entry['data'] ?? [];
            if (($data['campaign_id'] ?? '') !== $campaignId) {
                continue;
            }
            $language = (string) ($data['language'] ?? '');
            if ($language === '') {
                continue;
            }
            $result[$language] = array_merge(['key' => $entry['key']], $data);
        }

        return $result;
    }

    private function generatePublicSlug(string $campaignId, string $language): string
    {
        $suffix = substr(bin2hex(random_bytes(4)), 0, 6);

        return strtolower($campaignId . '-' . $language . '-' . $suffix);
    }

    private function getDefaultStylePrompt(string $backgroundStyle): string
    {
        return match (strtolower(trim($backgroundStyle))) {
            'solid' => 'Use a clean premium layout with a calm static background and strong typography contrast. Keep it readable and conversion-focused.',
            'image_cover' => 'Create a cinematic hero with a full-cover background image and a subtle dark overlay. Keep content readable and centered around one clear CTA.',
            'icon_fixed' => 'Use a minimal static background with one decorative icon/shape in the back. The icon should support the topic but never distract from the CTA.',
            'icon_floating' => 'Use a playful floating icon in the background with gentle motion. Keep animation subtle enough for business pages and maintain excellent readability.',
            'glass_3d_ball' => 'Create a modern glassmorphism hero with a pseudo-3D bouncing ball scene in the background. Keep text sharp, premium, and easy to read.',
            default => 'Use layered parallax buzzword rows with smooth motion and strong visual hierarchy. Keep the hero text highly readable and conversion-driven.',
        };
    }

    private function ensureUniquePublicSlug(string $slug, ?int $ignoreRecordId = null): string
    {
        $candidate = $slug;
        $attempt = 0;
        while ($this->publicSlugExists($candidate, $ignoreRecordId)) {
            ++$attempt;
            $suffix = substr(bin2hex(random_bytes(3)), 0, 4);
            $candidate = substr($slug, 0, 110) . '-' . $suffix;
            if ($attempt > 10) {
                $candidate = 'page-' . substr(bin2hex(random_bytes(8)), 0, 10);
                break;
            }
        }

        return $candidate;
    }

    private function publicSlugExists(string $slug, ?int $ignoreRecordId = null): bool
    {
        $entries = $this->pluginDataRepository->findBy([
            'pluginName' => self::PLUGIN_NAME,
            'dataType' => self::DATA_TYPE_PUBLIC_PAGE,
        ]);

        foreach ($entries as $entry) {
            if (!$entry instanceof PluginData) {
                continue;
            }
            if ($ignoreRecordId !== null && $entry->getId() === $ignoreRecordId) {
                continue;
            }
            $data = $entry->getData();
            if (($data['slug'] ?? '') === $slug && (bool) ($data['is_active'] ?? true)) {
                return true;
            }
        }

        return false;
    }
}
