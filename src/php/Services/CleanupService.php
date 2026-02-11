<?php
/**
 * Cleanup service — removes expired export files.
 *
 * @package VmfaFolderExporter
 */

declare(strict_types=1);

namespace VmfaFolderExporter\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Handles scheduled cleanup of expired export ZIP files.
 */
class CleanupService {

	/**
	 * Schedule the cleanup hook if not already scheduled.
	 *
	 * @return void
	 */
	public function schedule_cleanup(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

		if ( ! as_has_scheduled_action( 'vmfa_folder_exporter_cleanup' ) ) {
			as_schedule_recurring_action(
				time() + HOUR_IN_SECONDS,
				HOUR_IN_SECONDS,
				'vmfa_folder_exporter_cleanup',
				array(),
				'vmfa-folder-exporter'
			);
		}
	}

	/**
	 * Clean up expired export files and their metadata.
	 *
	 * @return int Number of exports cleaned up.
	 */
	public function cleanup_expired(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$option_names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( 'vmfa_export_' ) . '%'
			)
		);

		$cleaned = 0;

		foreach ( $option_names as $name ) {
			$export = get_option( $name );

			if ( ! is_array( $export ) || ! isset( $export[ 'created_at' ] ) ) {
				continue;
			}

			$created = strtotime( $export[ 'created_at' ] );
			if ( false === $created ) {
				continue;
			}

			// Check if export has expired (24 hours).
			if ( time() - $created < ExportService::EXPORT_EXPIRY ) {
				continue;
			}

			// Delete the ZIP file.
			if ( ! empty( $export[ 'file_path' ] ) && file_exists( $export[ 'file_path' ] ) ) {
				wp_delete_file( $export[ 'file_path' ] );
			}

			delete_option( $name );
			++$cleaned;
		}

		return $cleaned;
	}

	/**
	 * Delete all exports and their files.
	 *
	 * @return int Number of exports deleted.
	 */
	public function delete_all(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$option_names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( 'vmfa_export_' ) . '%'
			)
		);

		$deleted = 0;

		foreach ( $option_names as $name ) {
			$export = get_option( $name );

			if ( is_array( $export ) && ! empty( $export[ 'file_path' ] ) && file_exists( $export[ 'file_path' ] ) ) {
				wp_delete_file( $export[ 'file_path' ] );
			}

			delete_option( $name );
			++$deleted;
		}

		// Remove the export directory.
		$upload_dir = wp_upload_dir();
		$default    = $upload_dir[ 'basedir' ] . '/' . ExportService::EXPORT_DIR;

		/** This filter is documented in ExportService::get_export_dir(). */
		$export_dir = apply_filters( 'vmfa_export_dir', $default );

		if ( is_dir( $export_dir ) ) {
			// Remove protection files.
			$htaccess = $export_dir . '/.htaccess';
			$index    = $export_dir . '/index.php';
			if ( file_exists( $htaccess ) ) {
				wp_delete_file( $htaccess );
			}
			if ( file_exists( $index ) ) {
				wp_delete_file( $index );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			rmdir( $export_dir );
		}

		return $deleted;
	}
}
