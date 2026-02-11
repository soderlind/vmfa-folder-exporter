<?php
/**
 * Tests for ExportService.
 *
 * @package VmfaFolderExporter\Tests
 */

declare(strict_types=1);

use VmfaFolderExporter\Services\ExportService;
use VmfaFolderExporter\Services\ManifestService;
use Brain\Monkey\Functions;

beforeEach( function () {
	$this->manifest_service = Mockery::mock( ManifestService::class);
	$this->service          = new ExportService( $this->manifest_service );
} );

it( 'starts an export and returns a job ID', function () {
	Functions\when( 'wp_generate_uuid4' )->justReturn( 'test-uuid-1234' );
	Functions\when( 'update_option' )->justReturn( true );
	Functions\when( 'current_time' )->justReturn( '2025-06-15 10:30:00' );
	Functions\when( 'as_enqueue_async_action' )->justReturn( 1 );

	$job_id = $this->service->start_export( 42, true, true, 1 );

	expect( $job_id )->toBe( 'test-uuid-1234' );
} );

it( 'returns false for non-existent export', function () {
	Functions\when( 'get_option' )->justReturn( false );

	$result = $this->service->get_export( 'non-existent' );

	expect( $result )->toBeFalse();
} );

it( 'returns export metadata when exists', function () {
	$data = array(
		'job_id'    => 'test-uuid',
		'folder_id' => 42,
		'status'    => 'complete',
	);

	Functions\when( 'get_option' )->justReturn( $data );

	$result = $this->service->get_export( 'test-uuid' );

	expect( $result )->toBeArray();
	expect( $result[ 'job_id' ] )->toBe( 'test-uuid' );
	expect( $result[ 'status' ] )->toBe( 'complete' );
} );

it( 'builds folder path map from term IDs', function () {
	Functions\when( 'get_ancestors' )->alias( function ( int $term_id ) {
		if ( 20 === $term_id ) {
			return array( 10 );  // Parent is 10.
		}
		return array();
	} );

	Functions\when( 'get_term' )->alias( function ( int $term_id ) {
		return match ( $term_id ) {
			10      => (object) array( 'term_id'      => 10, 'name'      => 'Photos' ),
			20      => (object) array( 'term_id'      => 20, 'name'      => 'Summer' ),
			default => null,
		};
	} );

	Functions\when( 'sanitize_file_name' )->returnArg();
	Functions\when( 'is_wp_error' )->justReturn( false );

	$map = $this->service->build_folder_path_map( array( 10, 20 ) );

	expect( $map )->toBeArray()->toHaveCount( 2 );
	expect( $map[ 10 ] )->toBe( 'Photos' );
	expect( $map[ 20 ] )->toBe( 'Photos/Summer' );
} );

it( 'gets attachment folder ID', function () {
	Functions\when( 'wp_get_object_terms' )->justReturn( array( 42 ) );
	Functions\when( 'is_wp_error' )->justReturn( false );

	$result = $this->service->get_attachment_folder( 100 );

	expect( $result )->toBe( 42 );
} );

it( 'returns null when attachment has no folder', function () {
	Functions\when( 'wp_get_object_terms' )->justReturn( array() );
	Functions\when( 'is_wp_error' )->justReturn( false );

	$result = $this->service->get_attachment_folder( 100 );

	expect( $result )->toBeNull();
} );

it( 'deletes export and its file', function () {
	$export = array(
		'job_id'    => 'test-uuid',
		'file_path' => '/tmp/test-export.zip',
	);

	Functions\when( 'get_option' )->justReturn( $export );
	Functions\when( 'delete_option' )->justReturn( true );
	Functions\when( 'wp_delete_file' )->justReturn();

	$result = $this->service->delete_export( 'test-uuid' );

	expect( $result )->toBeTrue();
} );

it( 'returns false when deleting non-existent export', function () {
	Functions\when( 'get_option' )->justReturn( false );

	$result = $this->service->delete_export( 'non-existent' );

	expect( $result )->toBeFalse();
} );
