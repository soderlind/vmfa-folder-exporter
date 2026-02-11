# Developer Documentation

## Filters

### `vmfa_export_dir`

Change the directory where export ZIP files are stored. Defaults to `wp-content/uploads/vmfa-exports`.

**Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$export_dir` | `string` | Absolute path to the export directory |

**Example — store exports outside the web root**

```php
add_filter( 'vmfa_export_dir', function ( string $dir ): string {
    return '/var/private/media-exports';
} );
```

**Example — use a site-specific subdirectory on multisite**

```php
add_filter( 'vmfa_export_dir', function ( string $dir ): string {
    $upload = wp_upload_dir();
    return $upload['basedir'] . '/vmfa-exports/' . get_current_blog_id();
} );
```

> **Note:** The directory is created automatically if it does not exist. An `.htaccess` and `index.php` are added to prevent direct browsing.

---

### `vmfa_export_manifest_columns`

Customise the CSV manifest column headers. The row data is built in `ManifestService::build_row()` — if you add columns here you also need to filter the row data.

**Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$columns` | `array<string>` | Default column headers |

**Default columns**

```
ID, filename, url, alt_text, caption, description,
mime_type, file_size_bytes, width, height, date_uploaded, folder_path
```

**Example — remove dimensions, add a custom column**

```php
add_filter( 'vmfa_export_manifest_columns', function ( array $columns ): array {
    // Remove width and height.
    $columns = array_diff( $columns, [ 'width', 'height' ] );

    // Add a custom column.
    $columns[] = 'photographer';

    return array_values( $columns );
} );
```

---

## REST API

All endpoints require the `upload_files` capability. Authenticate with a nonce (`X-WP-Nonce` header) or an application password.

**Base URL:** `/wp-json/vmfa-folder-exporter/v1`

### Start an export

```
POST /exports
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `folder_id` | `integer` | *(required)* | Folder term ID |
| `include_children` | `boolean` | `true` | Include subfolders |
| `include_manifest` | `boolean` | `true` | Add CSV manifest to ZIP |

**Example**

```bash
curl -X POST \
  "https://example.com/wp-json/vmfa-folder-exporter/v1/exports" \
  -H "X-WP-Nonce: $NONCE" \
  -H "Content-Type: application/json" \
  -d '{"folder_id": 42, "include_children": true, "include_manifest": true}'
```

**Response** `201 Created`

```json
{
  "job_id": "69d5ed36-67b7-4c4d-bde9-7e6a2b1f407a",
  "folder_id": 42,
  "status": "pending",
  "progress": 0,
  "total": 0,
  "file_name": "",
  "file_size": 0,
  "created_at": "2026-02-11 14:34:54",
  "completed_at": "",
  "error": ""
}
```

---

### List exports

```
GET /exports
```

Returns exports owned by the current user (admins see all).

**Example**

```bash
curl "https://example.com/wp-json/vmfa-folder-exporter/v1/exports" \
  -H "X-WP-Nonce: $NONCE"
```

---

### Get export status

```
GET /exports/{id}
```

Poll this endpoint to track progress. The `status` field transitions through: `pending` → `processing` → `complete` (or `failed`).

**Example**

```bash
curl "https://example.com/wp-json/vmfa-folder-exporter/v1/exports/69d5ed36-67b7-4c4d-bde9-7e6a2b1f407a" \
  -H "X-WP-Nonce: $NONCE"
```

**Response** `200 OK`

```json
{
  "job_id": "69d5ed36-67b7-4c4d-bde9-7e6a2b1f407a",
  "folder_id": 42,
  "status": "complete",
  "progress": 14,
  "total": 14,
  "file_name": "Animals-2026-02-11-143533.zip",
  "file_size": 20971520,
  "created_at": "2026-02-11 14:34:54",
  "completed_at": "2026-02-11 14:35:33",
  "error": ""
}
```

---

### Delete an export

```
DELETE /exports/{id}
```

Deletes both the metadata and the ZIP file on disk.

**Example**

```bash
curl -X DELETE \
  "https://example.com/wp-json/vmfa-folder-exporter/v1/exports/69d5ed36-67b7-4c4d-bde9-7e6a2b1f407a" \
  -H "X-WP-Nonce: $NONCE"
```

**Response** `200 OK`

```json
{ "deleted": true }
```

---

### Download ZIP

```
GET /exports/{id}/download
```

Streams the ZIP file. Only available when status is `complete`.

**Example**

```bash
curl -OJ \
  "https://example.com/wp-json/vmfa-folder-exporter/v1/exports/69d5ed36-67b7-4c4d-bde9-7e6a2b1f407a/download" \
  -H "X-WP-Nonce: $NONCE"
```

---

## WP-CLI

### `wp vmfa-export folders`

List all folders with their IDs, names, paths, and media counts.

```bash
wp vmfa-export folders
wp vmfa-export folders --format=json
```

### `wp vmfa-export folder <id>`

Export a folder as a ZIP archive.

```bash
wp vmfa-export folder 42
wp vmfa-export folder 42 --output=/tmp/photos.zip
wp vmfa-export folder 42 --no-children --no-manifest
```

| Flag | Description |
|------|-------------|
| `--output=<path>` | Save ZIP to a specific path (default: current directory) |
| `--no-children` | Exclude subfolders |
| `--no-manifest` | Skip CSV manifest |

### `wp vmfa-export list`

List recent exports.

```bash
wp vmfa-export list
wp vmfa-export list --format=json
```

### `wp vmfa-export clean`

Remove expired or all exports.

```bash
wp vmfa-export clean          # Remove expired (>24h)
wp vmfa-export clean --all    # Remove all
```

---

## Building

```bash
composer install
npm install
npm run build
```

## Testing

```bash
composer test       # PHP tests (Pest)
npm test            # JS tests (Vitest)
npm run lint        # Lint JS/CSS
```
