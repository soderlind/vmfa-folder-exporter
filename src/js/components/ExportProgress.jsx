/**
 * Export progress component — polls for export completion.
 *
 * @package
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { Spinner, Notice, Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * ExportProgress component.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.exportData The current export metadata.
 * @param {Function} props.onComplete Callback fired when export finishes.
 * @return {import('react').ReactElement} The progress display.
 */
export function ExportProgress({ exportData, onComplete }) {
	const [status, setStatus] = useState(exportData.status || 'pending');
	const [progress, setProgress] = useState(exportData.progress || 0);
	const [total, setTotal] = useState(exportData.total || 0);
	const [error, setError] = useState(exportData.error || '');
	const [jobId] = useState(exportData.job_id);

	const poll = useCallback(async () => {
		try {
			const result = await apiFetch({
				path: `vmfa-folder-exporter/v1/exports/${jobId}`,
			});

			setStatus(result.status);
			setProgress(result.progress || 0);
			setTotal(result.total || 0);
			setError(result.error || '');

			if (result.status === 'complete' || result.status === 'failed') {
				return true; // Stop polling.
			}
		} catch {
			// Continue polling on transient errors.
		}

		return false;
	}, [jobId]);

	useEffect(() => {
		if (status === 'complete' || status === 'failed') {
			return;
		}

		const interval = setInterval(async () => {
			const done = await poll();
			if (done) {
				clearInterval(interval);
			}
		}, 2000);

		return () => clearInterval(interval);
	}, [poll, status]);

	const handleDownload = () => {
		const restUrl =
			window.vmfaFolderExporter?.restUrl ||
			'/wp-json/vmfa-folder-exporter/v1/';
		const nonce = window.vmfaFolderExporter?.nonce || '';
		window.location.href = `${restUrl}exports/${jobId}/download?_wpnonce=${nonce}`;
	};

	if (status === 'failed') {
		return (
			<div className="vmfa-folder-exporter__progress">
				<Notice status="error" isDismissible={false}>
					{error || __('Export failed.', 'vmfa-folder-exporter')}
				</Notice>
				<Button variant="secondary" onClick={onComplete}>
					{__('Dismiss', 'vmfa-folder-exporter')}
				</Button>
			</div>
		);
	}

	if (status === 'complete') {
		return (
			<div className="vmfa-folder-exporter__progress vmfa-folder-exporter__progress--complete">
				<Notice status="success" isDismissible={false}>
					{__('Export complete!', 'vmfa-folder-exporter')}
				</Notice>
				<div className="vmfa-folder-exporter__progress-actions">
					<Button variant="primary" onClick={handleDownload}>
						{__('Download ZIP', 'vmfa-folder-exporter')}
					</Button>
					<Button variant="secondary" onClick={onComplete}>
						{__('Done', 'vmfa-folder-exporter')}
					</Button>
				</div>
			</div>
		);
	}

	const percentage = total > 0 ? Math.round((progress / total) * 100) : 0;

	return (
		<div className="vmfa-folder-exporter__progress">
			<div className="vmfa-folder-exporter__progress-bar">
				<div
					className="vmfa-folder-exporter__progress-fill"
					style={{ width: `${percentage}%` }}
				/>
			</div>
			<div className="vmfa-folder-exporter__progress-info">
				<Spinner />
				<span>
					{total > 0
						? sprintf(
								/* translators: 1: processed count, 2: total count */
								__(
									'Processing %1$d of %2$d files…',
									'vmfa-folder-exporter'
								),
								progress,
								total
							)
						: __('Preparing export…', 'vmfa-folder-exporter')}
				</span>
			</div>
		</div>
	);
}
