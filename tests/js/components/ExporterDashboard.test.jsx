/**
 * Tests for ExporterDashboard component.
 */

import { render, screen } from '@testing-library/react';
import { ExporterDashboard } from '../../../src/js/components/ExporterDashboard';

// apiFetch is used by child ExportHistory for initial load.
import apiFetch from '@wordpress/api-fetch';

describe( 'ExporterDashboard', () => {
	beforeEach( () => {
		apiFetch.mockResolvedValue( [] ); // ExportHistory fetch returns empty.
	} );

	afterEach( () => {
		vi.clearAllMocks();
	} );

	it( 'renders the main heading', () => {
		render( <ExporterDashboard /> );

		expect( screen.getByText( /export folder/i ) ).toBeInTheDocument();
	} );

	it( 'renders StatsCard with folder count', () => {
		render( <ExporterDashboard /> );

		expect( screen.getByText( '3' ) ).toBeInTheDocument();
		expect( screen.getByText( /available folders/i ) ).toBeInTheDocument();
	} );

	it( 'renders FolderPicker', () => {
		render( <ExporterDashboard /> );

		expect(
			screen.getByRole( 'combobox', { name: /folder/i } )
		).toBeInTheDocument();
	} );

	it( 'renders export options checkboxes', () => {
		render( <ExporterDashboard /> );

		expect( screen.getByText( /include subfolders/i ) ).toBeInTheDocument();
		expect( screen.getByText( /include csv manifest/i ) ).toBeInTheDocument();
	} );

	it( 'renders the export button in disabled state', () => {
		render( <ExporterDashboard /> );

		const button = screen.getByRole( 'button', { name: /export as zip/i } );
		expect( button ).toBeDisabled();
	} );
} );
