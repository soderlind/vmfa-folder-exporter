/**
 * Tests for ExportProgress component.
 */

import { render, screen, waitFor, act } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';
import { ExportProgress } from '../../../src/js/components/ExportProgress';

describe( 'ExportProgress', () => {
	beforeEach( () => {
		vi.useFakeTimers();
	} );

	afterEach( () => {
		vi.useRealTimers();
		vi.clearAllMocks();
	} );

	it( 'renders a progress bar when status is processing', () => {
		render(
			<ExportProgress
				exportData={ { job_id: 'abc', status: 'processing', progress: 5, total: 20 } }
				onComplete={ vi.fn() }
			/>
		);

		expect( screen.getByText( /processing 5 of 20/i ) ).toBeInTheDocument();
	} );

	it( 'renders a preparing message when total is 0', () => {
		render(
			<ExportProgress
				exportData={ { job_id: 'abc', status: 'pending', progress: 0, total: 0 } }
				onComplete={ vi.fn() }
			/>
		);

		expect( screen.getByText( /preparing export/i ) ).toBeInTheDocument();
	} );

	it( 'shows success state when status is complete', () => {
		render(
			<ExportProgress
				exportData={ { job_id: 'abc', status: 'complete', progress: 10, total: 10 } }
				onComplete={ vi.fn() }
			/>
		);

		expect( screen.getByText( /export complete/i ) ).toBeInTheDocument();
		expect( screen.getByText( /download zip/i ) ).toBeInTheDocument();
	} );

	it( 'shows error state when status is failed', () => {
		render(
			<ExportProgress
				exportData={ {
					job_id: 'abc',
					status: 'failed',
					error: 'Disk full',
					progress: 0,
					total: 0,
				} }
				onComplete={ vi.fn() }
			/>
		);

		expect( screen.getByText( 'Disk full' ) ).toBeInTheDocument();
		expect( screen.getByText( /dismiss/i ) ).toBeInTheDocument();
	} );

	it( 'polls the API and updates status', async () => {
		vi.useRealTimers();

		apiFetch.mockResolvedValue( {
			job_id: 'abc',
			status: 'complete',
			progress: 10,
			total: 10,
		} );

		render(
			<ExportProgress
				exportData={ { job_id: 'abc', status: 'processing', progress: 3, total: 10 } }
				onComplete={ vi.fn() }
			/>
		);

		await waitFor( () => {
			expect( screen.getByText( /export complete/i ) ).toBeInTheDocument();
		}, { timeout: 5000 } );
	} );
} );
