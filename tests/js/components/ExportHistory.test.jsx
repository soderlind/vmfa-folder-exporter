/**
 * Tests for ExportHistory component.
 */

import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';
import { ExportHistory } from '../../../src/js/components/ExportHistory';

describe( 'ExportHistory', () => {
	afterEach( () => {
		vi.clearAllMocks();
	} );

	it( 'shows spinner while loading', () => {
		apiFetch.mockReturnValue( new Promise( () => {} ) ); // Never resolves.
		render( <ExportHistory refreshKey={ 0 } /> );

		expect( screen.getByTestId( 'spinner' ) ).toBeInTheDocument();
	} );

	it( 'shows empty message when no exports exist', async () => {
		apiFetch.mockResolvedValue( [] );
		render( <ExportHistory refreshKey={ 0 } /> );

		await waitFor( () => {
			expect( screen.getByText( /no exports yet/i ) ).toBeInTheDocument();
		} );
	} );

	it( 'renders a table of exports', async () => {
		apiFetch.mockResolvedValue( [
			{
				job_id: 'abc-123',
				file_name: 'photos.zip',
				status: 'complete',
				file_size: 1048576,
				created_at: '2025-01-15 10:30',
			},
		] );

		render( <ExportHistory refreshKey={ 0 } /> );

		await waitFor( () => {
			expect( screen.getByText( 'photos.zip' ) ).toBeInTheDocument();
		} );

		expect( screen.getByText( 'complete' ) ).toBeInTheDocument();
		expect( screen.getByText( '1.0 MB' ) ).toBeInTheDocument();
		expect( screen.getByText( /download/i ) ).toBeInTheDocument();
		expect( screen.getByText( /delete/i ) ).toBeInTheDocument();
	} );

	it( 'removes an export from the list on delete', async () => {
		const user = userEvent.setup();
		apiFetch
			.mockResolvedValueOnce( [
				{
					job_id: 'abc-123',
					file_name: 'photos.zip',
					status: 'complete',
					file_size: 1024,
					created_at: '2025-01-15',
				},
			] )
			.mockResolvedValueOnce( {} ); // DELETE response.

		render( <ExportHistory refreshKey={ 0 } /> );

		await waitFor( () => {
			expect( screen.getByText( 'photos.zip' ) ).toBeInTheDocument();
		} );

		const deleteButton = screen.getByText( /delete/i );
		await user.click( deleteButton );

		await waitFor( () => {
			expect( screen.queryByText( 'photos.zip' ) ).not.toBeInTheDocument();
		} );
	} );

	it( 're-fetches when refreshKey changes', async () => {
		apiFetch.mockResolvedValue( [] );

		const { rerender } = render( <ExportHistory refreshKey={ 0 } /> );

		await waitFor( () => {
			expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		} );

		rerender( <ExportHistory refreshKey={ 1 } /> );

		await waitFor( () => {
			expect( apiFetch ).toHaveBeenCalledTimes( 2 );
		} );
	} );
} );
