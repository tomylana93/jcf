---
paths:
  - 'app/Providers/**'
  - 'app/Enums/Module.php'
  - 'app/Support/**'
  - 'routes/**'
  - 'bootstrap/app.php'
  - 'tests/Feature/ModuleBoundariesTest.php'
  - 'resources/js/types/features.ts'
  - 'resources/js/types/permission.ts'
---

# Providers

## Module registry is the single module definition
Optional modules are cases of App\Enums\Module, which owns each module's settings class, permissions, provider and migration paths. Adding a module means: a Module case plus every match arm, a Settings class with database/settings/<module> migrations, optional database/migrations/<module>, a module provider returned by Module::provider() (never AppServiceProvider or bootstrap/providers.php), the ModuleName and PermissionName unions in resources/js/types, and rules in tests/Feature/ModuleBoundariesTest.php. Module-owned schedules, rate limiters and contract bindings live in that module provider.

## Check module state through the Module enum
Use Module::X->flagged() for hard-flag registration (settings, migrations, package providers), Module::X->available() for route registration and runtime requirements such as the API domain, FeatureManager::enabled(Module::X) for the stored toggle, and FeatureManager::ensureEnabled(Module::X) inside Actions. Do not read config('features.*') directly or pass module names as string literals; strings are only allowed as feature:<module> middleware parameters.

## Core consumes optional modules through contracts
Cross-module contracts live in App\Support\Contracts (SearchProvider, ChatSummary, HealthCheck); a module tags or binds its implementation in its own provider, and the core coordinator only iterates the contract. Core areas (Search, System, Middleware, Account, Identity) never import another module's Actions or models. Extend tests/Feature/ModuleBoundariesTest.php whenever a module, model or Action namespace is added, and restrict new module frontend internals with a no-restricted-imports pattern in vite.config.ts like the chat one.
