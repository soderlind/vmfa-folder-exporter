<?php
/**
 * Export service — builds ZIP archives from folder contents.
 *
 * @package VmfaFolderExporter
 */

declare(strict_types=1);

namespace VmfaFolderExporter\Services;

defined( 'ABSPATH' ) || exit;

use ZipArchive;

/**
 * Handles creating ZIP exports of folder contents.
 */
class ExportService {

	/**
	 * Export directory name inside uploads.
	 */
	public const EXPORT_DIR = 'vmfa-exports';

	/**
	 * Export expiry duration in seconds (24 hours).
	 */
	public const EXPORT_EXPIRY = DAY_IN_SECONDS;

	/**
	 * Manifest service.
	 *
	 * @var ManifestService
	 */
	private ManifestService $manifest_service;

	/**
	 * Constructor.
	 *
	 * @param ManifestService $manifest_service Manifest service instance.
	 */
	public function __construct( ManifestService $manifest_service ) {
		$this->manifest_service = $manifest_service;
	}

	/**
	 * Start a new export job via Action Scheduler.
	 *
	 * @param int   $folder_id        The folder (term) ID to export.
	 * @param bool  $include_children Whether to include subfolders.
	 * @param bool  $include_manifest Whether to include a CSV manifest.
	 * @param int   $user_id          The requesting user ID.
	 * @return string The export job ID.
	 */
	public function start_export( int $folder_id, bool $include_children = true, bool $include_manifest = true, int $user_id = 0 ): string {
		$job_id = wp_generate_uuid4();

		// Save initial export metadata.
		update_option(
			'vmfa_export_' . $job_id,
			array(
				'job_id'            => $job_id,
				'folder_id'         => $folder_id,
				'include_children'  => $include_children,
				'include_manifest'  => $include_manifest,
				'user_id'           => $user_id,
				'status'            => 'pending',
				'progress'          => 0,
				'total'             => 0,
				'file_path'         => '',
				'file_name'         => '',
				'file_size'         => 0,
				'created_at'        => current_time( 'mysql', true ),
				'completed_at'      => '',
				'error'             => '',
			),
			false // Do not autoload.
		);

		// Enqueue the background job.
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action(
				'vmfa_folder_exporter_build',
				array( $job_id, $folder_id, array(
					'include_children' => $include_children,
					'include_manifest' => $include_manifest,
					'user_id'          => $user_id,
				) ),
				'vmfa-folder-exporter'
			);
		}

