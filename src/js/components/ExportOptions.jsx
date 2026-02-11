/**
 * Export options component — toggles for children and manifest.
 *
 * @package
 */

import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * ExportOptions component.
 *
 * @param {Object}   props                  Component props.
 * @param {boolean}  props.includeChildren  Whether to include subfolders.
 * @param {Function} props.onChangeChildren Callback when children toggle changes.
 * @param {boolean}  props.includeManifest  Whether to include CSV manifest.
 * @param {Function} props.onChangeManifest Callback when manifest toggle changes.
 * @return {import('react').ReactElement} The options panel.
 */
export function ExportOptions({
	includeChildren,
	onChangeChildren,
	includeManifest,
	onChangeManifest,
}) {
	return (
		<div className="vmfa-folder-exporter__options">
			<CheckboxControl
				label={__('Include subfolders', 'vmfa-folder-exporter')}
				help={__(
					'Export media from child folders as well, preserving the folder hierarchy in the ZIP.',
					'vmfa-folder-exporter'
				)}
				checked={includeChildren}
				onChange={onChangeChildren}
				__nextHasNoMarginBottom
			/>
			<CheckboxControl
				label={__('Include CSV manifest', 'vmfa-folder-exporter')}
				help={__(
					'Add a manifest.csv with metadata (ID, filename, URL, alt text, caption, size, etc.) to the ZIP root.',
					'vmfa-folder-exporter'
				)}
				checked={includeManifest}
				onChange={onChangeManifest}
				__nextHasNoMarginBottom
			/>
		</div>
	);
}
