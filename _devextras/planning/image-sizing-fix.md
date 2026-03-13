# Marketeer Image Sizing Fix

**Status:** Planned (not yet implemented)
**Created:** 2026-03-13

## Problem

Generated marketing images do not match the requested dimensions. A request for a `728x90` leaderboard banner or a `1200x627` LinkedIn image always produces a `1024x1024` square.

### Root Cause

Two gaps in the pipeline:

1. **No size option passed to AI providers.** The `generateImage` call in `MarketeerController` (line ~1363) passes only the prompt text to `generateImageForUser()` with an empty `$options` array. Every provider falls back to its default of `1024x1024`.

2. **No post-processing.** The `saveImageFile` method in `LandingPageService` writes the raw bytes from the AI provider directly to disk. There is no resizing, cropping, or any image manipulation.

The target dimensions (e.g. "728x90 pixels") appear only as natural language text inside the prompt, which image generation models largely ignore for their actual output resolution.

---

## Current Code Flow

```
MarketeerController::generateImage()
  -> reads $imageType from request (e.g. "banner_wide")
  -> ContentGenerator::buildImagePrompt() puts "728x90 pixels" in prompt TEXT
  -> generateImageForUser($user, $imagePrompt)          // NO $options['size']!
     -> AiFacade::generateImage($prompt, $userId, [])    // empty options
        -> Provider::generateImage($prompt, [])           // defaults to 1024x1024
  -> LandingPageService::saveImageFile()                  // writes raw bytes, no resize
```

### Key file locations (in synaplan-marketing repo)

| File | Role |
|------|------|
| `marketeer-plugin/backend/Controller/MarketeerController.php` | `generateImage()` endpoint (line ~1331), `generateImageForUser()` helper (line ~2461) |
| `marketeer-plugin/backend/Service/ContentGenerator.php` | `buildImagePrompt()` (line ~759), dimension strings in prompt (line ~768) |
| `marketeer-plugin/backend/Service/LandingPageService.php` | `saveImageFile()` (line ~128) -- writes raw bytes |
| `marketeer-plugin/frontend/index.js` | `IMG_TYPES` array with display dimensions (line ~21) |

### Key file locations (in synaplan core -- READ ONLY, do not modify)

| File | Role |
|------|------|
| `backend/src/AI/Service/AiFacade.php` | `generateImage()` (line ~410) -- forwards `$options` to provider |
| `backend/src/AI/Provider/OpenAIProvider.php` | `generateImage()` (line ~338) -- uses `$options['size']` |
| `backend/src/AI/Provider/GoogleProvider.php` | `generateImage()` (line ~214) -- uses `$options['aspect_ratio']` |
| `backend/src/AI/Provider/TheHiveProvider.php` | `generateImage()` (line ~113) -- uses `$options['size']` or `width`/`height` |

---

## Provider Size Capabilities

### OpenAI DALL-E 3
- **Option:** `$options['size']` as string `"WIDTHxHEIGHT"`
- **Supported values:** `1024x1024`, `1024x1792`, `1792x1024` (ONLY these three)
- **Default:** `1024x1024`
- **Arbitrary sizes:** NO

### OpenAI gpt-image-1
- **Option:** `$options['size']` as string `"WIDTHxHEIGHT"`
- **Supported values:** `1024x1024`, `1024x1536`, `1536x1024`, `auto` (up to 4096x4096 in auto)
- **Default:** `1024x1024`
- **Arbitrary sizes:** Partially -- still constrained to specific presets

### Google Imagen 3.0 (Vertex API)
- **Option:** `$options['aspect_ratio']` as string ratio
- **Supported values:** `1:1`, `3:4`, `4:3`, `9:16`, `16:9`
- **Default:** `1:1`
- **Arbitrary sizes:** NO -- only aspect ratios, actual pixel size determined by model

### Google Gemini Native Image
- **Option:** `$options['aspect_ratio']` in `generationConfig.imageConfig`
- **Supported values:** Similar ratio set to Imagen
- **Default:** None specified (model chooses)
- **Arbitrary sizes:** NO

### TheHive (Flux Schnell, SDXL)
- **Option:** `$options['size']` as `"WIDTHxHEIGHT"` or `$options['width']`/`$options['height']`
- **Supported values:** Arbitrary (within model limits, typically up to 1024-2048)
- **Default:** `1024x1024` per model
- **Arbitrary sizes:** YES (parsed via `parseDimensions()`)

### Key Takeaway

**No provider can produce a `728x90` or `300x250` image natively.** Even TheHive, which accepts arbitrary dimensions, may produce poor results at extreme aspect ratios like 8:1. Post-generation crop/resize is mandatory.

---

## Solution: Two-Layer Approach

### Layer 1: Request the best native size from the provider

