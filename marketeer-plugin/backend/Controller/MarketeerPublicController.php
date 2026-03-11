<?php

declare(strict_types=1);

namespace Plugin\Marketeer\Controller;

use App\Entity\PluginData;
use App\Entity\User;
use App\Repository\PluginDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Marketeer\Service\LandingPageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/v1/marketeer', name: 'api_marketeer_public_')]
class MarketeerPublicController extends AbstractController
{
    private const PLUGIN_NAME = 'marketeer';
    private const DATA_TYPE_PUBLIC_PAGE = 'public_page';

    public function __construct(
        private PluginDataRepository $pluginDataRepository,
        private LandingPageService $landingPageService,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/public/{slug}', name: 'page', methods: ['GET'])]
    public function publicPage(string $slug): Response
    {
        $slug = strtolower(trim($slug));
        if (!preg_match('/^[a-z0-9-]{3,120}$/', $slug)) {
            return new Response('Invalid public slug', Response::HTTP_BAD_REQUEST, ['Content-Type' => 'text/plain']);
        }

        $entry = $this->findBySlug($slug);
        if ($entry === null) {
            return new Response('Published page not found', Response::HTTP_NOT_FOUND, ['Content-Type' => 'text/plain']);
        }

        $data = $entry->getData();
        if (!(bool) ($data['is_active'] ?? true)) {
            return new Response('Published page is inactive', Response::HTTP_GONE, ['Content-Type' => 'text/plain']);
        }

        $userId = $entry->getUserId();
        $campaignId = (string) ($data['campaign_id'] ?? '');
        $language = (string) ($data['language'] ?? 'en');
        if ($campaignId === '') {
            return new Response('Published page is invalid', Response::HTTP_NOT_FOUND, ['Content-Type' => 'text/plain']);
        }

        $filePath = $this->landingPageService->getCampaignDir($userId, $campaignId) . '/' . $language . '/index.html';
        if (!is_file($filePath)) {
            return new Response('Published file not found', Response::HTTP_NOT_FOUND, ['Content-Type' => 'text/plain']);
        }

        $data['view_count'] = ((int) ($data['view_count'] ?? 0)) + 1;
        $data['last_accessed_at'] = (new \DateTimeImmutable())->format('c');
        $entry->setData($data);
        $this->em->flush();

        return new Response((string) file_get_contents($filePath), Response::HTTP_OK, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'public, max-age=120',
        ]);
    }

    #[Route('/admin/public-pages', name: 'admin_list', methods: ['GET'])]
    public function listPublishedPages(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null || !$user->isAdmin()) {
            return $this->json(['success' => false, 'error' => 'Admin access required'], Response::HTTP_FORBIDDEN);
        }

        $entries = $this->pluginDataRepository->findBy([
            'pluginName' => self::PLUGIN_NAME,
            'dataType' => self::DATA_TYPE_PUBLIC_PAGE,
        ]);

        $items = [];
        foreach ($entries as $entry) {
            if (!$entry instanceof PluginData) {
                continue;
            }
            $data = $entry->getData();
            $items[] = [
                'id' => $entry->getId(),
                'key' => $entry->getDataKey(),
                'user_id' => $entry->getUserId(),
                'slug' => $data['slug'] ?? null,
                'campaign_id' => $data['campaign_id'] ?? null,
                'language' => $data['language'] ?? null,
                'title' => $data['title'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'published_at' => $data['published_at'] ?? null,
                'updated_at' => $data['updated_at'] ?? null,
                'view_count' => (int) ($data['view_count'] ?? 0),
                'last_accessed_at' => $data['last_accessed_at'] ?? null,
                'public_url' => '/api/v1/marketeer/public/' . ($data['slug'] ?? ''),
            ];
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) ($b['published_at'] ?? ''), (string) ($a['published_at'] ?? '')));

        return $this->json([
            'success' => true,
            'count' => count($items),
            'pages' => $items,
        ]);
    }

    private function findBySlug(string $slug): ?PluginData
    {
        $entries = $this->pluginDataRepository->findBy([
            'pluginName' => self::PLUGIN_NAME,
            'dataType' => self::DATA_TYPE_PUBLIC_PAGE,
        ]);

        foreach ($entries as $entry) {
            if (!$entry instanceof PluginData) {
                continue;
            }
            $data = $entry->getData();
            if (($data['slug'] ?? '') === $slug) {
                return $entry;
            }
        }

        return null;
    }
}
