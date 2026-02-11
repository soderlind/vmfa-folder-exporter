/**
 * Tests for FolderPicker component.
 */

import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { FolderPicker } from '../../../src/js/components/FolderPicker';

describe( 'FolderPicker', () => {
	it( 'renders the default placeholder option', () => {
		render( <FolderPicker selectedFolder={ null } onChange={ vi.fn() } /> );

		expect(
			screen.getByRole( 'combobox', { name: /folder/i } )
		).toBeInTheDocument();
		expect( screen.getByText( /select a folder/i ) ).toBeInTheDocument();
	} );

	it( 'renders hierarchical folder options from global data', () => {
		render( <FolderPicker selectedFolder={ null } onChange={ vi.fn() } /> );

		// The global has Photos (5), Summer (3), Documents (2).
		expect( screen.getByText( /Photos \(5\)/ ) ).toBeInTheDocument();
		expect( screen.getByText( /Summer \(3\)/ ) ).toBeInTheDocument();
		expect( screen.getByText( /Documents \(2\)/ ) ).toBeInTheDocument();
	} );

	it( 'calls onChange with the selected folder ID', async () => {
		const user = userEvent.setup();
		const onChange = vi.fn();

		render( <FolderPicker selectedFolder={ null } onChange={ onChange } /> );

		const select = screen.getByRole( 'combobox', { name: /folder/i } );
		await user.selectOptions( select, '3' );

		expect( onChange ).toHaveBeenCalledWith( 3 );
	} );

	it( 'calls onChange with null when placeholder is selected', async () => {
		const user = userEvent.setup();
		const onChange = vi.fn();

		render( <FolderPicker selectedFolder={ 1 } onChange={ onChange } /> );

		const select = screen.getByRole( 'combobox', { name: /folder/i } );
		await user.selectOptions( select, '' );

		expect( onChange ).toHaveBeenCalledWith( null );
	} );
} );
