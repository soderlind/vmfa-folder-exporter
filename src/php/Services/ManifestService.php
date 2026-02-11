<?php
/**
 * Manifest service — generates CSV manifests of media metadata.
 *
 * @package VmfaFolderExporter
 */

declare(strict_types=1);

namespace VmfaFolderExporter\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Generates CSV manifests for exported folders.
 */
class ManifestService {

	/**
	 * CSV column headers.
	 *
	 * @var array<string>
	 */
	private const COLUMNS = array(
		'ID',
		'filename',
		'url',
		'alt_text',
		'caption',
		'description',
		'mime_type',
		'file_size_bytes',
		'width',
		'height',
		'date_uploaded',
		'folder_path',
	);

	/**
	 * Generate a CSV string from attachments.
	 *
	 * @param array            $attachments  Array of WP_Post attachment objects.
	 * @param array<int,string> $folder_paths Map of term_id => folder path string.
	 * @return string The CSV content.
	 */
	public function generate( array $attachments, array $folder_paths ): string {
		$handle = fopen( 'php://temp', 'r+' );

		if ( false === $handle ) {
			return '';
		}

		// Write BOM for Excel compatibility.
		fwrite( $handle, "\xEF\xBB\xBF" );

		/**
		 * Filters the CSV manifest column headers.
		 *
		 * @param array $columns Default column headers.
		 */
		$columns = apply_filters( 'vmfa_export_manifest_columns', self::COLUMNS );

		fputcsv( $handle, $columns );

		foreach ( $attachments as $attachment ) {
			$row = $this->build_row( $attachment, $folder_paths );
			fputcsv( $handle, $row );
		}

		rewind( $handle );
		$csv = stream_get_contents( $handle );
		fclose( $handle );

		return $csv ?: '';
	}

	/**
	 * Build a single CSV row for an attachment.
	 *
	 * @param \WP_Post          $attachment   The attachment post object.
	 * @param array<int,string> $folder_paths Map of term_id => folder path string.
	 * @return array The CSV row values.
	 */
	public function build_row( \WP_Post $attachment, array $folder_paths ): array {
		$metadata  = wp_get_attachment_metadata( $attachment->ID );
		$file_path = get_attached_file( $attachment->ID );
		$file_size = $file_path && file_exists( $file_path ) ? filesize( $file_path ) : 0;

		$width  = 0;
		$height = 0;
		if ( is_array( $metadata ) ) {
			$width  = $metadata[ 'width' ] ?? 0;
			$height = $metadata[ 'height' ] ?? 0;
		}

		// Get the folder path for this attachment.
		$folder_path = '';
		$terms       = wp_get_object_terms( $attachment->ID, 'vmfo_folder', array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$term_id     = (int) $terms[ 0 ];
			$folder_path = $folder_paths[ $term_id ] ?? '';
		}

		return array(
			$attachment->ID,
			wp_basename( get_attached_file( $attachment->ID ) ?: '' ),
			wp_get_attachment_url( $attachment->ID ) ?: '',
			get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ) ?: '',
			$attachment->post_excerpt,
			$attachment->post_content,
			$attachment->post_mime_type,
			$file_size,
			$width,
			$height,
			$attachment->post_date,
			$folder_path,
		);
	}

	/**
	 * Get the CSV column headers.
	 *
	 * @return array<string> Column headers.
	 */
	public function get_columns(): array {
		return self::COLUMNS;
	}
}
