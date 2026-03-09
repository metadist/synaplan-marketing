<?php

declare(strict_types=1);

namespace Plugin\Marketeer\Service;

use App\Service\File\UserUploadPathBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Manages file storage for generated landing pages, images, and keyword files.
 *
 * Directory structure under user uploads:
 *   marketeer/{campaign-slug}/{language}/
 *     ├── index.html
 *     ├── keywords.txt
 *     └── images/
 *         ├── hero.png
 *         ├── linkedin.png
 *         ├── instagram.png
 *         └── og.png
 */
final readonly class LandingPageService
{
    public function __construct(
        #[Autowire('%app.upload_dir%')] private string $uploadDir,
        private UserUploadPathBuilder $pathBuilder,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param string[] $languages
     */
    public function ensureCampaignDirectories(int $userId, string $campaignSlug, array $languages): void
    {
        foreach ($languages as $lang) {
            $dir = $this->getCampaignLangDir($userId, $campaignSlug, $lang);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $imgDir = $dir . '/images';
            if (!is_dir($imgDir)) {
                mkdir($imgDir, 0755, true);
            }
        }
    }

    public function saveHtmlFile(int $userId, string $campaignSlug, string $language, string $html): string
    {
        $dir = $this->getCampaignLangDir($userId, $campaignSlug, $language);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filePath = $dir . '/index.html';
        file_put_contents($filePath, $html);

        return "marketeer/{$campaignSlug}/{$language}/index.html";
    }

    /**
     * @param string[] $keywords
     */
    public function saveKeywordsFile(int $userId, string $campaignSlug, string $language, array $keywords): string
    {
        $dir = $this->getCampaignLangDir($userId, $campaignSlug, $language);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filePath = $dir . '/keywords.txt';
        file_put_contents($filePath, implode("\n", $keywords) . "\n");

        return "marketeer/{$campaignSlug}/{$language}/keywords.txt";
    }

    /**
     * @param array{url?: string, base64?: string, data?: string} $imageData
     */
    public function saveImageFile(
        int $userId,
        string $campaignSlug,
        string $language,
        string $imageType,
        array $imageData,
    ): string {
        $dir = $this->getCampaignLangDir($userId, $campaignSlug, $language) . '/images';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $extension = 'png';
        $filename = "{$imageType}.{$extension}";
        $filePath = $dir . '/' . $filename;

        if (!empty($imageData['base64'])) {
            file_put_contents($filePath, base64_decode($imageData['base64']));
        } elseif (!empty($imageData['data'])) {
            file_put_contents($filePath, base64_decode($imageData['data']));
        } elseif (!empty($imageData['url'])) {
            $content = @file_get_contents($imageData['url']);
            if ($content !== false) {
                file_put_contents($filePath, $content);
            }
        }

        return "marketeer/{$campaignSlug}/{$language}/images/{$filename}";
    }

    /**
     * @return array<int, array{path: string, size: int, modified: string}>
     */
    public function listCampaignFiles(int $userId, string $campaignSlug): array
    {
        $baseDir = $this->getCampaignDir($userId, $campaignSlug);
        if (!is_dir($baseDir)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS),
        );

        $prefixLen = strlen($this->getUserUploadDir($userId) . '/');

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = substr($file->getPathname(), $prefixLen);
                $files[] = [
                    'path' => $relativePath,
                    'size' => $file->getSize(),
                    'modified' => date('c', $file->getMTime()),
                ];
            }
        }

        usort($files, fn (array $a, array $b) => strcmp($a['path'], $b['path']));

        return $files;
    }

    public function deleteCampaignDirectory(int $userId, string $campaignSlug): void
    {
        $dir = $this->getCampaignDir($userId, $campaignSlug);
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($dir);

        $this->logger->info('Marketeer campaign directory deleted', [
            'user_id' => $userId,
            'campaign' => $campaignSlug,
            'path' => $dir,
        ]);
    }

    public function createZip(int $userId, string $campaignSlug): string
    {
        $campaignDir = $this->getCampaignDir($userId, $campaignSlug);
        $zipPath = sys_get_temp_dir() . "/marketeer_{$campaignSlug}_" . uniqid() . '.zip';

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($campaignDir, \RecursiveDirectoryIterator::SKIP_DOTS),
        );

        $prefixLen = strlen($campaignDir) + 1;

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = substr($file->getPathname(), $prefixLen);
                $zip->addFile($file->getPathname(), $campaignSlug . '/' . $relativePath);
            }
        }

        $zip->close();

        return $zipPath;
    }

    public function getCampaignDir(int $userId, string $campaignSlug): string
    {
        return $this->getUserUploadDir($userId) . '/marketeer/' . $campaignSlug;
    }

    private function getCampaignLangDir(int $userId, string $campaignSlug, string $language): string
    {
        return $this->getCampaignDir($userId, $campaignSlug) . '/' . $language;
    }

    private function getUserUploadDir(int $userId): string
    {
        return $this->uploadDir . '/' . $this->pathBuilder->buildUserBaseRelativePath($userId);
    }
}
