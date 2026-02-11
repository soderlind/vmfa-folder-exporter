<?php
/**
 * Uninstall handler for VMFA Folder Exporter.
 *
 * Fired when the plugin is deleted via the WordPress admin.
 * Removes all plugin data: options, export files, and scheduled actions.
 *
 * @package VmfaFolderExporter
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete all export metadata options.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$option_names = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( 'vmfa_export_' ) . '%'
	)
);

foreach ( $option_names as $name ) {
	$export = get_option( $name );

	// Delete associated ZIP files.
	if ( is_array( $export ) && ! empty( $export[ 'file_path' ] ) && file_exists( $export[ 'file_path' ] ) ) {
		wp_delete_file( $export[ 'file_path' ] );
	}

	delete_option( $name );
}

// Remove the export directory.
$upload_dir = wp_upload_dir();
$export_dir = $upload_dir[ 'basedir' ] . '/vmfa-exports';

if ( is_dir( $export_dir ) ) {
	// Remove protection and remaining files.
	$files = glob( $export_dir . '/*' );
	if ( is_array( $files ) ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
	rmdir( $export_dir );
}

// Unschedule Action Scheduler actions.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'vmfa_folder_exporter_build' );
	as_unschedule_all_actions( 'vmfa_folder_exporter_cleanup' );
}
