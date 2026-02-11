<?php
/**
 * PHPUnit bootstrap file for Brain Monkey integration.
 *
 * @package VmfaFolderExporter\Tests
 */

declare(strict_types=1);

// Composer autoloader.
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// Define WordPress constants used by the plugin.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

// Minimal WP_Error stub for unit tests.
if ( ! class_exists( 'WP_Error' ) ) {
	// phpcs:ignore
	class WP_Error {
		public $errors = array();
		public $error_data = array();
		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( $code ) {
				$this->errors[ $code ][] = $message;
				if ( $data ) {
					$this->error_data[ $code ] = $data;
				}
			}
		}
		public function get_error_code() {
			$codes = array_keys( $this->errors );
			return $codes[0] ?? '';
		}
		public function get_error_message( $code = '' ) {
			if ( ! $code ) {
				$code = $this->get_error_code();
			}
			return $this->errors[ $code ][0] ?? '';
		}
	}
}

// Minimal WP_REST_Controller stub.
if ( ! class_exists( 'WP_REST_Controller' ) ) {
	// phpcs:ignore
	class WP_REST_Controller {
		protected $namespace;
		protected $rest_base;
	}
}

// Minimal WP_REST_Server stub.
if ( ! class_exists( 'WP_REST_Server' ) ) {
	// phpcs:ignore
	class WP_REST_Server {
		const READABLE  = 'GET';
		const CREATABLE = 'POST';
		const EDITABLE  = 'POST, PUT, PATCH';
		const DELETABLE = 'DELETE';
	}
}

// Minimal WP_REST_Request stub.
if ( ! class_exists( 'WP_REST_Request' ) ) {
	// phpcs:ignore
	class WP_REST_Request {
		private $params = array();
		public function get_param( $key ) {
			return $this->params[ $key ] ?? null;
		}
		public function get_params() {
			return $this->params;
		}
		public function set_param( $key, $value ) {
			$this->params[ $key ] = $value;
		}
	}
}

// Minimal WP_REST_Response stub.
if ( ! class_exists( 'WP_REST_Response' ) ) {
	// phpcs:ignore
	class WP_REST_Response {
		public $data;
		public $status;
		public function __construct( $data = null, $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}
		public function get_data() {
			return $this->data;
		}
	}
}

// Minimal WP_Post stub.
if ( ! class_exists( 'WP_Post' ) ) {
	// phpcs:ignore
	class WP_Post {
		public $ID = 0;
		public $post_title = '';
		public $post_excerpt = '';
		public $post_content = '';
		public $post_mime_type = '';
		public $post_date = '';
		public $post_status = 'inherit';

		public function __construct( $data = array() ) {
			foreach ( $data as $key => $value ) {
				if ( property_exists( $this, $key ) ) {
					$this->$key = $value;
				}
			}
		}
	}
}

if ( ! defined( 'VMFA_FOLDER_EXPORTER_VERSION' ) ) {
	define( 'VMFA_FOLDER_EXPORTER_VERSION', '1.0.0-test' );
}

if ( ! defined( 'VMFA_FOLDER_EXPORTER_FILE' ) ) {
	define( 'VMFA_FOLDER_EXPORTER_FILE', dirname( __DIR__, 2 ) . '/vmfa-folder-exporter.php' );
}

if ( ! defined( 'VMFA_FOLDER_EXPORTER_PATH' ) ) {
	define( 'VMFA_FOLDER_EXPORTER_PATH', dirname( __DIR__, 2 ) . '/' );
}

if ( ! defined( 'VMFA_FOLDER_EXPORTER_URL' ) ) {
	define( 'VMFA_FOLDER_EXPORTER_URL', 'https://example.com/wp-content/plugins/vmfa-folder-exporter/' );
}

if ( ! defined( 'VMFA_FOLDER_EXPORTER_BASENAME' ) ) {
	define( 'VMFA_FOLDER_EXPORTER_BASENAME', 'vmfa-folder-exporter/vmfa-folder-exporter.php' );
}
