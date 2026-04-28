# GitHub Copilot Instructions

This repository is a Laravel 13 + Inertia v3 + Vue 3 inventory management application. Follow these instructions when suggesting or generating code.

## Tech Stack

- **Backend:** PHP 8.3, Laravel 13, Inertia Laravel v3, Laravel Wayfinder v0
- **Frontend:** Vue 3, Inertia Vue v3, TypeScript, Tailwind CSS v4, daisyui v5, Vite 8
- **Tooling:** Laravel Pint (PHP formatter), ESLint 9, Prettier 3, vue-tsc, PHPUnit 12
- **Dev Utilities:** Laravel Boost (MCP), Pail (logs), Sail, Tinker
- **Package manager:** `pnpm` (see `pnpm-lock.yaml`, `pnpm-workspace.yaml`)

## Project Layout

```
app/
  Http/Controllers/       # CategoryController, ProductController, SupplierController, Login, Logout, Register
  Models/                 # Category, Product, Supplier, User (Eloquent, uses #[Fillable] attribute)
  Providers/
database/
  factories/              # ProductFactory, UserFactory
  migrations/             # users, cache, jobs, product tables, category/supplier
  seeders/DatabaseSeeder.php
resources/
  css/app.css             # Tailwind v4 entrypoint
  js/
    app.ts                # Inertia Vue client bootstrap
    pages/                # Inertia pages: Welcome, Login, Register, Dashboard, Product/, Category/, Supplier/
    layouts/
    components/           # Shared Vue components (e.g. DataTable.vue)
    actions/              # Wayfinder-generated controller actions
    routes/               # Wayfinder-generated named routes
    wayfinder/            # Wayfinder runtime helpers
    lib/                  # Utility modules (clsx, tailwind-merge, etc.)
    types/                # Shared TS types
routes/
  web.php                 # All HTTP routes (guest + auth groups)
  console.php
tests/
  Feature/                # ProductControllerTest, ExampleTest
  Unit/
```

## Routing & Controllers

- Routes live in `routes/web.php` and are split into `guest` and `auth` middleware groups.
- CRUD is exposed via `Route::resource(...)` for `product`, `category`, and `supplier`, each with an extra `bulk-destroy` DELETE route.
- Auth flow uses single-action invokable controllers: `Login`, `Logout`, `Register`.
- Simple Inertia-only pages use `Route::inertia('/path', 'PageName')`.
- When generating frontend calls to backend endpoints, **use Wayfinder** imports from `@/actions/...` and `@/routes/...` rather than hardcoded URLs. Regenerate with `php artisan wayfinder:generate` (or rely on the Vite `wayfinder()` plugin which has `formVariants: true`).

## Models

- Eloquent models use the PHP 8 `#[Fillable([...])]` attribute instead of the `$fillable` property (see `app/Models/Product.php`). Follow this pattern for new models.
- Use `HasFactory` and create a factory under `database/factories/` for every new model.

## Frontend Conventions

- Inertia pages are mounted from `resources/js/pages/` (rendered by `Inertia::render()` or `Route::inertia()`).
- Use Inertia v3 features: `<Link>`, `<Form>`, `useForm`, `useHttp`, `router`, deferred props (`Inertia::defer()`), optional props (`Inertia::optional()` — **not** `Inertia::lazy()`), `Inertia::merge()`.
- Vue components must have a single root element.
- Styling uses Tailwind CSS v4 utility classes plus **daisyui v5** components (registered via `@plugin "daisyui";` in `resources/css/app.css`). Shared merging helpers live in `resources/js/lib/`.
- Prefer daisyui components (`btn`, `card`, `table`, `menu`, `navbar`, `input`, `select`, `checkbox`, `join`, `badge`, etc.) before raw Tailwind utilities.
- Use daisyui semantic color tokens (`bg-base-100`, `bg-base-200`, `border-base-300`, `text-primary`, `bg-primary/10`) instead of hardcoded `gray-*` / `indigo-*` so themes work.
- For Laravel paginator labels: don't `v-html` raw labels — strip `&laquo;` / `&raquo;` and render `mdi:chevron-left` / `mdi:chevron-right` icons (those glyphs don't render reliably in Instrument Sans).
- When the user asks for a styling fix, only adjust classes — don't restructure existing markup.
- Icons via `@iconify/vue` (`<Icon icon="mdi:..." />`).

## Testing

- PHPUnit only (no Pest). Create tests with `php artisan make:test --phpunit {Name}` (add `--unit` for unit tests; default to feature tests).
- Run focused tests with `php artisan test --compact --filter=testName`.
- Use model factories and factory states instead of manual model construction.
- Do not delete existing tests without approval.

## Formatting, Linting & Type Checks

Always run the relevant checker after changes:

- PHP: `vendor/bin/pint --dirty --format agent` (do **not** use `--test`).
- JS/TS lint: `pnpm lint` / `pnpm lint:check`.
- Prettier: `pnpm format` / `pnpm format:check`.
- Vue/TS types: `pnpm types:check` (runs `vue-tsc --noEmit`).
- Full CI gate: `composer ci:check`.

## Common Commands

- Dev stack (server + queue + vite): `composer dev`
- Build frontend: `pnpm build`
- SSR build: `pnpm build:ssr`
- Migrate DB: `php artisan migrate`
- Inspect routes: `php artisan route:list --except-vendor`
- Tail logs: `php artisan pail`

If the UI doesn't reflect a frontend change, the dev server / build likely needs to run (`pnpm dev`, `pnpm build`, or `composer dev`).

## Coding Rules (Repo-wide)

- PHP: always use curly braces, explicit return types, typed parameters, constructor property promotion, PHPDoc blocks over inline comments, array-shape PHPDoc for complex arrays.
- Enums: TitleCase keys.
- Naming: descriptive variable/method names (`isRegisteredForDiscounts`, not `discount()`).
- Reuse existing components/helpers before creating new ones.
- Do not add new top-level directories or new composer/npm dependencies without approval.
- Do not create documentation files unless explicitly requested.
- Prefer named routes (`route('product.index')`) and Wayfinder on the frontend over hardcoded URLs.
- Use `php artisan make:*` generators (with `--no-interaction`) rather than hand-creating Laravel files.

## Skills (see `AGENTS.md`)

Relevant Laravel Boost skills are declared for this project:

- `laravel-best-practices` — backend PHP work, controllers, queries, validation, auth, jobs.
- `inertia-vue-development` — Inertia v3 + Vue pages, forms, navigation.
- `tailwindcss-development` — Tailwind v4 utility styling.
- `wayfinder-development` — whenever frontend calls backend routes/controllers.

Consult `AGENTS.md` for the full Laravel Boost guideline set; those rules take precedence over generic Laravel advice.
