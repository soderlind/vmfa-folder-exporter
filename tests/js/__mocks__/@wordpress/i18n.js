/**
 * Mock for @wordpress/i18n — pass-through.
 */

export const __ = ( text ) => text;
export const _x = ( text ) => text;
export const _n = ( single, plural, count ) =>
	count === 1 ? single : plural;
export const sprintf = ( fmt, ...args ) => {
	// Handle positional placeholders like %1$d, %2$s first.
	let result = fmt.replace( /%(\d+)\$[sd]/g, ( _, pos ) => args[ pos - 1 ] );
	// Then handle simple %s, %d.
	let i = 0;
	result = result.replace( /%[sd]/g, () => args[ i++ ] );
	return result;
};
