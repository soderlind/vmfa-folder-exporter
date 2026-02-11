/**
 * Main dashboard component for folder exporter.
 *
 * @package
 */

import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { FolderPicker } from './FolderPicker';
import { ExportOptions } from './ExportOptions';
import { ExportButton } from './ExportButton';
import { ExportProgress } from './ExportProgress';
import { ExportHistory } from './ExportHistory';
import { StatsCard } from './StatsCard';

/**
 * ExporterDashboard component.
 *
 * @return {import('react').ReactElement} The dashboard.
 */
export function ExporterDashboard() {
	const [selectedFolder, setSelectedFolder] = useState(null);
	const [includeChildren, setIncludeChildren] = useState(true);
	const [includeManifest, setIncludeManifest] = useState(true);
	const [activeExport, setActiveExport] = useState(null);
	const [refreshKey, setRefreshKey] = useState(0);

	const handleExportStarted = useCallback((exportData) => {
		setActiveExport(exportData);
	}, []);

	const handleExportComplete = useCallback(() => {
		setActiveExport(null);
		setRefreshKey((prev) => prev + 1);
	}, []);

	return (
		<div className="vmfa-folder-exporter">
			<StatsCard />

			<div className="vmfa-folder-exporter__export-panel">
				<h2>{__('Export Folder', 'vmfa-folder-exporter')}</h2>

				<FolderPicker
					selectedFolder={selectedFolder}
					onChange={setSelectedFolder}
				/>

				<ExportOptions
					includeChildren={includeChildren}
					onChangeChildren={setIncludeChildren}
					includeManifest={includeManifest}
					onChangeManifest={setIncludeManifest}
				/>

				{activeExport ? (
					<ExportProgress
						exportData={activeExport}
						onComplete={handleExportComplete}
					/>
				) : (
					<ExportButton
						folderId={selectedFolder}
						includeChildren={includeChildren}
						includeManifest={includeManifest}
						onExportStarted={handleExportStarted}
					/>
				)}
			</div>

			<ExportHistory refreshKey={refreshKey} />
		</div>
	);
}
