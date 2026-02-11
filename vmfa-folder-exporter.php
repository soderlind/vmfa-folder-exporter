<?php
/**
 * Plugin Name:       Virtual Media Folders - Folder Exporter
 * Plugin URI:        https://github.com/soderlind/vmfa-folder-exporter
 * Description:       Export folders (or subtrees) as ZIP archives with optional CSV manifests. Add-on for Virtual Media Folders.
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      8.3
 * Requires Plugins:  virtual-media-folders
 * Author:            Per Soderlind
 * Author URI:        https://soderlind.no
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vmfa-folder-exporter
 * Domain Path:       /languages
 *
 * @package VmfaFolderExporter
 */

declare(strict_types=1);

namespace VmfaFolderExporter;

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'VMFA_FOLDER_EXPORTER_VERSION', '1.0.0' );
define( 'VMFA_FOLDER_EXPORTER_FILE', __FILE__ );
define( 'VMFA_FOLDER_EXPORTER_PATH', plugin_dir_path( __FILE__ ) );
define( 'VMFA_FOLDER_EXPORTER_URL', plugin_dir_url( __FILE__ ) );
define( 'VMFA_FOLDER_EXPORTER_BASENAME', plugin_basename( __FILE__ ) );

// Require Composer autoloader.
if ( file_exists( VMFA_FOLDER_EXPORTER_PATH . 'vendor/autoload.php' ) ) {
	require_once VMFA_FOLDER_EXPORTER_PATH . 'vendor/autoload.php';
}

// Initialize Action Scheduler early (must be loaded before plugins_loaded).
// Action Scheduler uses its own version management, so it's safe to load even if another plugin bundles it.
if ( ! function_exists( 'as_schedule_single_action' ) ) {
	$action_scheduler_paths = array(
		VMFA_FOLDER_EXPORTER_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php',
		VMFA_FOLDER_EXPORTER_PATH . 'woocommerce/action-scheduler/action-scheduler.php',
	);

	foreach ( $action_scheduler_paths as $action_scheduler_path ) {
		if ( file_exists( $action_scheduler_path ) ) {
			require_once $action_scheduler_path;
			break;
		}
	}
}

/**
 * Initialize the plugin.
 *
 * @return void
 */
function init(): void {
	// Update checker via GitHub releases.
	if ( ! class_exists( \Soderlind\WordPress\GitHubUpdater::class) ) {
		require_once __DIR__ . '/class-github-updater.php';
	}
	\Soderlind\WordPress\GitHubUpdater::init(
		github_url: 'https://github.com/soderlind/vmfa-folder-exporter',
		plugin_file: VMFA_FOLDER_EXPORTER_FILE,
		plugin_slug: 'vmfa-folder-exporter',
		name_regex: '/vmfa-folder-exporter\.zip/',
		branch: 'main',
	);

	// Boot the plugin.
	Plugin::get_instance()->init();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init', 15 );
