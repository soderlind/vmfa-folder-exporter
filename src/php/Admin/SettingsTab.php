<?php
/**
 * Settings tab for Folder Exporter.
 *
 * @package VmfaFolderExporter
 */

declare(strict_types=1);

namespace VmfaFolderExporter\Admin;

defined( 'ABSPATH' ) || exit;

use VmfaFolderExporter\Plugin;

/**
 * Registers and renders the Folder Exporter settings tab.
 */
class SettingsTab {

	/**
	 * Tab slug.
	 */
	private const TAB_SLUG = 'folder-exporter';

	/**
	 * Register tab with parent plugin.
	 *
	 * @param array $tabs Existing tabs array.
	 * @return array Modified tabs array.
	 */
	public function register_tab( array $tabs ): array {
		$tabs[ self::TAB_SLUG ] = array(
			'title'    => __( 'Folder Exporter', 'vmfa-folder-exporter' ),
			'callback' => array( $this, 'render_tab_content' ),
		);
		return $tabs;
	}

	/**
	 * Render tab content within parent plugin's settings page.
	 *
	 * @param string $active_tab    The currently active tab slug.
	 * @param string $active_subtab The currently active subtab slug.
	 * @return void
	 */
	public function render_tab_content( string $active_tab, string $active_subtab ): void {
		?>
		<div class="vmfa-tab-content">
			<div id="vmfa-folder-exporter-app"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue scripts when Folder Exporter tab is active.
	 *
	 * @param string $active_tab    The currently active tab slug.
	 * @param string $active_subtab The currently active subtab slug.
	 * @return void
	 */
	public function enqueue_tab_scripts( string $active_tab, string $active_subtab ): void {
		if ( self::TAB_SLUG !== $active_tab ) {
			return;
		}

		$this->do_enqueue_assets();
	}

	/**
	 * Register admin menu (fallback when parent doesn't support tabs).
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		add_submenu_page(
			'upload.php',
			__( 'Virtual Media Folders Folder Exporter', 'vmfa-folder-exporter' ),
			__( 'Folder Exporter', 'vmfa-folder-exporter' ),
			'upload_files',
			'vmfa-folder-exporter',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render admin page (fallback for standalone page).
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Virtual Media Folders Folder Exporter', 'vmfa-folder-exporter' ); ?></h1>
			<div id="vmfa-folder-exporter-app"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue admin assets (fallback for standalone page).
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'media_page_vmfa-folder-exporter' !== $hook_suffix ) {
			return;
		}

		$this->do_enqueue_assets();
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @return void
	 */
	private function do_enqueue_assets(): void {
		$asset_file = VMFA_FOLDER_EXPORTER_PATH . 'build/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		// Append file modification time to version hash to bust aggressive caches.
		$js_file     = VMFA_FOLDER_EXPORTER_PATH . 'build/index.js';
		$css_file    = VMFA_FOLDER_EXPORTER_PATH . 'build/index.css';
		$js_version  = $asset['version'] . '.' . ( file_exists( $js_file ) ? filemtime( $js_file ) : '' );
		$css_version = $asset['version'] . '.' . ( file_exists( $css_file ) ? filemtime( $css_file ) : '' );

		wp_enqueue_script(
			'vmfa-folder-exporter-admin',
			VMFA_FOLDER_EXPORTER_URL . 'build/index.js',
			$asset['dependencies'],
			$js_version,
			true
		);

		wp_set_script_translations(
			'vmfa-folder-exporter-admin',
			'vmfa-folder-exporter',
			VMFA_FOLDER_EXPORTER_PATH . 'languages'
		);

		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'vmfa-folder-exporter-admin',
				VMFA_FOLDER_EXPORTER_URL . 'build/index.css',
				array( 'wp-components' ),
				$css_version
			);
		}

		wp_localize_script(
			'vmfa-folder-exporter-admin',
			'vmfaFolderExporter',
			array(
				'restUrl' => rest_url( 'vmfa-folder-exporter/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'folders' => Plugin::get_folders(),
			)
		);
	}
}
