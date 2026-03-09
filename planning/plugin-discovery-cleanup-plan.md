# Plugin Discovery Cleanup Plan

## Current Verdict

The new plugin discovery architecture in `synaplan` is a good direction, but it is not yet stable enough to call complete.

What already works:
- core route and service discovery is now manifest-driven
- hardcoded plugin entries in `routes.yaml`, `services.yaml`, and `composer.json` were removed
- per-user plugin installation still creates symlinks into the user data directory

What is still risky:
- backend discovery is global, but plugin access is not consistently enforced per installed plugin
- the local fallback path for plugin discovery/autoloading appears wrong outside a `/plugins` mount
- the refactor has almost no direct automated test coverage

## Short Instruction: How To Separate Plugins From Core

1. Keep each real plugin in its own repo, with the plugin package itself as the deployable root.
2. Use the same package structure everywhere:
   - `manifest.json`
   - `backend/`
   - `frontend/`
   - `migrations/`
3. Deploy plugins into the central plugin repository mounted as `/plugins`, not into core source control.
4. Activate plugins per user only through `app:plugin:install {userId} {pluginName}` so the user data symlinks are created.
5. Never add plugin-specific entries to core `composer.json`, `routes.yaml`, `services.yaml`, sidebar config, or router config.
6. Treat `synaplan/plugins/*` as temporary incubation only. Mature plugins should move to dedicated repos.

## Required Core Follow-Ups

These should be fixed in `synaplan` before calling discovery mode complete:

1. Enforce installed-and-enabled access on all plugin routes, not only `userId === currentUser`.
2. Fix the local plugin fallback path so non-container local development resolves the real repo plugin directory.
3. Fix snake_case and mixed-case namespace/directory mapping in the custom plugin autoloader fallback path.
4. Add automated tests for:
   - plugin manifest discovery
   - PSR-4 registration from manifest namespace
   - per-user install symlink creation
   - plugin asset serving from user plugin directory
   - uninstalled plugin route access rejection
   - installed but disabled plugin route access rejection

## Plugin-by-Plugin Assessment

### `sortx`

Status: external repo already exists, but package root and access control still need cleanup.

Required changes:
- keep `sortx-plugin/` as the only deployable package root
- add explicit `"namespace": "Plugin\\SortX"` in the manifest and keep it mandatory
- keep `setup` and `setup-check`; do not rely on manifest `hooks.onInstall`
- update controller access checks to require both user ownership and `P_sortx.enabled = 1`
- add plugin smoke tests for install, setup, schema, classify, and disabled access rejection

### `castingdata`

Status: structurally discovery-compatible, but still bundled with core.

Required changes:
- move to its own repo if it is expected to evolve independently or customer-by-customer
- keep the current manifest/package structure
- add installed/enabled access checks in the controller using `P_castingdata.enabled`
- add a lightweight plugin setup/install smoke test separate from the CastApp integration suite

Recommendation:
- if this remains a productized connector, it should become a separate repo
- if it remains a tightly coupled demo/reference integration, it can stay in core temporarily

### `brogent`

Status: not ready for current discovery mode.

Required changes:
- move to its own repo
- add explicit namespace in `manifest.json`
- replace the current frontend contract with a self-contained `frontend/index.js` that exports `default.mount(el, context)`
- stop relying on `menuItems`, `routes`, and raw Vue imports unless the host gets a plugin build/runtime layer
- stop relying on manifest `hooks.onInstall` for critical bootstrap
- add installed/enabled access checks in the controller using `P_brogent.enabled`
- add tests for pairing, device auth, run claim, and disabled access rejection

### `marketeer`

Status: closest to the desired target state.

What is already good:
- separate repo already exists
- `marketeer-plugin/` is a clean plugin package
- frontend already exports `mount()`
- controller already checks `P_marketeer.enabled`
- there is at least one integration smoke script

Required changes:
- make `marketeer-plugin/` the only deployable artifact
- do not rely on `hooks.onInstall` for required setup; keep `/setup` as the explicit bootstrap path
- add automated tests into CI, not only the local shell script
- add explicit tests for discovery/install/disable behavior

### `hello_world`

Status: keep as a sample plugin, not as a real bundled product plugin.

Required changes:
- keep it in core as a fixture/example only
- document clearly that it is a sample plugin
- if used as a test fixture, add proper auth/install/enable checks or keep it out of production deployments
- align manifest naming and capabilities with the real plugin contract if it is used for automated tests

## Test Assessment

Current test situation is not strong enough to confirm stability of the architecture refactor.

What exists:
- one `castingdata` Playwright suite in `synaplan/frontend/tests/e2e/tests/castingdata-plugin.spec.ts`
- one `marketeer` shell smoke script in `synaplan-marketing/test-marketeer.sh`

Important gaps:
- no backend tests for `Kernel` plugin discovery
- no backend tests for `PluginManager`
- no backend tests for `PluginAssetController`
- no tests proving install symlinks are created correctly
- no tests proving uninstalled plugins are blocked
- no tests proving disabled plugins are blocked
- no tests for `sortx`, `brogent`, or `hello_world` in discovery mode
- the existing plugin Playwright suite is excluded from the default E2E run

Conclusion:
- there are not yet solid tests in place for the discovery-mode refactor itself

## Cleanup Work Plan

### Phase 1: Secure The Core Contract

1. Fix core discovery/autoloader fallback paths.
2. Add a shared plugin access helper pattern for installed-and-enabled enforcement.
3. Add backend tests for discovery, install, symlink, and asset serving.
4. Add a regression test proving uninstalled plugins cannot be called.

### Phase 2: Normalize Plugin Packaging

1. `sortx`: ship only `sortx-plugin/`.
2. `marketeer`: ship only `marketeer-plugin/`.
3. `castingdata`: decide repo split and package as standalone plugin root.
4. `brogent`: move out of core and rebuild the frontend entrypoint to match host expectations.
5. `hello_world`: reduce to sample/test-fixture role.

### Phase 3: Standardize Plugin Runtime Rules

Every plugin should follow these rules:
- manifest namespace must be explicit when default namespace inference is not exact
- frontend entrypoint must be `frontend/index.js`
- frontend module must export `default.mount(el, context)`
- required bootstrap must be available through migrations and/or explicit setup endpoints
- controller access must require:
  - authenticated user
  - matching `userId`
  - plugin enabled flag in `BCONFIG`

### Phase 4: Build A Real Test Matrix

For each plugin, add:
- discovery smoke test
- install smoke test
- setup smoke test
- disabled access rejection test
- frontend asset load smoke test

For plugin-specific behavior:
- `sortx`: schema/classify/analyze setup path
- `castingdata`: config/test-connection path
- `brogent`: pairing/run flow
- `marketeer`: campaign CRUD/dashboard/download path
- `hello_world`: discovery fixture only

## Recommended Repo Split Decision

- `sortx`: yes, keep separate
- `castingdata`: yes, likely should become separate
- `brogent`: yes, should become separate
- `marketeer`: yes, already separate
- `hello_world`: no, keep inside core as example/test fixture

## Suggested Execution Order

1. harden core access control and add core discovery tests
2. normalize `sortx` and `marketeer` packaging
3. extract `castingdata`
4. extract and rebuild `brogent`
5. reduce `hello_world` to sample/test role

## Go/No-Go Summary

- architecture direction: go
- current implementation stability: no-go until core tests and access enforcement are added
- plugin separation strategy: go
- production readiness for discovery mode across all plugins: not yet