Map each `imageType` to the closest provider-supported size. This maximizes quality before cropping by generating at the right aspect ratio when possible.

| Image Type | Target Pixels | Best Provider Size | Rationale |
|-----------|---------------|-------------------|-----------|
| `hero` | 1920x1080 | `1792x1024` | Wide landscape |
| `linkedin` | 1200x627 | `1792x1024` | Wide landscape (~1.9:1) |
| `og` | 1200x630 | `1792x1024` | Wide landscape (~1.9:1) |
| `instagram` | 1080x1080 | `1024x1024` | Square |
| `icon` | 512x512 | `1024x1024` | Square |
| `banner_wide` | 728x90 | `1792x1024` | Widest available (~8:1 target) |
| `banner_rect` | 300x250 | `1024x1024` | Near-square (~1.2:1) |
| `banner_sky` | 160x600 | `1024x1792` | Tall portrait (~1:3.75) |

### Layer 2: Post-process with PHP GD

After saving the raw image, crop and resize to exact target dimensions:

1. Load image with `imagecreatefromstring(file_get_contents($path))`
2. Compare source aspect ratio to target aspect ratio
3. **Center-crop** the source to match the target aspect ratio (trim excess)
4. **Resize** the cropped region to exact target pixels via `imagecopyresampled()`
5. Save back as PNG with `imagepng()`

```
Source: 1792x1024 (ratio 1.75:1)
Target: 728x90   (ratio 8.09:1)

Step 1: Center-crop source to 8.09:1 ratio
        -> crop to 1792x221 (full width, narrow horizontal strip from center)
Step 2: Resize 1792x221 -> 728x90
        -> final output is exactly 728x90
```

### Layer 3: Improve prompt composition hints

For extreme aspect ratios, add explicit guidance in the prompt so the AI composes the image in a way that survives center-cropping:

```
Current:  "Dimensions: 728x90 pixels (leaderboard banner ad)"
Improved: "Dimensions: 728x90 pixels (leaderboard banner ad). Compose for an 
           extremely wide horizontal strip (aspect ratio ~8:1). Place the main 
           subject centered with generous margins on all sides -- the image will 
           be center-cropped to this ratio."
```

---

## Implementation Details

### Step 1: Add dimension constants to ContentGenerator

**File:** `marketeer-plugin/backend/Service/ContentGenerator.php`

Add a public constant:
```php
public const IMAGE_DIMENSIONS = [
    'hero'        => [1920, 1080],
    'linkedin'    => [1200, 627],
    'instagram'   => [1080, 1080],
    'og'          => [1200, 630],
    'icon'        => [512, 512],
    'banner_wide' => [728, 90],
    'banner_rect' => [300, 250],
    'banner_sky'  => [160, 600],
];
```

Add a static method:
```php
/**
 * Returns the best provider-native size string (e.g. "1792x1024") for the
 * given image type. Providers that only support fixed sizes will get the
 * closest available option.
 */
public static function getProviderSizeOption(string $imageType): string
{
    $dims = self::IMAGE_DIMENSIONS[$imageType] ?? [1920, 1080];
    $ratio = $dims[0] / $dims[1];

    // Provider-supported sizes (OpenAI DALL-E 3 as baseline)
    // 1024x1024 = 1.0, 1792x1024 = 1.75, 1024x1792 = 0.57
    if ($ratio > 1.3) {
        return '1792x1024'; // landscape
    }
    if ($ratio < 0.77) {
        return '1024x1792'; // portrait
    }
    return '1024x1024'; // square-ish
}
```

### Step 2: Pass size option in controller

**File:** `marketeer-plugin/backend/Controller/MarketeerController.php`

Change around line 1363 from:
```php
$result = $this->generateImageForUser($user, $imagePrompt);
```
To:
```php
$providerSize = ContentGenerator::getProviderSizeOption($imageType);
$result = $this->generateImageForUser($user, $imagePrompt, ['size' => $providerSize]);
```

### Step 3: Add cropAndResize to LandingPageService

**File:** `marketeer-plugin/backend/Service/LandingPageService.php`

