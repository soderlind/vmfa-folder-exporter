/**
 * Vitest global setup.
 */

import '@testing-library/jest-dom/vitest';

// Provide a minimal window.vmfaFolderExporter global.
window.vmfaFolderExporter = {
	restUrl: '/wp-json/vmfa-folder-exporter/v1/',
	nonce: 'test-nonce',
	folders: [
		{ id: 1, name: 'Photos', slug: 'photos', parent: 0, count: 5 },
		{ id: 2, name: 'Summer', slug: 'summer', parent: 1, count: 3 },
		{ id: 3, name: 'Documents', slug: 'documents', parent: 0, count: 2 },
	],
};
