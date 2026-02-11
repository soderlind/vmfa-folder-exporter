<?php
/**
 * WP-CLI commands for Folder Exporter.
 *
 * @package VmfaFolderExporter
 */

declare(strict_types=1);

namespace VmfaFolderExporter\CLI;

defined( 'ABSPATH' ) || exit;

use VmfaFolderExporter\Services\ExportService;
use VmfaFolderExporter\Services\ManifestService;
use VmfaFolderExporter\Services\CleanupService;
use WP_CLI;
use WP_CLI\Utils;

/**
 * Export virtual media folders as ZIP archives.
 *
 * ## EXAMPLES
 *
 *     # List available folders with IDs
 *     wp vmfa-export folders
 *
 *     # Export a folder with subfolders
 *     wp vmfa-export folder 42
 *
 *     # Export without subfolders or manifest
 *     wp vmfa-export folder 42 --no-children --no-manifest
 *
 *     # Export to a specific path
 *     wp vmfa-export folder 42 --output=/tmp/my-export.zip
 *
 *     # List recent exports
 *     wp vmfa-export list
 *
 *     # Clean up expired exports
 *     wp vmfa-export clean
 */
class ExportCommand {

	/**
	 * Export service.
	 *
	 * @var ExportService
	 */
	private ExportService $export_service;

	/**
	 * Manifest service.
	 *
	 * @var ManifestService
	 */
	private ManifestService $manifest_service;

	/**
	 * Constructor.
	 *
	 * @param ExportService   $export_service   Export service instance.
	 * @param ManifestService $manifest_service Manifest service instance.
	 */
	public function __construct( ExportService $export_service, ManifestService $manifest_service ) {
		$this->export_service   = $export_service;
		$this->manifest_service = $manifest_service;
	}

