<?php
/**
 * Tests for ManifestService.
 *
 * @package VmfaFolderExporter\Tests
 */

declare(strict_types=1);

use VmfaFolderExporter\Services\ManifestService;
use Brain\Monkey\Functions;

beforeEach( function () {
	$this->service = new ManifestService();
} );

it( 'returns expected column headers', function () {
	$columns = $this->service->get_columns();

	expect( $columns )->toBeArray()
		->toContain( 'ID' )
		->toContain( 'filename' )
		->toContain( 'url' )
		->toContain( 'alt_text' )
		->toContain( 'caption' )
		->toContain( 'description' )
		->toContain( 'mime_type' )
		->toContain( 'file_size_bytes' )
		->toContain( 'width' )
		->toContain( 'height' )
		->toContain( 'date_uploaded' )
		->toContain( 'folder_path' );
} );

it( 'generates CSV with UTF-8 BOM and headers', function () {
	Functions\when( 'apply_filters' )->returnArg( 2 );

	$csv = $this->service->generate( [], [] );

	// Should contain BOM.
	expect( substr( $csv, 0, 3 ) )->toBe( "\xEF\xBB\xBF" );

	// Should contain header row.
	expect( $csv )->toContain( 'ID' )
		->toContain( 'filename' )
		->toContain( 'folder_path' );
} );

it( 'builds a row for an attachment', function () {
	$attachment                 = new WP_Post();
	$attachment->ID             = 42;
	$attachment->post_excerpt   = 'A caption';
	$attachment->post_content   = 'A description';
	$attachment->post_mime_type = 'image/jpeg';
	$attachment->post_date      = '2025-06-15 10:30:00';

	Functions\when( 'wp_get_attachment_metadata' )->justReturn( array(
		'width'  => 1920,
		'height' => 1080,
	) );
	Functions\when( 'get_attached_file' )->justReturn( '/tmp/test-image.jpg' );
	Functions\when( 'wp_basename' )->justReturn( 'test-image.jpg' );
	Functions\when( 'wp_get_attachment_url' )->justReturn( 'https://example.com/test-image.jpg' );
	Functions\when( 'get_post_meta' )->justReturn( 'Alt text value' );
	Functions\when( 'wp_get_object_terms' )->justReturn( array( 10 ) );

	$folder_paths = array( 10 => 'Photos/Summer' );

	$row = $this->service->build_row( $attachment, $folder_paths );

	expect( $row )->toBeArray()->toHaveCount( 12 );
	expect( $row[ 0 ] )->toBe( 42 );           // ID.
	expect( $row[ 1 ] )->toBe( 'test-image.jpg' ); // filename.
	expect( $row[ 2 ] )->toBe( 'https://example.com/test-image.jpg' ); // url.
	expect( $row[ 3 ] )->toBe( 'Alt text value' ); // alt_text.
	expect( $row[ 4 ] )->toBe( 'A caption' );  // caption.
	expect( $row[ 5 ] )->toBe( 'A description' ); // description.
	expect( $row[ 6 ] )->toBe( 'image/jpeg' ); // mime_type.
	expect( $row[ 8 ] )->toBe( 1920 );          // width.
	expect( $row[ 9 ] )->toBe( 1080 );          // height.
	expect( $row[ 11 ] )->toBe( 'Photos/Summer' ); // folder_path.
} );
