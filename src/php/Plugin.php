<?php
/**
 * Main plugin class.
 *
 * @package VmfaFolderExporter
 */

declare(strict_types=1);

namespace VmfaFolderExporter;

defined( 'ABSPATH' ) || exit;

use VirtualMediaFolders\Addon\AbstractPlugin;
use VmfaFolderExporter\Admin\SettingsTab;
use VmfaFolderExporter\REST\ExportController;
use VmfaFolderExporter\Services\ExportService;
use VmfaFolderExporter\Services\ManifestService;
use VmfaFolderExporter\Services\CleanupService;

/**
 * Plugin bootstrap class.
 */
final class Plugin extends AbstractPlugin {

	private ?ExportService $export_service   = null;
	private ?ManifestService $manifest_service = null;
	private ?CleanupService $cleanup_service  = null;
	private ?SettingsTab $settings_tab       = null;

	/** @inheritDoc */
	protected function get_text_domain(): string {
		return 'vmfa-folder-exporter';
	}

	/** @inheritDoc */
	protected function get_plugin_file(): string {
		return VMFA_FOLDER_EXPORTER_FILE;
	}

	/** @inheritDoc */
	protected function init_services(): void {
		$this->manifest_service = new ManifestService();
		$this->cleanup_service  = new CleanupService();
		$this->export_service   = new ExportService( $this->manifest_service );
		$this->settings_tab     = new SettingsTab();
	}

	/** @inheritDoc */
	protected function init_hooks(): void {
		// Admin hooks.
		if ( is_admin() ) {
			if ( $this->supports_parent_tabs() ) {
				add_filter( 'vmfo_settings_tabs', array( $this->settings_tab, 'register_tab' ) );
				add_action( 'vmfo_settings_enqueue_scripts', array( $this->settings_tab, 'enqueue_tab_scripts' ), 10, 2 );
			} else {
				add_action( 'admin_menu', array( $this->settings_tab, 'register_admin_menu' ) );
				add_action( 'admin_enqueue_scripts', array( $this->settings_tab, 'enqueue_admin_assets' ) );
			}
		}

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Action Scheduler hooks for background export processing.
		add_action( 'vmfa_folder_exporter_build', array( $this->export_service, 'process_export' ), 10, 3 );

		// Cleanup expired exports — schedule hourly (defer to init to ensure AS data store is ready).
		add_action( 'vmfa_folder_exporter_cleanup', array( $this->cleanup_service, 'cleanup_expired' ) );
		add_action( 'init', array( $this->cleanup_service, 'schedule_cleanup' ), 20 );
	}

	/** @inheritDoc */
	protected function init_cli(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command(
				'vmfa-export',
				new CLI\ExportCommand( $this->export_service, $this->manifest_service )
			);
		}
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$export_controller = new ExportController( $this->export_service );
		$export_controller->register_routes();
	}

	public function get_export_service(): ExportService {
		return $this->export_service;
	}

	public function get_manifest_service(): ManifestService {
		return $this->manifest_service;
	}

	/**
	 * Get folders from parent plugin.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_folders(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'vmfo_folder',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$folders = array();
		foreach ( $terms as $term ) {
			$folders[] = array(
				'id'     => $term->term_id,
				'name'   => $term->name,
				'slug'   => $term->slug,
				'parent' => $term->parent,
				'count'  => $term->count,
			);
		}

		return $folders;
	}
}