```php
/**
 * Center-crop and resize an image file to exact target dimensions using GD.
 * Overwrites the file in place. On failure, leaves the original untouched.
 */
private function cropAndResize(string $filePath, int $targetWidth, int $targetHeight): void
{
    $raw = file_get_contents($filePath);
    if ($raw === false) {
        return;
    }

    $src = @imagecreatefromstring($raw);
    if ($src === false) {
        $this->logger->warning('GD: failed to load image for resize', ['path' => $filePath]);
        return;
    }

    $srcW = imagesx($src);
    $srcH = imagesy($src);

    if ($srcW === $targetWidth && $srcH === $targetHeight) {
        imagedestroy($src);
        return;
    }

    $srcRatio = $srcW / $srcH;
    $tgtRatio = $targetWidth / $targetHeight;

    if ($srcRatio > $tgtRatio) {
        // Source is wider: crop sides
        $cropH = $srcH;
        $cropW = (int) round($srcH * $tgtRatio);
        $cropX = (int) round(($srcW - $cropW) / 2);
        $cropY = 0;
    } else {
        // Source is taller: crop top/bottom
        $cropW = $srcW;
        $cropH = (int) round($srcW / $tgtRatio);
        $cropX = 0;
        $cropY = (int) round(($srcH - $cropH) / 2);
    }

    $dst = imagecreatetruecolor($targetWidth, $targetHeight);
    if ($dst === false) {
        imagedestroy($src);
        return;
    }

    // Preserve transparency for PNG
    imagealphablending($dst, false);
    imagesavealpha($dst, true);

    imagecopyresampled(
        $dst, $src,
        0, 0,
        $cropX, $cropY,
        $targetWidth, $targetHeight,
        $cropW, $cropH,
    );

    imagepng($dst, $filePath, 6); // quality 6 (0=no compression, 9=max)

    imagedestroy($src);
    imagedestroy($dst);
}
```

### Step 4: Call cropAndResize in saveImageFile

In `saveImageFile()`, after the file is written to disk, add:

```php
// Crop and resize to target dimensions
$dims = ContentGenerator::IMAGE_DIMENSIONS[$imageType] ?? null;
if ($dims !== null) {
    try {
        $this->cropAndResize($filePath, $dims[0], $dims[1]);
    } catch (\Throwable $e) {
        $this->logger->warning('Image crop/resize failed, keeping original', [
            'path' => $filePath,
            'error' => $e->getMessage(),
        ]);
    }
}
```

This requires passing `$imageType` into `saveImageFile()` -- the method already receives it as a parameter.

### Step 5: Improve prompt composition hints

In `ContentGenerator::buildImagePrompt()`, enhance the `$dimensions` match to include composition guidance:

```php
$dimensions = match ($imageType) {
    'linkedin' => '1200x627 pixels (LinkedIn). Compose for ~1.9:1 wide horizontal layout',
    'instagram' => '1080x1080 pixels (Instagram square). Compose for 1:1 square layout',
    'og' => '1200x630 pixels (Open Graph). Compose for ~1.9:1 wide horizontal layout',
    'icon' => '512x512 pixels (icon). Compose for 1:1 square, single centered element',
    'banner_wide' => '728x90 pixels (leaderboard banner). Compose for extremely wide ~8:1 horizontal strip. Center the subject with generous margins -- the image will be center-cropped',
    'banner_rect' => '300x250 pixels (medium rectangle). Compose for ~1.2:1 near-square layout',
    'banner_sky' => '160x600 pixels (skyscraper). Compose for very tall ~1:3.75 vertical strip. Center the subject with generous margins -- the image will be center-cropped',
    default => '1920x1080 pixels (hero banner). Compose for 16:9 wide landscape layout',
};
```

---

## Verification Checklist

After implementing:

- [ ] Generate a `banner_wide` image -- output file should be exactly 728x90
- [ ] Generate a `banner_sky` image -- output file should be exactly 160x600
- [ ] Generate an `instagram` image -- output file should be exactly 1080x1080
- [ ] Generate a `hero` image -- output file should be exactly 1920x1080
- [ ] Generate an `icon` image -- output file should be exactly 512x512
- [ ] Check with different providers (OpenAI, Google, TheHive) if configured
- [ ] Verify GD failure gracefully falls back to unprocessed image
- [ ] Run `make -C backend lint && make -C backend phpstan`

---

## Scope Boundaries

### Changes ONLY in marketeer-plugin (3 files)

| File | Change |
|------|--------|
| `marketeer-plugin/backend/Service/ContentGenerator.php` | `IMAGE_DIMENSIONS` constant, `getProviderSizeOption()`, improved prompts |
| `marketeer-plugin/backend/Controller/MarketeerController.php` | Pass `['size' => $providerSize]` to `generateImageForUser()` |
| `marketeer-plugin/backend/Service/LandingPageService.php` | `cropAndResize()` method, call it in `saveImageFile()` |

### NO changes to

- Synaplan core providers (OpenAI, Google, TheHive) -- they already support `size`/`aspect_ratio` in options
- No new dependencies -- PHP GD is bundled in the base Docker image (`synaplan-base-php`)
- No database/migration changes
- No frontend changes -- `IMG_TYPES` dimensions in `frontend/index.js` already match

### After implementation, sync to synaplan

```bash
rm -rf /wwwroot/synaplan/plugins/marketeer && \
cp -r /wwwroot/synaplan-marketing/marketeer-plugin /wwwroot/synaplan/plugins/marketeer
```