	/**
	 * Export a folder as a ZIP archive.
	 *
	 * ## OPTIONS
	 *
	 * <folder_id>
	 * : The folder (term) ID to export.
	 *
	 * [--output=<path>]
	 * : Output path for the ZIP file. Defaults to current directory.
	 *
	 * [--no-children]
	 * : Exclude subfolders from the export.
	 *
	 * [--no-manifest]
	 * : Do not include a CSV manifest in the ZIP.
	 *
	 * ## EXAMPLES
	 *
	 *     # Export folder 42 to current directory
	 *     wp vmfa-export folder 42
	 *
	 *     # Export folder 42 without children
	 *     wp vmfa-export folder 42 --no-children
	 *
	 *     # Export to specific path
	 *     wp vmfa-export folder 42 --output=/tmp/photos.zip
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function folder( array $args, array $assoc_args ): void {
		$folder_id        = (int) $args[ 0 ];
		$include_children = ! Utils\get_flag_value( $assoc_args, 'no-children', false );
		$include_manifest = ! Utils\get_flag_value( $assoc_args, 'no-manifest', false );

		// Verify the folder exists.
		$folder = get_term( $folder_id, 'vmfo_folder' );
		if ( ! $folder || is_wp_error( $folder ) ) {
			WP_CLI::error( sprintf( 'Folder with ID %d not found.', $folder_id ) );
		}

		// Determine output path.
		$folder_name = sanitize_file_name( $folder->name );
		$default     = getcwd() . '/' . $folder_name . '-' . gmdate( 'Y-m-d-His' ) . '.zip';
		$output_path = Utils\get_flag_value( $assoc_args, 'output', $default );

		WP_CLI::log( sprintf( 'Exporting folder "%s" (ID: %d)…', $folder->name, $folder_id ) );

		if ( $include_children ) {
			WP_CLI::log( 'Including subfolders.' );
		}
		if ( $include_manifest ) {
			WP_CLI::log( 'Including CSV manifest.' );
		}

		// Collect term IDs for progress reporting.
		$term_ids = array( $folder_id );
		if ( $include_children ) {
			$children = get_term_children( $folder_id, 'vmfo_folder' );
			if ( ! is_wp_error( $children ) ) {
				$term_ids = array_merge( $term_ids, $children );
			}
		}

		$attachments = $this->export_service->get_attachments( $term_ids );
		$total       = count( $attachments );

		if ( 0 === $total ) {
			WP_CLI::error( 'No media files found in this folder.' );
		}

		WP_CLI::log( sprintf( 'Found %d media file(s).', $total ) );

		$progress = Utils\make_progress_bar( 'Building ZIP', $total );

		$result = $this->export_service->build_zip_sync(
			$folder_id,
			$output_path,
			$include_children,
			$include_manifest,
			function ( int $processed, int $total_count ) use ( $progress ) {
				$progress->tick();
			}
		);

		$progress->finish();

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$size = size_format( filesize( $output_path ) );
		WP_CLI::success( sprintf( 'Export saved to %s (%s)', $output_path, $size ) );
	}

	/**
	 * List recent exports.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format. Accepts table, json, csv, yaml.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp vmfa-export list
	 *     wp vmfa-export list --format=json
	 *
	 * @subcommand list
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function list_exports( array $args, array $assoc_args ): void {
		$exports = $this->export_service->get_recent_exports();

		if ( empty( $exports ) ) {
			WP_CLI::log( 'No exports found.' );
			return;
		}

		$items = array_map( function ( $export ) {
			return array(
				'job_id'     => $export[ 'job_id' ] ?? '',
				'folder_id'  => $export[ 'folder_id' ] ?? '',
				'status'     => $export[ 'status' ] ?? '',
				'file_name'  => $export[ 'file_name' ] ?? '',
				'file_size'  => isset( $export[ 'file_size' ] ) ? size_format( (int) $export[ 'file_size' ] ) : '',
				'created_at' => $export[ 'created_at' ] ?? '',
			);
		}, $exports );

		$format = Utils\get_flag_value( $assoc_args, 'format', 'table' );

		Utils\format_items( $format, $items, array( 'job_id', 'folder_id', 'status', 'file_name', 'file_size', 'created_at' ) );
	}

	/**
	 * List available folders with their IDs.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format. Accepts table, json, csv, yaml.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp vmfa-export folders
	 *     wp vmfa-export folders --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function folders( array $args, array $assoc_args ): void {
		$terms = get_terms(
			array(
				'taxonomy'   => 'vmfo_folder',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			WP_CLI::log( 'No folders found.' );
			return;
		}

		// Build a parent → path map for display.
		$path_map = array();
		foreach ( $terms as $term ) {
			$ancestors = array_reverse( get_ancestors( $term->term_id, 'vmfo_folder', 'taxonomy' ) );
			$parts     = array();
			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'vmfo_folder' );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$parts[] = $ancestor->name;
				}
			}
			$parts[]                      = $term->name;
			$path_map[ $term->term_id ] = implode( '/', $parts );
		}

		$items = array_map( function ( $term ) use ( $path_map ) {
			return array(
				'id'    => $term->term_id,
				'name'  => $term->name,
				'path'  => $path_map[ $term->term_id ] ?? $term->name,
				'count' => $term->count,
			);
		}, $terms );

		$format = Utils\get_flag_value( $assoc_args, 'format', 'table' );

		Utils\format_items( $format, $items, array( 'id', 'name', 'path', 'count' ) );
	}

	/**
	 * Clean up expired export files.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Delete all exports, not just expired ones.
	 *
	 * ## EXAMPLES
	 *
	 *     wp vmfa-export clean
	 *     wp vmfa-export clean --all
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function clean( array $args, array $assoc_args ): void {
		$cleanup = new CleanupService();
		$all     = Utils\get_flag_value( $assoc_args, 'all', false );

		if ( $all ) {
			$count = $cleanup->delete_all();
			WP_CLI::success( sprintf( 'Deleted %d export(s).', $count ) );
		} else {
			$count = $cleanup->cleanup_expired();
			WP_CLI::success( sprintf( 'Cleaned up %d expired export(s).', $count ) );
		}
	}
}
