# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.2] - 2026-07-12

### Fixed

- Action Scheduler now installs into `vendor/woocommerce/action-scheduler/` (via `extra.installer-paths`) instead of being relocated to `wp-content/plugins/action-scheduler/` by `composer/installers`, restoring the release build's bundling check and the runtime `ActionSchedulerLoader` path resolution.

### Security

- Resolved the majority of Dependabot alerts by updating build/test dependencies (`npm audit fix`, `@wordpress/scripts` 31 → 32, `@wordpress/components` → 36). Remaining alerts are dev-only transitive dependencies pinned by `@wordpress/scripts`.

### Changed

- Added grouped `.github/dependabot.yml` config (npm/composer/github-actions) to consolidate future dependency update PRs.

## [1.3.1] - 2026-04-20

### Fixed

- Deferred `schedule_cleanup()` to `init` hook (priority 20) to fix "as_has_scheduled_action was called incorrectly" warning

## [1.3.0] - 2026-03-14

### Changed

- Refactored Plugin class to extend `VirtualMediaFolders\Addon\AbstractPlugin` base class
- Refactored SettingsTab to extend `AbstractSettingsTab`, removed inline enqueue and WP 7 compat logic
- Replaced inline Action Scheduler loading with `ActionSchedulerLoader::maybe_load()`
- Removed duplicated singleton boilerplate and textdomain loading

## [1.2.0] - 2026-03-10

### Added

- WP 7.0+ design-token style overrides for progress bar, status badges, and export panel

## [1.1.3] - 2026-03-08

### Security

- Updated npm dependencies.

## [1.1.2] - 2026-03-07

### Changed

- Tested up to WordPress 7.0

## [1.1.1] - 2026-02-11

### Added

- `vmfa_export_dir` filter to change the export ZIP storage directory
- Developer documentation moved to `docs/DEVELOPER.md` with filter examples, REST API reference, and WP-CLI details

## [1.1.0] - 2026-02-11

### Added

- `wp vmfa-export folders` WP-CLI command to list all folders with ID, name, path, and media count
- WPCS array bracket spacing applied across all PHP files

## [1.0.0] - 2025-07-12

### Added

- ZIP export with folder hierarchy preservation
- Optional CSV manifest with 12 metadata columns
- Background processing via Action Scheduler
- Automatic 24-hour export cleanup
- React admin dashboard with folder picker, export options, progress tracking, and export history
- WP-CLI commands: `wp vmfa-export folder`, `wp vmfa-export list`, `wp vmfa-export clean`
- REST API endpoints for export management
- `vmfa_export_manifest_columns` filter for customising manifest columns
- Full test suite (Pest PHP + Vitest)
