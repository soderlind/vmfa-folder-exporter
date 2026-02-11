# Virtual Media Folders — Folder Exporter

Add-on for [Virtual Media Folders](https://wordpress.org/plugins/virtual-media-folders/) that lets you export folders (or subtrees) as ZIP archives with optional CSV manifests.

## Features

- **ZIP export** — export any folder as a downloadable ZIP archive with the original folder hierarchy preserved.
- **Include subfolders** — optionally include all descendent folders in the export.
- **CSV manifest** — generate a manifest with ID, filename, URL, alt text, caption, description, MIME type, file size, dimensions, date uploaded, and folder path.
- **Background processing** — powered by Action Scheduler for large folders.
- **Automatic cleanup** — expired exports are automatically removed after 24 hours.
- **Admin dashboard** — React-based UI with folder picker, export options, progress tracking, and export history.
- **WP-CLI support** — export, list, and clean up from the command line.

## Requirements

| Requirement | Version |
|-------------|---------|
| WordPress | 6.8+ |
| PHP | 8.3+ |
| [Virtual Media Folders](https://wordpress.org/plugins/virtual-media-folders/) | active |

## Installation

1. Download [`vmfa-folder-exporter.zip`](https://github.com/soderlind/vmfa-folder-exporter/releases/latest/download/vmfa-folder-exporter.zip)
2. Upload via `Plugins → Add New → Upload Plugin`
3. Activate via `WordPress Admin → Plugins`

Plugin [updates are handled automatically](https://github.com/soderlind/wordpress-plugin-github-updater#readme) via GitHub. No need to manually download and install updates.

## Usage

### Admin Dashboard

Navigate to **Media → Virtual Folders → Folder Exporter**. The dashboard provides:

| Section | Purpose |
|---------|---------|
| **Stats** | Total available folders |
| **Export Folder** | Select a folder, choose options, start export |
| **Progress** | Real-time progress bar and download button |
| **Recent Exports** | History table with download/delete actions |

### Export Options

| Option | Default | Description |
|--------|---------|-------------|
| Include subfolders | ✅ | Include all descendent folders in the ZIP |
| Include CSV manifest | ✅ | Add a `manifest.csv` file at the ZIP root |

### CSV Manifest Columns

| Column | Description |
|--------|-------------|
| ID | Attachment post ID |
| filename | Original filename |
| url | Full attachment URL |
| alt_text | Image alt text |
| caption | Attachment caption |
| description | Attachment description |
| mime_type | MIME type (e.g., `image/jpeg`) |
| file_size_bytes | File size in bytes |
| width | Image width in pixels (if applicable) |
| height | Image height in pixels (if applicable) |
| date_uploaded | Upload date |
| folder_path | Virtual folder path (e.g., `Photos/2025/Summer`) |

### WP-CLI

```bash
wp vmfa-export folder 42              # Export folder ID 42 as ZIP
wp vmfa-export folder 42 --output=/tmp/photos.zip
wp vmfa-export folder 42 --no-children
wp vmfa-export folder 42 --no-manifest
wp vmfa-export list                   # List recent exports
wp vmfa-export list --format=json
wp vmfa-export clean                  # Remove expired exports
wp vmfa-export clean --all            # Remove all exports
```

## Developer Documentation

### Filters

| Filter | Description |
|--------|-------------|
| `vmfa_export_manifest_columns` | Customise or add CSV manifest columns |

### REST API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/vmfa-folder-exporter/v1/exports` | Start a new export |
| `GET` | `/vmfa-folder-exporter/v1/exports` | List exports |
| `GET` | `/vmfa-folder-exporter/v1/exports/{id}` | Get export status |
| `DELETE` | `/vmfa-folder-exporter/v1/exports/{id}` | Delete an export |
| `GET` | `/vmfa-folder-exporter/v1/exports/{id}/download` | Download ZIP |

### Building

```bash
composer install
npm install
npm run build
```

### Testing

```bash
composer test       # PHP tests (Pest)
npm test            # JS tests (Vitest)
npm run lint        # Lint JS/CSS
```

## License

GPL-2.0-or-later
