# Plugin Architecture: Auto-Discovery & 3-Market Model

This document captures the architectural decisions for the Synaplan plugin system overhaul. These changes must be implemented in `synaplan/` before the Marketeer plugin (or any new plugin) can be deployed without manual config edits.

## Problem Statement

Adding a new plugin to Synaplan currently requires editing **3 core files**:

1. `backend/composer.json` -- PSR-4 autoload entry
2. `backend/config/routes.yaml` -- route block
3. `backend/config/services.yaml` -- service registration + `$uploadDir` wiring

This does not scale and defeats the purpose of a plugin architecture. The goal: **drop a plugin into `/plugins/`, run the install command, done.**

Additionally, plugin controllers currently do NOT verify whether a plugin is installed for the requesting user. Any authenticated user can call any plugin endpoint.

## Two-Layer Architecture

The plugin system has two separate concerns that must not be conflated:

### Layer 1: Code Loading (System-Wide)

All plugins in `/plugins/` must have their PHP classes loadable, routes registered, and services available in the Symfony container. This is system-wide because Symfony serves all users from one PHP process. You cannot conditionally load routes per user.

### Layer 2: Access Control (Per-User, Per-Request)

When a user calls a plugin endpoint, the controller must check:
1. Is the user authenticated? (Symfony security firewall)
2. Does the user own this userId? (`canAccessPlugin()` -- already exists)
3. Is this plugin installed for this user? (**missing -- must be added**)
4. Does the user's subscription plan allow this plugin? (future, platform only)

## The 3-Market Model

Synaplan serves 3 markets from one codebase:

| Market | Who | Plugins in `/plugins/` | Who installs per user | Access gating |
|--------|-----|----------------------|----------------------|---------------|
| **Platform** | web.synaplan.com SaaS customers | All premium plugins, admin-managed | Automation or admin CLI, triggered by subscription | Subscription level + BCONFIG `P_{name}.enabled` |
| **Enterprise** | Self-hosted companies | Admin picks which to deploy | Admin CLI per user | BCONFIG `P_{name}.enabled` |
| **Open Source** | GitHub community | User downloads/adds plugins | User installs for themselves | BCONFIG `P_{name}.enabled` |

In all 3 cases, the same mechanism works:
1. Plugin code exists in `/plugins/{name}/` (central repo, read-only Docker mount)
2. `app:plugin:install {userId} {name}` creates symlinks + BCONFIG entries
3. API calls check BCONFIG `P_{name}.enabled = 1` before allowing access

## Per-User Installation Flow (Already Exists)

The `PluginManager` class in `synaplan/backend/src/Service/Plugin/PluginManager.php` handles this:

```
Central Repository                 User Space (per user)
/plugins/{name}/                   {uploadDir}/{userPath}/plugins/{name}/
  ├── manifest.json                  ├── backend -> /plugins/{name}/backend/  (symlink)
  ├── backend/                       ├── frontend -> /plugins/{name}/frontend/ (symlink)
  ├── frontend/                      └── up -> ../../../                       (symlink)
  └── migrations/
```

The `up` symlink points back to the user's upload root, giving plugins access to the user's file space.

BCONFIG entries created by migration:
- `(userId, P_{name}, enabled, 1)` -- activation flag
- `(userId, P_{name}, ...)` -- plugin-specific settings

## Auto-Discovery Implementation Plan

### A1. Custom Autoloader

**New file**: `backend/config/plugin-autoloader.php`

Resolves the namespace-to-directory mismatch. Plugin directories use lowercase (`marketeer`) with a `backend/` subdirectory, but PHP namespaces use PascalCase (`Plugin\Marketeer\Controller\...`).

```php
spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'Plugin\\')) {
        return;
    }
    // Plugin\Marketeer\Controller\X -> /plugins/marketeer/backend/Controller/X.php
    $parts = explode('\\', $class);
    array_shift($parts); // remove "Plugin"
    $pluginName = strtolower(array_shift($parts));
    $pluginsDir = '/plugins';
    if (!is_dir($pluginsDir)) {
        $pluginsDir = dirname(__DIR__, 2) . '/plugins';
    }
    $file = $pluginsDir . '/' . $pluginName . '/backend/' . implode('/', $parts) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
```

This also handles the **platform scenario** where plugins are mounted at runtime (after the Docker image is built), which compiled PSR-4 maps cannot do.

Register in `composer.json`:
```json
"autoload": {
    "psr-4": {
        "App\\": "src/",
        "Plugin\\": "/plugins/",
        "Inference\\": "lib/grpc/Inference/",
        "GPBMetadata\\": "lib/grpc/GPBMetadata/"
    },
    "files": ["config/plugin-autoloader.php"]
}
```

