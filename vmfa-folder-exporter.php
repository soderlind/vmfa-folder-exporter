<?php
/**
 * Plugin Name:       Virtual Media Folders - Folder Exporter
 * Plugin URI:        https://github.com/soderlind/vmfa-folder-exporter
 * Description:       Export folders (or subtrees) as ZIP archives with optional CSV manifests. Add-on for Virtual Media Folders.
 * Version:           1.3.3
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
define( 'VMFA_FOLDER_EXPORTER_VERSION', '1.3.3' );
define( 'VMFA_FOLDER_EXPORTER_FILE', __FILE__ );
define( 'VMFA_FOLDER_EXPORTER_PATH', plugin_dir_path( __FILE__ ) );
define( 'VMFA_FOLDER_EXPORTER_URL', plugin_dir_url( __FILE__ ) );
define( 'VMFA_FOLDER_EXPORTER_BASENAME', plugin_basename( __FILE__ ) );

// Require Composer autoloader.
if ( file_exists( VMFA_FOLDER_EXPORTER_PATH . 'vendor/autoload.php' ) ) {
	require_once VMFA_FOLDER_EXPORTER_PATH . 'vendor/autoload.php';
}

// Initialize Action Scheduler early (must be loaded before plugins_loaded).
use VirtualMediaFolders\Addon\ActionSchedulerLoader;

if ( class_exists( ActionSchedulerLoader::class ) ) {
	ActionSchedulerLoader::maybe_load( VMFA_FOLDER_EXPORTER_PATH );
}

/**
 * Initialize the plugin.
 *
 * @return void
 */
function init(): void {
	// The parent plugin (Virtual Media Folders 2.0.0+) provides the add-on base class.
	if ( ! class_exists( \VirtualMediaFolders\Addon\AbstractPlugin::class ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\\missing_parent_notice' );
		return;
	}

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

/**
 * Admin notice shown when the required parent plugin is missing or outdated.
 *
 * @return void
 */
function missing_parent_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__(
			'Virtual Media Folders - Folder Exporter requires the "Virtual Media Folders" plugin (version 2.0.0 or later) to be installed and active.',
			'vmfa-folder-exporter'
		)
	);
}
