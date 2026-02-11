# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