Remove all per-plugin entries (`Plugin\SortX\`, `Plugin\CastingData\`, `Plugin\Marketeer\`).

### A2. Dynamic Route Loading

**Modify**: `backend/src/Kernel.php` -- `configureRoutes()` method.

The Kernel already had this code **commented out** with the note "plugins need proper autoload setup." Step A1 fixes that.

```php
protected function configureRoutes(RoutingConfigurator $routes): void
{
    // ... existing route imports ...

    // Dynamic Plugin Route Loading
    $pluginsDir = '/plugins';
    if (is_dir($pluginsDir)) {
        foreach (glob($pluginsDir . '/*/backend/Controller', GLOB_ONLYDIR) as $dir) {
            $routes->import($dir, 'attribute');
        }
    }
}
```

Remove all `plugin_*` blocks from `routes.yaml`.

### A3. Dynamic Service Registration

**Modify**: `backend/src/Kernel.php` -- `configureContainer()` method.

After loading `services.yaml`, scan `/plugins/*/manifest.json` and register service definitions:

```php
protected function configureContainer(ContainerConfigurator $container): void
{
    // ... existing config imports ...

    // Dynamic Plugin Service Registration
    $pluginsDir = '/plugins';
    if (is_dir($pluginsDir)) {
        foreach (glob($pluginsDir . '/*/manifest.json') as $manifestPath) {
            $pluginDir = dirname($manifestPath);
            $backendDir = $pluginDir . '/backend';
            if (!is_dir($backendDir)) {
                continue;
            }
            $data = json_decode(file_get_contents($manifestPath), true);
            $name = ucfirst($data['id'] ?? basename($pluginDir));
            $container->services()
                ->load("Plugin\\{$name}\\", $backendDir . '/')
                ->exclude($backendDir . '/{Entity,migrations,tests}')
                ->bind('$uploadDir', '%kernel.project_dir%/var/uploads');
        }
    }
}
```

Remove all `Plugin\*` blocks from `services.yaml`.

### A4. Per-User Access Gate

**Modify**: `canAccessPlugin()` in every plugin controller to check BCONFIG:

```php
private function canAccessPlugin(?User $user, int $userId): bool
{
    if ($user === null || $user->getId() !== $userId) {
        return false;
    }
    return $this->configRepository->getValue($userId, self::CONFIG_GROUP, 'enabled') === '1';
}
```

Affected controllers: MarketeerController, SortXController, BrogentController, CastingDataController.

Error response for uninstalled plugins:
```json
{"success": false, "error": "Plugin not installed", "code": "PLUGIN_NOT_INSTALLED"}
```

### A5. PluginManifest Enhancement (Optional)

Enhance `backend/src/Service/Plugin/PluginManifest.php` to include a `requiredPlan` field:

```json
{
  "id": "marketeer",
  "requiredPlan": "PRO"
}
```

`PluginManager::installPlugin()` can then check the user's subscription level on the platform before allowing installation. On open source / enterprise (where BillingService is disabled), this check is skipped.

## File Inventory (Changes in synaplan/)

| File | Action |
|------|--------|
| `backend/config/plugin-autoloader.php` | **Create** |
| `backend/src/Kernel.php` | **Modify** -- add dynamic route + service loading |
| `backend/composer.json` | **Modify** -- remove per-plugin PSR-4, add files autoloader |
| `backend/config/routes.yaml` | **Modify** -- remove `plugin_sortx`, `plugin_castingdata`, `plugin_marketeer` blocks |
| `backend/config/services.yaml` | **Modify** -- remove `Plugin\SortX\`, `Plugin\CastingData\`, `Plugin\Marketeer\` blocks |
| `backend/src/Service/Plugin/PluginManifest.php` | **Modify** -- add `id`, `displayName`, `requiredPlan` |
| All plugin controllers | **Modify** -- add `isPluginInstalled()` check |

## What Does NOT Change

- **Symlink model** -- `PluginManager::installPlugin()` continues to create per-user symlinks.
- **Migration system** -- SQL migrations still run per-user on install.
- **security.yaml** -- Generic plugin assets rule stays. Plugins needing public endpoints (like BroGent pairing) still add rules case-by-case.
- **Frontend plugin loading** -- `GET /api/v1/config/runtime` already only returns plugins installed for the current user.

## After This: Adding a New Plugin

1. Put plugin directory in `/plugins/{name}/` with `manifest.json` and `backend/` dir.
2. Restart container (or `cache:clear` on platform).
3. Run `php bin/console app:plugin:install {userId} {name}`.

Zero core config edits. Works identically on platform, enterprise, and open source.