		return $job_id;
	}

	/**
	 * Process an export job (Action Scheduler callback).
	 *
	 * @param string $job_id    The export job ID.
	 * @param int    $folder_id The folder (term) ID to export.
	 * @param array  $options   Export options.
	 * @return void
	 */
	public function process_export( string $job_id, int $folder_id, array $options ): void {
		$include_children = $options['include_children'] ?? true;
		$include_manifest = $options['include_manifest'] ?? true;

		try {
			$this->update_export_status( $job_id, 'processing' );

			// Get the folder term.
			$folder = get_term( $folder_id, 'vmfo_folder' );
			if ( ! $folder || is_wp_error( $folder ) ) {
				$this->update_export_status( $job_id, 'failed', __( 'Folder not found.', 'vmfa-folder-exporter' ) );
				return;
			}

			// Collect folder IDs to export.
			$term_ids = array( $folder_id );
			if ( $include_children ) {
				$children = get_term_children( $folder_id, 'vmfo_folder' );
				if ( ! is_wp_error( $children ) ) {
					$term_ids = array_merge( $term_ids, $children );
				}
			}

			// Get all attachments in these folders.
			$attachments = $this->get_attachments( $term_ids );
			$total       = count( $attachments );

			$this->update_export_meta( $job_id, array( 'total' => $total ) );

			if ( 0 === $total ) {
				$this->update_export_status( $job_id, 'failed', __( 'No media files found in this folder.', 'vmfa-folder-exporter' ) );
				return;
			}

			// Create the export directory.
			$export_dir = $this->get_export_dir();
			if ( ! $export_dir ) {
				$this->update_export_status( $job_id, 'failed', __( 'Could not create export directory.', 'vmfa-folder-exporter' ) );
				return;
			}

			// Build the ZIP file.
			$folder_name = sanitize_file_name( $folder->name );
			$file_name   = $folder_name . '-' . gmdate( 'Y-m-d-His' ) . '.zip';
			$file_path   = $export_dir . '/' . $file_name;

			$result = $this->build_zip( $file_path, $attachments, $term_ids, $include_manifest, $job_id );

			if ( is_wp_error( $result ) ) {
				$this->update_export_status( $job_id, 'failed', $result->get_error_message() );
				return;
			}

			// Update export metadata with results.
			$this->update_export_meta(
				$job_id,
				array(
					'status'       => 'complete',
					'file_path'    => $file_path,
					'file_name'    => $file_name,
					'file_size'    => filesize( $file_path ),
					'completed_at' => current_time( 'mysql', true ),
					'progress'     => $total,
				)
			);
		} catch ( \Exception $e ) {
			$this->update_export_status( $job_id, 'failed', $e->getMessage() );
		}
	}

	/**
	 * Build a ZIP archive from attachments.
	 *
	 * @param string $file_path        Full path for the ZIP file.
	 * @param array  $attachments      Array of WP_Post attachment objects.
	 * @param array  $term_ids         Array of folder term IDs being exported.
	 * @param bool   $include_manifest Whether to include a CSV manifest.
	 * @param string $job_id           The job ID for progress updates.
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	public function build_zip( string $file_path, array $attachments, array $term_ids, bool $include_manifest, string $job_id ): true|\WP_Error {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new \WP_Error( 'zip_unavailable', __( 'ZipArchive extension is not available.', 'vmfa-folder-exporter' ) );
		}

		$zip = new ZipArchive();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$open_result = $zip->open( $file_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );

		if ( true !== $open_result ) {
			return new \WP_Error( 'zip_create_failed', __( 'Could not create ZIP file.', 'vmfa-folder-exporter' ) );
		}

		// Build folder path map for directory structure in ZIP.
		$folder_paths = $this->build_folder_path_map( $term_ids );

		$processed = 0;
		foreach ( $attachments as $attachment ) {
			$source_file = get_attached_file( $attachment->ID );

			if ( ! $source_file || ! file_exists( $source_file ) ) {
				++$processed;
				continue;
			}

			// Determine the folder path for this attachment.
			$attachment_folder = $this->get_attachment_folder( $attachment->ID );
			$zip_dir           = $attachment_folder && isset( $folder_paths[ $attachment_folder ] )
				? $folder_paths[ $attachment_folder ] . '/'
				: '';

			$filename = wp_basename( $source_file );
			$zip_path = $zip_dir . $filename;

			// Handle duplicate filenames in the same directory.
			$counter = 1;
			while ( false !== $zip->locateName( $zip_path ) ) {
				$info     = pathinfo( $filename );
				$zip_path = $zip_dir . $info['filename'] . '-' . $counter . '.' . ( $info['extension'] ?? '' );
				++$counter;
			}

			$zip->addFile( $source_file, $zip_path );

			++$processed;

			// Update progress every 10 files.
			if ( 0 === $processed % 10 ) {
				$this->update_export_meta( $job_id, array( 'progress' => $processed ) );
			}
		}

		// Add CSV manifest if requested.
		if ( $include_manifest ) {
			$csv_content = $this->manifest_service->generate( $attachments, $folder_paths );
			$zip->addFromString( 'manifest.csv', $csv_content );
		}

		$zip->close();

		return true;
	}

	/**
	 * Get attachments assigned to given folder term IDs.
	 *
	 * @param array $term_ids Array of term IDs.
	 * @return array Array of WP_Post objects.
	 */
	public function get_attachments( array $term_ids ): array {
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'vmfo_folder',
					'field'    => 'term_id',
					'terms'    => $term_ids,
				),
			),
			'fields'         => '',
		);

		return get_posts( $args );
	}

	/**
	 * Build a map of term_id => folder path string.
	 *
	 * @param array $term_ids Array of term IDs to map.
	 * @return array<int, string> Map of term_id => path like "Photos/2025/Summer".
	 */
	public function build_folder_path_map( array $term_ids ): array {
		$map = array();

		foreach ( $term_ids as $term_id ) {
			$ancestors = get_ancestors( $term_id, 'vmfo_folder', 'taxonomy' );
			$parts     = array();

			// Ancestors are returned closest-first to root; reverse for path order.
			foreach ( array_reverse( $ancestors ) as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'vmfo_folder' );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$parts[] = sanitize_file_name( $ancestor->name );
				}
			}

			$term = get_term( $term_id, 'vmfo_folder' );
			if ( $term && ! is_wp_error( $term ) ) {
				$parts[] = sanitize_file_name( $term->name );
			}

			$map[ $term_id ] = implode( '/', $parts );
		}

		return $map;
	}

	/**
	 * Get the folder term ID for a given attachment.
	 *
	 * @param int $attachment_id The attachment post ID.
	 * @return int|null The term ID or null if unassigned.
	 */
	public function get_attachment_folder( int $attachment_id ): ?int {
		$terms = wp_get_object_terms( $attachment_id, 'vmfo_folder', array( 'fields' => 'ids' ) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}

		return (int) $terms[0];
	}

	/**
	 * Get export metadata.
	 *
	 * @param string $job_id The export job ID.
	 * @return array|false Export metadata or false if not found.
	 */
	public function get_export( string $job_id ): array|false {
		return get_option( 'vmfa_export_' . $job_id, false );
	}

	/**
	 * Get recent exports.
	 *
	 * @param int $limit Maximum number of exports to return.
	 * @return array Array of export metadata arrays.
	 */
	public function get_recent_exports( int $limit = 20 ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$option_names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id DESC LIMIT %d",
				$wpdb->esc_like( 'vmfa_export_' ) . '%',
				$limit
			)
		);

		$exports = array();
		foreach ( $option_names as $name ) {
			$data = get_option( $name );
			if ( is_array( $data ) && isset( $data['job_id'] ) ) {
				$exports[] = $data;
			}
		}

		return $exports;
	}

	/**
	 * Delete an export and its ZIP file.
	 *
	 * @param string $job_id The export job ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_export( string $job_id ): bool {
		$export = $this->get_export( $job_id );

		if ( ! $export ) {
			return false;
		}

		// Delete the ZIP file if it exists.
		if ( ! empty( $export['file_path'] ) && file_exists( $export['file_path'] ) ) {
			wp_delete_file( $export['file_path'] );
		}

		return delete_option( 'vmfa_export_' . $job_id );
	}

	/**
	 * Get or create the export directory.
	 *
	 * @return string|false The export directory path, or false on failure.
	 */
	public function get_export_dir(): string|false {
		$upload_dir = wp_upload_dir();
		$export_dir = $upload_dir['basedir'] . '/' . self::EXPORT_DIR;

		if ( ! file_exists( $export_dir ) ) {
			if ( ! wp_mkdir_p( $export_dir ) ) {
				return false;
			}

			// Protect directory from direct browsing.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $export_dir . '/.htaccess', 'Deny from all' );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $export_dir . '/index.php', '<?php // Silence is golden.' );
		}

		return $export_dir;
	}

	/**
	 * Update the status of an export job.
	 *
	 * @param string $job_id The export job ID.
	 * @param string $status The new status.
	 * @param string $error  Optional error message.
	 * @return void
	 */
	private function update_export_status( string $job_id, string $status, string $error = '' ): void {
		$data = array( 'status' => $status );
		if ( '' !== $error ) {
			$data['error'] = $error;
		}
		$this->update_export_meta( $job_id, $data );
	}

	/**
	 * Update export metadata.
	 *
	 * @param string $job_id The export job ID.
	 * @param array  $data   Key-value pairs to merge into export metadata.
	 * @return void
	 */
	private function update_export_meta( string $job_id, array $data ): void {
		$export = $this->get_export( $job_id );
		if ( ! $export ) {
			return;
		}

		$export = array_merge( $export, $data );
		update_option( 'vmfa_export_' . $job_id, $export, false );
	}

	/**
	 * Build a ZIP synchronously (for WP-CLI).
	 *
	 * @param int    $folder_id        The folder (term) ID to export.
	 * @param string $output_path      Full path for the output ZIP file.
	 * @param bool   $include_children Whether to include subfolders.
	 * @param bool   $include_manifest Whether to include a CSV manifest.
	 * @param callable|null $progress_callback Optional callback for progress reporting. Receives (int $processed, int $total).
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	public function build_zip_sync(
		int $folder_id,
		string $output_path,
		bool $include_children = true,
		bool $include_manifest = true,
		?callable $progress_callback = null
	): true|\WP_Error {
		$folder = get_term( $folder_id, 'vmfo_folder' );
		if ( ! $folder || is_wp_error( $folder ) ) {
			return new \WP_Error( 'invalid_folder', __( 'Folder not found.', 'vmfa-folder-exporter' ) );
		}

		// Collect folder IDs.
		$term_ids = array( $folder_id );
		if ( $include_children ) {
			$children = get_term_children( $folder_id, 'vmfo_folder' );
			if ( ! is_wp_error( $children ) ) {
				$term_ids = array_merge( $term_ids, $children );
			}
		}

		$attachments = $this->get_attachments( $term_ids );
		$total       = count( $attachments );

		if ( 0 === $total ) {
			return new \WP_Error( 'no_files', __( 'No media files found in this folder.', 'vmfa-folder-exporter' ) );
		}

		if ( ! class_exists( 'ZipArchive' ) ) {
			return new \WP_Error( 'zip_unavailable', __( 'ZipArchive extension is not available.', 'vmfa-folder-exporter' ) );
		}

		$zip = new ZipArchive();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$open_result = $zip->open( $output_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );

		if ( true !== $open_result ) {
			return new \WP_Error( 'zip_create_failed', __( 'Could not create ZIP file.', 'vmfa-folder-exporter' ) );
		}

		$folder_paths = $this->build_folder_path_map( $term_ids );
		$processed    = 0;

		foreach ( $attachments as $attachment ) {
			$source_file = get_attached_file( $attachment->ID );

			if ( ! $source_file || ! file_exists( $source_file ) ) {
				++$processed;
				if ( $progress_callback ) {
					$progress_callback( $processed, $total );
				}
				continue;
			}

			$attachment_folder = $this->get_attachment_folder( $attachment->ID );
			$zip_dir           = $attachment_folder && isset( $folder_paths[ $attachment_folder ] )
				? $folder_paths[ $attachment_folder ] . '/'
				: '';

			$filename = wp_basename( $source_file );
			$zip_path = $zip_dir . $filename;

			// Handle duplicate filenames.
			$counter = 1;
			while ( false !== $zip->locateName( $zip_path ) ) {
				$info     = pathinfo( $filename );
				$zip_path = $zip_dir . $info['filename'] . '-' . $counter . '.' . ( $info['extension'] ?? '' );
				++$counter;
			}

			$zip->addFile( $source_file, $zip_path );

			++$processed;
			if ( $progress_callback ) {
				$progress_callback( $processed, $total );
			}
		}

		if ( $include_manifest ) {
			$csv_content = $this->manifest_service->generate( $attachments, $folder_paths );
			$zip->addFromString( 'manifest.csv', $csv_content );
		}

		$zip->close();

		return true;
	}
}
