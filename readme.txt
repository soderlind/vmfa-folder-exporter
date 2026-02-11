=== Virtual Media Folders — Folder Exporter ===
Contributors: PerS
Tags: media, export, zip, folders, virtual-media-folders
Requires at least: 6.8
Tested up to: 6.9
Requires PHP: 8.3
Stable tag: 1.1.1
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
