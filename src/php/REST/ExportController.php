<?php
/**
 * Export REST API controller.
 *
 * @package VmfaFolderExporter
 */

declare(strict_types=1);

namespace VmfaFolderExporter\REST;

defined( 'ABSPATH' ) || exit;

use VmfaFolderExporter\Services\ExportService;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * REST API controller for folder export operations.
 */
class ExportController extends WP_REST_Controller {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'vmfa-folder-exporter/v1';

	/**
	 * Export service.
	 *
	 * @var ExportService
	 */
	private ExportService $export_service;

	/**
	 * Constructor.
	 *
	 * @param ExportService $export_service Export service instance.
	 */
	public function __construct( ExportService $export_service ) {
		$this->export_service = $export_service;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// POST /exports — Start a new export.
		register_rest_route(
			$this->namespace,
			'/exports',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_export' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'folder_id'        => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => function ( $value ) {
								return is_numeric( $value ) && (int) $value > 0;
							},
						),
						'include_children'  => array(
							'type'              => 'boolean',
							'default'           => true,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
						'include_manifest'  => array(
							'type'              => 'boolean',
							'default'           => true,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_exports' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// GET /exports/{id} — Get export status.
		register_rest_route(
			$this->namespace,
			'/exports/(?P<id>[a-f0-9-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_export' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_export' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		// GET /exports/{id}/download — Download the ZIP file.
		register_rest_route(
			$this->namespace,
			'/exports/(?P<id>[a-f0-9-]+)/download',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'download_export' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'id' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Check if the current user has permission.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission(): bool|WP_Error {
		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to export folders.', 'vmfa-folder-exporter' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Start a new export.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_export( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$folder_id        = (int) $request->get_param( 'folder_id' );
		$include_children = (bool) $request->get_param( 'include_children' );
		$include_manifest = (bool) $request->get_param( 'include_manifest' );

		// Verify the folder exists.
		$folder = get_term( $folder_id, 'vmfo_folder' );
		if ( ! $folder || is_wp_error( $folder ) ) {
			return new WP_Error(
				'invalid_folder',
				__( 'The specified folder does not exist.', 'vmfa-folder-exporter' ),
				array( 'status' => 404 )
			);
		}

		$job_id = $this->export_service->start_export(
			$folder_id,
			$include_children,
			$include_manifest,
			get_current_user_id()
		);

		$export = $this->export_service->get_export( $job_id );

		return new WP_REST_Response( $export, 201 );
	}

	/**
	 * Get export status.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_export( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$job_id = $request->get_param( 'id' );
		$export = $this->export_service->get_export( $job_id );

		if ( ! $export ) {
			return new WP_Error(
				'not_found',
				__( 'Export not found.', 'vmfa-folder-exporter' ),
				array( 'status' => 404 )
			);
		}

		// Verify ownership.
		if ( (int) $export['user_id'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view this export.', 'vmfa-folder-exporter' ),
				array( 'status' => 403 )
			);
		}

		// Don't expose the server file path in the response.
		$response = $export;
		unset( $response['file_path'] );

		return new WP_REST_Response( $response );
	}

	/**
	 * List recent exports.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function list_exports( WP_REST_Request $request ): WP_REST_Response {
		$exports = $this->export_service->get_recent_exports();

		$user_id = get_current_user_id();
		$is_admin = current_user_can( 'manage_options' );

		// Filter to user's own exports unless admin.
		$filtered = array_filter( $exports, function ( $export ) use ( $user_id, $is_admin ) {
			return $is_admin || (int) ( $export['user_id'] ?? 0 ) === $user_id;
		} );

		// Remove file_path from each export.
		$safe_exports = array_map( function ( $export ) {
			unset( $export['file_path'] );
			return $export;
		}, array_values( $filtered ) );

		return new WP_REST_Response( $safe_exports );
	}

	/**
	 * Delete an export.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_export( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$job_id = $request->get_param( 'id' );
		$export = $this->export_service->get_export( $job_id );

		if ( ! $export ) {
			return new WP_Error(
				'not_found',
				__( 'Export not found.', 'vmfa-folder-exporter' ),
				array( 'status' => 404 )
			);
		}

		// Verify ownership.
		if ( (int) $export['user_id'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to delete this export.', 'vmfa-folder-exporter' ),
				array( 'status' => 403 )
			);
		}

		$this->export_service->delete_export( $job_id );

		return new WP_REST_Response( array( 'deleted' => true ) );
	}

	/**
	 * Download an export ZIP file.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error|void
	 */
	public function download_export( WP_REST_Request $request ) {
		$job_id = $request->get_param( 'id' );
		$export = $this->export_service->get_export( $job_id );

		if ( ! $export ) {
			return new WP_Error(
				'not_found',
				__( 'Export not found.', 'vmfa-folder-exporter' ),
				array( 'status' => 404 )
			);
		}

		// Verify ownership.
		if ( (int) $export['user_id'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to download this export.', 'vmfa-folder-exporter' ),
				array( 'status' => 403 )
			);
		}

		if ( 'complete' !== $export['status'] ) {
			return new WP_Error(
				'not_ready',
				__( 'Export is not yet complete.', 'vmfa-folder-exporter' ),
				array( 'status' => 409 )
			);
		}

		$file_path = $export['file_path'] ?? '';
		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			return new WP_Error(
				'file_missing',
				__( 'Export file no longer exists. It may have expired.', 'vmfa-folder-exporter' ),
				array( 'status' => 410 )
			);
		}

		$file_name = $export['file_name'] ?? 'export.zip';

		// Stream the file.
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Length: ' . filesize( $file_path ) );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $file_path );
		exit;
	}
}
