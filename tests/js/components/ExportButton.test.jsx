/**
 * Tests for ExportButton component.
 */

import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';
import { ExportButton } from '../../../src/js/components/ExportButton';

describe( 'ExportButton', () => {
	afterEach( () => {
		vi.clearAllMocks();
	} );

	it( 'renders the button in disabled state when no folder is selected', () => {
		render(
			<ExportButton
				folderId={ null }
				includeChildren={ true }
				includeManifest={ true }
				onExportStarted={ vi.fn() }
			/>
		);

		const button = screen.getByRole( 'button', { name: /export as zip/i } );
		expect( button ).toBeDisabled();
	} );

	it( 'renders the button enabled when a folder is selected', () => {
		render(
			<ExportButton
				folderId={ 1 }
				includeChildren={ true }
				includeManifest={ true }
				onExportStarted={ vi.fn() }
			/>
		);

		const button = screen.getByRole( 'button', { name: /export as zip/i } );
		expect( button ).not.toBeDisabled();
	} );

	it( 'calls apiFetch and onExportStarted on success', async () => {
		const user = userEvent.setup();
		const onExportStarted = vi.fn();
		const mockResult = { job_id: 'abc-123', status: 'pending' };
		apiFetch.mockResolvedValue( mockResult );

		render(
			<ExportButton
				folderId={ 1 }
				includeChildren={ true }
				includeManifest={ false }
				onExportStarted={ onExportStarted }
			/>
		);

		const button = screen.getByRole( 'button', { name: /export as zip/i } );
		await user.click( button );

		await waitFor( () => {
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: 'vmfa-folder-exporter/v1/exports',
				method: 'POST',
				data: {
					folder_id: 1,
					include_children: true,
					include_manifest: false,
				},
			} );
		} );

		expect( onExportStarted ).toHaveBeenCalledWith( mockResult );
	} );

	it( 'shows an error notice on API failure', async () => {
		const user = userEvent.setup();
		apiFetch.mockRejectedValue( new Error( 'Server error' ) );

		render(
			<ExportButton
				folderId={ 1 }
				includeChildren={ true }
				includeManifest={ true }
				onExportStarted={ vi.fn() }
			/>
		);

		const button = screen.getByRole( 'button', { name: /export as zip/i } );
		await user.click( button );

		await waitFor( () => {
			expect( screen.getByText( 'Server error' ) ).toBeInTheDocument();
		} );
	} );
} );
