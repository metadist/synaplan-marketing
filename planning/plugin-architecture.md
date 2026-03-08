# Plugin Auto-Discovery Architecture

**One-time core change. After this, zero core edits for new plugins.**

## How It Works

```
/plugins/{name}/
  ├── manifest.json        ← declares id, namespace, version
  ├── backend/
  │   ├── Controller/      ← auto-discovered routes (Symfony attributes)
  │   └── Service/         ← auto-registered services (autowired)
  ├── frontend/            ← optional UI
  └── migrations/          ← per-user SQL (run by app:plugin:install)
```

**Boot sequence:**
1. `plugin-autoloader.php` resolves `Plugin\Xyz\*` → `/plugins/xyz/backend/*`
2. `Kernel::configureRoutes()` scans `/plugins/*/backend/Controller/` for route attributes
3. `Kernel::configureContainer()` reads each `manifest.json`, registers services with correct namespace

**Adding a new plugin:**
1. Drop directory into `/plugins/`
2. `php bin/console cache:clear`
3. `php bin/console app:plugin:install {userId} {name}`

No composer.json, no routes.yaml, no services.yaml edits.

## Core Changes (one-time)

| File | Change |
|------|--------|
| `config/plugin-autoloader.php` | **New** — SPL autoloader for `Plugin\*` namespaces |
| `src/Kernel.php` | **Modify** — dynamic route + service loading |
| `composer.json` | **Modify** — remove per-plugin PSR-4, add autoloader file |
| `config/routes.yaml` | **Modify** — remove per-plugin blocks |
| `config/services.yaml` | **Modify** — remove per-plugin blocks |

## manifest.json Contract

```json
{
  "id": "marketeer",
  "namespace": "Plugin\\Marketeer",
  "version": "1.0.0",
  "capabilities": ["api", "frontend", "migrations"],
  "config": { "group": "P_marketeer" }
}
```

The `namespace` field is required when it differs from `Plugin\` + ucfirst(id). Examples:
- `sortx` → `"namespace": "Plugin\\SortX"` (mixed case)
- `castingdata` → `"namespace": "Plugin\\CastingData"` (mixed case)
- `marketeer` → omit (default `Plugin\\Marketeer` works)

## Per-User Access Gate

Every plugin controller must check `BCONFIG P_{name}.enabled = 1`:

```php
private function canAccessPlugin(?User $user, int $userId): bool
{
    return $user !== null
        && $user->getId() === $userId
        && $this->configRepository->getValue($userId, self::CONFIG_GROUP, 'enabled') === '1';
}
```

---

# Marketeer MVP Plan

## Scope (5 features, nothing more)

1. **Campaign CRUD** — create, edit, delete campaigns with CTAs, audience, USPs
2. **Landing pages** — AI-generate, refine, per-language, GDPR snippets auto-injected
3. **Collaterals** — AI images (hero, social, banners, icons) + Google Ads RSA + social posts (LinkedIn, Instagram, Discord)
4. **Google Ads planning** — AI-generate campaign structures with ad groups, keywords, match types; export for import
5. **Compliance & launch** — GDPR checker, cookie consent snippets, pre-launch checklist

## Data Model (all in generic plugin_data table)

| Type | Key pattern | Content |
|------|-------------|---------|
| `campaign` | `{slug}` | Title, topic, audience, USPs, CTAs, platforms, tracking, status |
| `page` | `{slug}_{lang}` | HTML, generation metadata |
| `ad_copy` | `{slug}_google_{lang}` | Headlines, descriptions, sitelinks |
| `social_post` | `{slug}_{platform}_{lang}` | Post text, hashtags, image concept |
| `collateral` | `{slug}_{type}_{lang}` | File path, prompt, provider |
| `ads_campaign` | `{slug}_{id}` | Campaign structure, ad groups, keywords, budget |

Config in BCONFIG `P_marketeer`: enabled, default_language, cta_url, brand_name, privacy_policy_url, imprint_url, gtm_id, gads_conversion_id.

## Testing

**Automated test script** (`test-marketeer.sh`) runs after deployment:

```
1. Setup check      → plugin responds, config correct
2. Seed defaults    → example campaign created
3. Campaign CRUD    → create, get, update, delete
4. Ads campaign     → manual create, list, delete
5. Ad copy list     → empty list returns correctly
6. Compliance       → quick check returns checklist
7. Cookie snippet   → HTML snippet returned
8. Dashboard        → overview with correct counts
9. Cleanup          → delete test data
```

Each step asserts `"success": true` and expected fields. Non-zero exit on failure.

**AI-dependent endpoints** (generate page, generate ad copy, generate image) are tested manually since they require a configured AI provider.
