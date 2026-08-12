=== Virtual Media Folders — Folder Exporter ===
Contributors: PerS
Tags: media, export, zip, folders, virtual-media-folders
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.3.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Export folders as ZIP archives with optional CSV manifests. Add-on for Virtual Media Folders.

== Description ==

Folder Exporter is an add-on for [Virtual Media Folders](https://wordpress.org/plugins/virtual-media-folders/). It lets you export any virtual folder (or subtree) as a downloadable ZIP archive, optionally with a CSV manifest containing metadata for every file.

= Features =

* **ZIP export** — download a folder as a ZIP with the original folder hierarchy preserved.
* **Include subfolders** — optionally include all descendent folders.
* **CSV manifest** — 12-column manifest with ID, filename, URL, alt text, caption, description, MIME type, file size, dimensions, date, and folder path.
* **Background processing** — via Action Scheduler for large folders.
* **Automatic cleanup** — expired exports are removed after 24 hours.
* **Admin dashboard** — React-based UI with folder picker, progress tracking, and export history.
* **WP-CLI support** — `wp vmfa-export folder`, `wp vmfa-export list`, `wp vmfa-export clean`.

== Installation ==

1. Install and activate [Virtual Media Folders](https://wordpress.org/plugins/virtual-media-folders/).
2. Upload or install this plugin and activate it.
3. Go to **Media → Virtual Folders → Folder Exporter**.

== Frequently Asked Questions ==

= How large can exports be? =

Exports run in the background via Action Scheduler, so there is no PHP timeout limit. The ZIP is built server-side and made available for download when complete.

= How long are exports kept? =

Exports are automatically cleaned up after 24 hours. You can also manually delete them from the dashboard or via WP-CLI.

= Can I customise the CSV manifest? =

Yes. Use the `vmfa_export_manifest_columns` filter to add, remove, or reorder columns.

= Does it export the actual files? =

Yes. The ZIP contains the actual media files organised in the same folder hierarchy as your virtual folders.

== Screenshots ==

1. Export dashboard with folder picker and options.
2. Export progress with real-time progress bar.
3. Export history with download and delete actions.

== Changelog ==

= 1.3.3 =
* Fixed: Prevent a fatal error when the "Virtual Media Folders" parent plugin is missing or older than 2.0.0; show an admin notice instead.
* Changed: Tested up to WordPress 7.1.

= 1.3.2 =
* Fixed: Action Scheduler now bundles into `vendor/woocommerce/action-scheduler/` instead of being relocated by `composer/installers`, restoring the release build and runtime loader.
* Security: Resolved the majority of Dependabot alerts by updating build/test dependencies (`@wordpress/scripts` 31 → 32, `@wordpress/components` → 36). Remaining alerts are dev-only transitive dependencies.
* Changed: Added grouped `.github/dependabot.yml` config to consolidate future dependency update PRs.

= 1.3.1 =
* Fixed: Deferred `schedule_cleanup()` to `init` hook to fix Action Scheduler warning

= 1.3.0 =
* Changed: Refactored Plugin class to extend VMF core `AbstractPlugin` base class
* Changed: Refactored SettingsTab to extend `AbstractSettingsTab`
* Changed: Replaced inline Action Scheduler loading with `ActionSchedulerLoader`
* Changed: Removed duplicated singleton boilerplate and textdomain loading

= 1.2.0 =
* Added: WP 7.0+ design-token style overrides for progress bar, status badges, and export panel

= 1.1.3 =
* Security: Updated npm dependencies.

= 1.1.2 =
* Changed: Tested up to WordPress 7.0

= 1.1.1 =
* Added `vmfa_export_dir` filter to change the export ZIP storage directory.
* Moved developer documentation to `docs/DEVELOPER.md` with examples.

= 1.1.0 =
* Added `wp vmfa-export folders` WP-CLI command to list all folders with ID, name, path, and media count.
* Applied WPCS array bracket spacing across all PHP files.

= 1.0.0 =
* Initial release.
* ZIP export with folder hierarchy.
* Optional CSV manifest (12 columns).
* Background processing via Action Scheduler.
* Automatic 24-hour cleanup.
* React admin dashboard.
* WP-CLI commands: folder, list, clean.
