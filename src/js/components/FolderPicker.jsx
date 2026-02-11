/**
 * Folder picker component with hierarchical indentation.
 *
 * @package
 */

import { useMemo } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Build a flat, indented option list from a hierarchical folder tree.
 *
 * @param {Array}  folders  Flat array of folder objects with { id, name, parent }.
 * @param {number} parentId Parent term ID to start from.
 * @param {number} depth    Current depth level.
 * @return {Array} Flat array of { label, value } options.
 */
function buildOptions(folders, parentId = 0, depth = 0) {
	const result = [];
	const children = folders.filter((f) => f.parent === parentId);

	for (const folder of children) {
		const prefix = '\u00A0'.repeat(depth * 4);
		result.push({
			label: `${prefix}${folder.name} (${folder.count})`,
			value: String(folder.id),
		});
		result.push(...buildOptions(folders, folder.id, depth + 1));
	}

	return result;
}

/**
 * FolderPicker component.
 *
 * @param {Object}      props                Component props.
 * @param {number|null} props.selectedFolder The selected folder ID.
 * @param {Function}    props.onChange       Callback when selection changes.
 * @return {import('react').ReactElement} The folder picker.
 */
export function FolderPicker({ selectedFolder, onChange }) {
	const folders = useMemo(() => window.vmfaFolderExporter?.folders || [], []);

	const options = useMemo(() => {
		const items = buildOptions(folders);
		return [
			{
				label: __('— Select a folder —', 'vmfa-folder-exporter'),
				value: '',
			},
			...items,
		];
	}, [folders]);

	return (
		<div className="vmfa-folder-exporter__folder-picker">
			<SelectControl
				label={__('Folder', 'vmfa-folder-exporter')}
				value={selectedFolder ? String(selectedFolder) : ''}
				options={options}
				onChange={(value) => onChange(value ? Number(value) : null)}
				__nextHasNoMarginBottom
			/>
		</div>
	);
}
