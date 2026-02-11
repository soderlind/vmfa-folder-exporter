/**
 * Export history component — shows recent exports.
 *
 * @package
 */

import { useState, useEffect } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { download as downloadIcon, trash } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';

/**
 * Format bytes into a human-readable string.
 *
 * @param {number} bytes The byte count.
 * @return {string} Formatted size string.
 */
function formatBytes(bytes) {
	if (!bytes) {
		return '—';
	}
	const units = ['B', 'KB', 'MB', 'GB'];
	let unit = 0;
	let size = bytes;
	while (size >= 1024 && unit < units.length - 1) {
		size /= 1024;
		unit++;
	}
	return `${size.toFixed(1)} ${units[unit]}`;
}

/**
 * ExportHistory component.
 *
 * @param {Object} props            Component props.
 * @param {number} props.refreshKey Key to trigger re-fetch.
 * @return {import('react').ReactElement} The history table.
 */
export function ExportHistory({ refreshKey }) {
	const [exports, setExports] = useState([]);
	const [isLoading, setIsLoading] = useState(true);

	const fetchExports = async () => {
		setIsLoading(true);
		try {
			const result = await apiFetch({
				path: 'vmfa-folder-exporter/v1/exports',
			});
			setExports(result);
		} catch {
			// Silently handle errors.
		} finally {
			setIsLoading(false);
		}
	};

	useEffect(() => {
		fetchExports();
	}, [refreshKey]);

	const handleDownload = (jobId) => {
		const restUrl =
			window.vmfaFolderExporter?.restUrl ||
			'/wp-json/vmfa-folder-exporter/v1/';
		const nonce = window.vmfaFolderExporter?.nonce || '';
		window.location.href = `${restUrl}exports/${jobId}/download?_wpnonce=${nonce}`;
	};

	const handleDelete = async (jobId) => {
		try {
			await apiFetch({
				path: `vmfa-folder-exporter/v1/exports/${jobId}`,
				method: 'DELETE',
			});
			setExports((prev) => prev.filter((e) => e.job_id !== jobId));
		} catch {
			// Silently handle errors.
		}
	};

	if (isLoading) {
		return (
			<div className="vmfa-folder-exporter__history">
				<h2>{__('Recent Exports', 'vmfa-folder-exporter')}</h2>
				<Spinner />
			</div>
		);
	}

	if (exports.length === 0) {
		return (
			<div className="vmfa-folder-exporter__history">
				<h2>{__('Recent Exports', 'vmfa-folder-exporter')}</h2>
				<p className="vmfa-folder-exporter__history-empty">
					{__(
						'No exports yet. Select a folder above and click "Export as ZIP" to get started.',
						'vmfa-folder-exporter'
					)}
				</p>
			</div>
		);
	}

	return (
		<div className="vmfa-folder-exporter__history">
			<h2>{__('Recent Exports', 'vmfa-folder-exporter')}</h2>
			<table className="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>{__('File', 'vmfa-folder-exporter')}</th>
						<th>{__('Status', 'vmfa-folder-exporter')}</th>
						<th>{__('Size', 'vmfa-folder-exporter')}</th>
						<th>{__('Created', 'vmfa-folder-exporter')}</th>
						<th>{__('Actions', 'vmfa-folder-exporter')}</th>
					</tr>
				</thead>
				<tbody>
					{exports.map((item) => (
						<tr key={item.job_id}>
							<td>{item.file_name || '—'}</td>
							<td>
								<span
									className={`vmfa-folder-exporter__status vmfa-folder-exporter__status--${item.status}`}
								>
									{item.status}
								</span>
							</td>
							<td>{formatBytes(item.file_size)}</td>
							<td>{item.created_at || '—'}</td>
							<td className="vmfa-folder-exporter__history-actions">
								{item.status === 'complete' && (
									<Button
										icon={downloadIcon}
										label={__(
											'Download',
											'vmfa-folder-exporter'
										)}
										onClick={() =>
											handleDownload(item.job_id)
										}
									/>
								)}
								<Button
									icon={trash}
									label={__('Delete', 'vmfa-folder-exporter')}
									isDestructive
									onClick={() => handleDelete(item.job_id)}
								/>
							</td>
						</tr>
					))}
				</tbody>
			</table>
		</div>
	);
}
