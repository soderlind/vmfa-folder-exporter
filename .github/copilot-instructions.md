# vmfa-folder-exporter Development Guidelines

Auto-generated from all feature plans. Last updated: 2025-07-12

## Active Technologies

- PHP 8.3+ (plugin), JavaScript/JSX via @wordpress/scripts for admin UI build. (001-folder-exporter)

## Project Structure

```text
src/
  php/
    Plugin.php
    Admin/SettingsTab.php
    Services/ExportService.php
    Services/ManifestService.php
    Services/CleanupService.php
    REST/ExportController.php
    CLI/ExportCommand.php
  js/
    index.js
    components/
  styles/
    admin.scss
tests/
  unit/
  js/
```

## Commands

npm test && npm run lint && composer test

## Code Style

PHP 8.3+ (plugin), JavaScript/JSX via @wordpress/scripts for admin UI build.: Follow standard conventions

## Recent Changes

- 001-folder-exporter: ZIP export with CSV manifest, background processing, WP-CLI, React admin UI.

<!-- MANUAL ADDITIONS START -->
<!-- MANUAL ADDITIONS END -->
