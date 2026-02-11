<?php
/**
 * Main plugin class.
 *
 * @package VmfaFolderExporter
 */

declare(strict_types=1);

namespace VmfaFolderExporter;

defined( 'ABSPATH' ) || exit;

use VmfaFolderExporter\Admin\SettingsTab;
use VmfaFolderExporter\REST\ExportController;
use VmfaFolderExporter\Services\ExportService;
use VmfaFolderExporter\Services\ManifestService;
use VmfaFolderExporter\Services\CleanupService;

/**
 * Plugin bootstrap class.
 */
final class Plugin {

	/**
	 * Tab slug for registration with parent plugin.
	 */
	private const TAB_SLUG = 'folder-exporter';

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Export service.
	 *
	 * @var ExportService|null
	 */
	private ?ExportService $export_service = null;

	/**
	 * Manifest service.
	 *
	 * @var ManifestService|null
	 */
	private ?ManifestService $manifest_service = null;

	/**
	 * Cleanup service.
	 *
	 * @var CleanupService|null
	 */
	private ?CleanupService $cleanup_service = null;

	/**
	 * Settings tab.
	 *
	 * @var SettingsTab|null
	 */
	private ?SettingsTab $settings_tab = null;

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {}

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->init_services();
		$this->init_hooks();
		$this->init_cli();

		// Load textdomain on init hook when locale is set.
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'vmfa-folder-exporter',
			false,
			dirname( plugin_basename( VMFA_FOLDER_EXPORTER_FILE ) ) . '/languages'
		);
	}

	/**
	 * Initialize services.
	 *
	 * @return void
	 */
	private function init_services(): void {
		$this->manifest_service = new ManifestService();
		$this->cleanup_service  = new CleanupService();
		$this->export_service   = new ExportService( $this->manifest_service );
		$this->settings_tab     = new SettingsTab();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Admin hooks.
		if ( is_admin() ) {
			if ( $this->supports_parent_tabs() ) {
				// Register as a tab in the parent plugin.
				add_filter( 'vmfo_settings_tabs', array( $this->settings_tab, 'register_tab' ) );
				add_action( 'vmfo_settings_enqueue_scripts', array( $this->settings_tab, 'enqueue_tab_scripts' ), 10, 2 );
			} else {
				// Fall back to standalone menu.
				add_action( 'admin_menu', array( $this->settings_tab, 'register_admin_menu' ) );
				add_action( 'admin_enqueue_scripts', array( $this->settings_tab, 'enqueue_admin_assets' ) );
			}
		}

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Action Scheduler hooks for background export processing.
		add_action( 'vmfa_folder_exporter_build', array( $this->export_service, 'process_export' ), 10, 3 );

		// Cleanup expired exports — schedule hourly.
		add_action( 'vmfa_folder_exporter_cleanup', array( $this->cleanup_service, 'cleanup_expired' ) );
		$this->cleanup_service->schedule_cleanup();
	}

	/**
	 * Initialize WP-CLI commands.
	 *
	 * @return void
	 */
	private function init_cli(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command(
				'vmfa-export',
				new CLI\ExportCommand( $this->export_service, $this->manifest_service )
			);
		}
	}

	/**
	 * Check if the parent plugin supports add-on tabs.
	 *
	 * @return bool True if parent supports tabs, false otherwise.
	 */
	private function supports_parent_tabs(): bool {
		return defined( 'VirtualMediaFolders\Settings::SUPPORTS_ADDON_TABS' )
			&& \VirtualMediaFolders\Settings::SUPPORTS_ADDON_TABS;
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

	/**
	 * Get export service instance.
	 *
	 * @return ExportService
	 */
	public function get_export_service(): ExportService {
		return $this->export_service;
	}

	/**
	 * Get manifest service instance.
	 *
	 * @return ManifestService
	 */
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
