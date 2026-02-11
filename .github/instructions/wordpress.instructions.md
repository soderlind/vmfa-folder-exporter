---
applyTo: 'wp-content/plugins/**,wp-content/themes/**,**/*.php,**/*.inc,**/*.js,**/*.jsx,**/*.ts,**/*.tsx,**/*.css,**/*.scss,**/*.json'
description: 'Coding, security, and testing rules for WordPress plugins and themes'
---

# WordPress Development — Copilot Instructions

**Goal:** Generate WordPress code that is secure, performant, testable, and compliant with official WordPress practices. Prefer hooks, small functions, dependency injection (where sensible), and clear separation of concerns.

## 1) Core Principles
- Never modify WordPress core. Extend via **actions** and **filters**.
- For plugins, always include a header and guard direct execution in entry PHP files.
- Use unique prefixes or PHP namespaces to avoid global collisions.
- Enqueue assets; never inline raw `<script>`/`<style>` in PHP templates.
- Make user‑facing strings translatable and load the correct text domain.

### Minimal plugin header & guard
```php
<?php
defined('ABSPATH') || exit;
/**
 * Plugin Name: Awesome Feature
 * Description: Example plugin scaffold.
 * Version: 0.1.0
 * Author: Example
 * License: GPL-2.0-or-later
 * Text Domain: awesome-feature
 * Domain Path: /languages
 */
```

## 2) Coding Standards (PHP, JS, CSS, HTML)
- Follow **WordPress Coding Standards (WPCS)** and write DocBlocks for public APIs.
- PHP: Prefer strict comparisons (`===`, `!==`) where appropriate. Be consistent with array syntax and spacing as per WPCS.
- JS: Match WordPress JS style; prefer `@wordpress/*` packages for block/editor code.
- CSS: Use BEM‑like class naming when helpful; avoid over‑specific selectors.
- PHP 8.3+ compatible patterns for this project.

## 3) Security & Data Handling
- **Escape on output, sanitize on input.**
  - Escape: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`.
  - Sanitize: `sanitize_text_field()`, `sanitize_email()`, `sanitize_key()`, `absint()`, `intval()`.
- **Capabilities & nonces** for forms, AJAX, REST:
  - Add nonces with `wp_nonce_field()` and verify via `check_admin_referer()` / `wp_verify_nonce()`.
  - Restrict mutations with `current_user_can( 'upload_files' )`.
- **Database:** always use `$wpdb->prepare()` with placeholders; never concatenate untrusted input.

## 4) Internationalization (i18n)
- Wrap user‑visible strings with translation functions using text domain `vmfa-folder-exporter`.
- Keep a `.pot` in `/languages` and ensure consistent domain usage.

## 5) Performance
- Defer heavy logic to specific hooks; avoid expensive work on `init`/`wp_loaded` unless necessary.
- Use Action Scheduler for background processing.
- Enqueue only what you need and conditionally.

## 6) REST API
- Register with `register_rest_route()`; always set a `permission_callback`.
- Validate/sanitize request args via the `args` schema.
- Return `WP_REST_Response` or arrays/objects that map cleanly to JSON.

## 7) Asset Loading
- Use `wp_register_style/script` and `wp_enqueue_style/script`.
- For admin screens, hook into `admin_enqueue_scripts` and check screen IDs.
- Build with `@wordpress/scripts` (`wp-scripts build`).

## 8) Testing
### PHP
- Use **Pest PHP** with **Brain\Monkey** for unit tests.
- Test: sanitization, capability checks, REST permissions, service logic.

### JavaScript
- Use **Vitest** with **@testing-library/react** for component tests.
- Mock `@wordpress/*` packages via vitest aliases.

## 9) Documentation & Commits
- Keep `README.md` up to date: install, usage, capabilities, hooks/filters, and test instructions.
- Use clear, imperative commit messages; reference issues/tickets and summarize impact.

## 10) What Copilot Must Ensure (Checklist)
- ✅ Unique prefixes/namespaces; no accidental globals.
- ✅ Nonce + capability checks for any write action (AJAX/REST/forms).
- ✅ Inputs sanitized; outputs escaped.
- ✅ User‑visible strings wrapped in i18n with correct text domain.
- ✅ Assets enqueued via APIs (no inline script/style).
- ✅ Tests added/updated for new behaviors.
- ✅ Code passes PHPCS (WPCS) and ESLint where applicable.
- ✅ Avoid direct DB concatenation; always prepare queries.
