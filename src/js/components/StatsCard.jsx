/**
 * Stats card component — summary of export capabilities.
 *
 * @package
 */

import { __ } from '@wordpress/i18n';

/**
 * StatsCard component.
 *
 * @return {import('react').ReactElement} The stats card.
 */
export function StatsCard() {
	const folders = window.vmfaFolderExporter?.folders || [];
	const totalFolders = folders.length;

	return (
		<div className="vmfa-folder-exporter__stats">
			<div className="vmfa-folder-exporter__stat-card">
				<span className="vmfa-folder-exporter__stat-value">
					{totalFolders}
				</span>
				<span className="vmfa-folder-exporter__stat-label">
					{__('Available Folders', 'vmfa-folder-exporter')}
				</span>
			</div>
		</div>
	);
}
