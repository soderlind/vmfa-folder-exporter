/**
 * Export button component — triggers the export.
 *
 * @package
 */

import { useState } from '@wordpress/element';
import { Button, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * ExportButton component.
 *
 * @param {Object}      props                 Component props.
 * @param {number|null} props.folderId        The selected folder ID.
 * @param {boolean}     props.includeChildren Whether to include subfolders.
 * @param {boolean}     props.includeManifest Whether to include CSV manifest.
 * @param {Function}    props.onExportStarted Callback with export data on success.
 * @return {import('react').ReactElement} The export button.
 */
export function ExportButton({
	folderId,
	includeChildren,
	includeManifest,
	onExportStarted,
}) {
	const [isLoading, setIsLoading] = useState(false);
	const [error, setError] = useState(null);

	const handleExport = async () => {
		if (!folderId) {
			return;
		}

		setIsLoading(true);
		setError(null);

		try {
			const result = await apiFetch({
				path: 'vmfa-folder-exporter/v1/exports',
				method: 'POST',
				data: {
					folder_id: folderId,
					include_children: includeChildren,
					include_manifest: includeManifest,
				},
			});

			onExportStarted(result);
		} catch (err) {
			setError(
				err.message || __('An error occurred.', 'vmfa-folder-exporter')
			);
		} finally {
			setIsLoading(false);
		}
	};

	return (
		<div className="vmfa-folder-exporter__export-button">
			{error && (
				<Notice
					status="error"
					isDismissible
					onDismiss={() => setError(null)}
				>
					{error}
				</Notice>
			)}
			<Button
				variant="primary"
				onClick={handleExport}
				isBusy={isLoading}
				disabled={!folderId || isLoading}
			>
				{isLoading
					? __('Starting export…', 'vmfa-folder-exporter')
					: __('Export as ZIP', 'vmfa-folder-exporter')}
			</Button>
		</div>
	);
}
