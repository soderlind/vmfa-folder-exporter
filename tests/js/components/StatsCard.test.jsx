/**
 * Tests for StatsCard component.
 */

import { render, screen } from '@testing-library/react';
import { StatsCard } from '../../../src/js/components/StatsCard';

describe( 'StatsCard', () => {
	it( 'displays the total number of folders from global data', () => {
		render( <StatsCard /> );

		// The global mock provides 3 folders.
		expect( screen.getByText( '3' ) ).toBeInTheDocument();
		expect( screen.getByText( /available folders/i ) ).toBeInTheDocument();
	} );

	it( 'displays 0 when no folders are available', () => {
		const original = window.vmfaFolderExporter.folders;
		window.vmfaFolderExporter.folders = [];

		render( <StatsCard /> );

		expect( screen.getByText( '0' ) ).toBeInTheDocument();

		// Restore.
		window.vmfaFolderExporter.folders = original;
	} );
} );
