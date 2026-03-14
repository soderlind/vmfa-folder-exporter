<?php
/**
 * Settings tab for Folder Exporter.
 *
 * @package VmfaFolderExporter
 */

declare(strict_types=1);

namespace VmfaFolderExporter\Admin;

defined( 'ABSPATH' ) || exit;

use VirtualMediaFolders\Addon\AbstractSettingsTab;
use VmfaFolderExporter\Plugin;

/**
 * Registers and renders the Folder Exporter settings tab.
 */
class SettingsTab extends AbstractSettingsTab {

	/** @inheritDoc */
	protected function get_tab_slug(): string {
		return 'folder-exporter';
	}

	/** @inheritDoc */
	protected function get_tab_label(): string {
		return __( 'Folder Exporter', 'vmfa-folder-exporter' );
	}

	/** @inheritDoc */
	protected function get_text_domain(): string {
		return 'vmfa-folder-exporter';
	}

	/** @inheritDoc */
	protected function get_build_path(): string {
		return VMFA_FOLDER_EXPORTER_PATH . 'build/';
	}

	/** @inheritDoc */
	protected function get_build_url(): string {
		return VMFA_FOLDER_EXPORTER_URL . 'build/';
	}

	/** @inheritDoc */
	protected function get_languages_path(): string {
		return VMFA_FOLDER_EXPORTER_PATH . 'languages';
	}

	/** @inheritDoc */
	protected function get_plugin_version(): string {
		return VMFA_FOLDER_EXPORTER_VERSION;
	}

	/** @inheritDoc */
	protected function get_localized_name(): string {
		return 'vmfaFolderExporter';
	}

	/** @inheritDoc */
	protected function get_localized_data(): array {
		return array(
			'restUrl' => rest_url( 'vmfa-folder-exporter/v1/' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'folders' => Plugin::get_folders(),
		);
	}

	/** @inheritDoc */
	protected function get_menu_capability(): string {
		return 'upload_files';
	}
}
