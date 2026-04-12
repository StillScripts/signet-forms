---
name: lint
description: Run linters and formatters on changed files. Use before committing, before creating a PR, or whenever the user wants to clean up code style. Runs Laravel Pint for PHP files and verifies the frontend build compiles cleanly.
---

# Lint

Run code quality tools on changed files to match project standards.

## PHP Files — Laravel Pint

Run Pint on dirty (uncommitted) PHP files:

```bash
vendor/bin/pint --dirty --format agent
```

This auto-fixes formatting issues following the `laravel` preset defined in `pint.json`.

If you need to lint specific files instead of all dirty files:

```bash
vendor/bin/pint app/Models/Team.php app/Filament/Pages/TeamSettings.php --format agent
```

## Frontend Assets — Build Check

If any CSS or JS files were modified, verify the frontend compiles:

```bash
npm run build
```

This runs Vite with the Tailwind CSS v4 plugin and Laravel Vite plugin. A successful build confirms there are no syntax errors in CSS/JS assets.

## When to Skip

- **No PHP files changed** → skip Pint
- **No CSS/JS files changed** → skip the build check
- **Only markdown, config, or skill files changed** → skip everything

## CI Alignment

GitHub Actions runs the same checks:
- `lint.yml` runs `composer lint` (which is `pint --parallel`)
- `tests.yml` runs `npm run build` as part of the test setup

Running lint locally before pushing prevents CI failures on style issues.
