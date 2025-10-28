# Contributing to Gäld

Thanks for your interest in contributing to **Gäld** — welcome! This document explains how to propose changes, run the project locally, follow our code style, and prepare a high-quality pull request.

## Table of Contents

-   [Code of Conduct](#code-of-conduct)
-   [How to contribute](#how-to-contribute)
-   [Getting the project and running locally](#getting-the-project-and-running-locally)
-   [Branching and commit conventions](#branching-and-commit-conventions)
-   [Testing and CI](#testing-and-ci)
-   [Code style & static analysis](#code-style--static-analysis)
-   [Pull Request checklist](#pull-request-checklist)
-   [Legal / Licensing / Developer Certificate of Origin](#legal--licensing--developer-certificate-of-origin)
-   [Roadmap & issues](#roadmap--issues)
-   [Points of contact](#points-of-contact)

## Code of Conduct

Please read and follow our [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md). Everyone contributing is expected to respect the project's values.

## How to contribute

Contributions can be:

-   Bug reports or feature requests (open an issue).
-   Documentation improvements.
-   Code fixes, features, or modules.
-   Tests and examples.
-   Translations.

Steps for code contributions:

1. Fork the repository to your account.
2. Create a branch `feature/<short-description>` or `fix/<short-description>`.
3. Implement changes, add tests, and run the test suite.
4. Ensure coding standards and static analysis pass.
5. Push to your fork and open a Pull Request (PR) against `main` (or the current development branch).
6. Add a clear PR description, link related issues, and list testing steps.

## Getting the project and running locally

### Requirements

-   PHP 8.4+ (match repo's `.php-version` or `composer.json`)
-   Composer
-   Node.js (LTS) + npm/yarn
-   Docker (optional, recommended for local parity)
-   PostgreSQL or MySQL (MySQL recommended)
-   Redis (optional — for queues)
-   Git

### Local setup (example)

```bash
# clone
git clone https://github.com/<your-org>/gaeld.git
cd gaeld

# copy env
cp .env.example .env
# edit .env: DB_* values, APP_KEY, MAIL_*, etc.

composer install
npm install
php artisan key:generate
php artisan migrate
php artisan db:seed   # optional
npm run dev          # or npm run build in CI
php artisan serve     # or use Valet / Docker
```

If using Docker, see docker/ (recommended to provide a docker-compose.yml).

## Branching and commit conventions

### Branching

-   main — stable release-ready branch.
-   develop — integration branch for work-in-progress (optional).
-   feature/\* — new features.
-   fix/\* — bug fixes.
-   hotfix/\* — immediate production fixes.

### Commits

We use Conventional Commits. Examples:

-   feat(invoicing): add credit note support
-   fix(vat): correct rate calculation for canton X
-   chore(deps): bump laravel/framework to 11.x
-   docs: update setup instructions

Include a short description and, when relevant, reference the issue: Fixes #123.

## Testing and CI

-   Unit and feature tests must be included for new functionality.
-   Use PHPUnit (or Pest) for backend tests.
-   Frontend tests (if present) with Playwright or PHPUnit for components.
-   CI must run: composer install, php artisan migrate --env=testing, phpunit, npm ci && npm run test if applicable.
-   Provide reproducible test data using factories and seeders.

## Code style & static analysis

-   PHP: PSR-12.
-   Use PHPStan (level to be defined) and Psalm optionally.
-   Use rector for automated refactors when needed.
-   Laravel best practices: use Eloquent resource classes, policies, form requests, and service classes.
-   Run:

```bash
composer run lint
composer run cs-check
```

(Define these scripts in composer.json.)

## Pull Request checklist

-   Branch name follows convention.
-   PR title and description are clear.
-   Linked issue (if applicable).
-   Tests included and passing.
-   Linting and static analysis pass.
-   No sensitive data or secrets in commits.
-   Documentation updated if behavior changes.
-   All changes are atomic and well-scoped.

## Legal / Licensing / Developer Certificate of Origin

All contributions will be accepted under the project license (AGPL v3). By submitting a PR, you agree to license your contribution under AGPL v3. We recommend adding a simple sign-off line to commits:

```
Signed-off-by: Your Name <email@example.com>
```

(If you prefer a CLA or other contributor agreement, the project maintainers will provide details.)

## Roadmap & issues

Please check the repository’s Issues and Projects boards for the roadmap. For major features, open a proposal issue and discuss before starting the implementation.

## Points of contact

-   Maintainers: @scanix
-   Discord: TBD
