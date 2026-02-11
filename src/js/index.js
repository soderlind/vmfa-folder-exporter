/**
 * VMFA Folder Exporter — React entry point.
 *
 * @package
 */

import { createRoot, StrictMode } from '@wordpress/element';
import { ExporterDashboard } from './components/ExporterDashboard';

import '../styles/admin.scss';

const root = document.getElementById('vmfa-folder-exporter-app');

if (root) {
	createRoot(root).render(
		<StrictMode>
			<ExporterDashboard />
		</StrictMode>
	);
}
