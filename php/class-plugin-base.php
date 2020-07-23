<?php
/**
 * Class Plugin_Base
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Class Plugin_Base.
 */
abstract class Plugin_Base {

	/**
	 * Plugin config.
	 *
	 * @var array
	 */
	public $config = [];

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	public $dir_path;

	/**
	 * Plugin directory URL.
	 *
	 * @var string
	 */
	public $dir_url;

	/**
	 * Plugin main file.
	 *
	 * @var string
	 */
	public $file;

	/**
	 * Directory in plugin containing autoloaded classes.
	 *
	 * @var string
	 */
	protected $autoload_class_dir = 'php';

	/**
	 * Autoload matches cache.
	 *
	 * @var array
	 */
	protected $autoload_matches_cache = [];

	/**
	 * Plugin_Base constructor.
	 */
	public function __construct() {
		$location       = $this->locate_plugin();
		$this->slug     = $location['dir_basename'];
		$this->dir_path = $location['dir_path'];
		$this->dir_url  = $location['dir_url'];
		$this->file     = trailingslashit( basename( $this->dir_path ) ) . 'unsplash.php';

		spl_autoload_register( [ $this, 'autoload' ] );
	}

	/**
	 * Get reflection object for this class.
	 *
	 * @return \ReflectionObject
	 */
	public function get_object_reflection() {
		static $reflection;
		if ( empty( $reflection ) ) {
			// @codeCoverageIgnoreStart
			$reflection = new \ReflectionObject( $this );
			// @codeCoverageIgnoreEnd
		}

		return $reflection;
	}

	/**
	 * Autoload for classes that are in the same namespace as $this.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param string $class Class name.
	 *
	 * @return void
	 */
	public function autoload( $class ) {
		if ( ! isset( $this->autoload_matches_cache[ $class ] ) ) {
			if ( ! preg_match( '/^(?P<namespace>.+)\\\\(?P<class>[^\\\\]+)$/', $class, $matches ) ) {
				$matches = false;
			}

			$this->autoload_matches_cache[ $class ] = $matches;
		} else {
			$matches = $this->autoload_matches_cache[ $class ];
		}

		if ( empty( $matches ) ) {
			return;
		}

		$namespace = $this->get_object_reflection()->getNamespaceName();

		if ( strpos( $matches['namespace'], $namespace ) === false ) {
			return;
		}

		$class_name = $matches['class'];
		$class_path = \trailingslashit( $this->dir_path );

		if ( $this->autoload_class_dir ) {
			$class_path .= \trailingslashit( $this->autoload_class_dir );

			$sub_path = str_replace( $namespace . '\\', '', $matches['namespace'] );
			if ( ! empty( $sub_path ) && 'Unsplash' !== $sub_path ) {
				$class_path .= str_replace( '\\-', '/', strtolower( preg_replace( '/(?<!^)([A-Z])/', '-\\1', $sub_path ) ) . '/' );
			}
		}

		$class_path .= sprintf( 'class-%s.php', strtolower( str_replace( '_', '-', $class_name ) ) );

		if ( is_readable( $class_path ) ) {
			require_once $class_path;
		}
	}

	/**
	 * Version of plugin_dir_url() which works for plugins installed in the plugins directory,
	 * and for plugins bundled with themes.
	 *
	 * @return array
	 * @throws Exception If the plugin is not located in the expected location.
	 */
	public function locate_plugin() {
		$file_name = $this->get_object_reflection()->getFileName();

		// Windows compat.
		if ( '/' !== \DIRECTORY_SEPARATOR ) {
			// @codeCoverageIgnoreStart
			$file_name = str_replace( \DIRECTORY_SEPARATOR, '/', $file_name );
			// @codeCoverageIgnoreEnd
		}

		$plugin_dir  = dirname( dirname( $file_name ) );
		$plugin_path = $this->relative_path( $plugin_dir, basename( content_url() ), \DIRECTORY_SEPARATOR );

		$dir_url      = content_url( trailingslashit( $plugin_path ) );
		$dir_path     = $plugin_dir;
		$dir_basename = basename( $plugin_dir );

		return compact( 'dir_url', 'dir_path', 'dir_basename' );
	}

	/**
	 * Relative Path
	 *
	 * Returns a relative path from a specified starting position of a full path
	 *
	 * @param string $path  The full path to start with.
	 * @param string $start The directory after which to start creating the relative path.
	 * @param string $sep   The directory separator.
	 *
	 * @return string
	 */
	public function relative_path( $path, $start, $sep ) {
		$path = explode( $sep, untrailingslashit( $path ) );
		if ( count( $path ) > 0 ) {
			foreach ( $path as $p ) {
				array_shift( $path );
				if ( $p === $start ) {
					break;
				}
			}
		}

		return implode( $sep, $path );
	}

	/**
	 * Get the public URL to the asset file.
	 *
	 * @param string $path_relative Path relative to this plugin directory root.
	 *
	 * @return string The URL to the asset.
	 */
	public function asset_url( $path_relative = '' ) {
		return $this->dir_url . $path_relative;
	}

	/**
	 * Call trigger_error() if not on VIP production.
	 *
	 * @param string $message Warning message.
	 * @param int    $code    Warning code.
	 */
	public function trigger_warning( $message, $code = \E_USER_WARNING ) {
		if ( ! $this->is_wpcom_vip_prod() ) {
			// phpcs:disable
			trigger_error( esc_html( get_class( $this ) . ': ' . $message ), $code );
			// phpcs:enable
		}
	}

	/**
	 * Return whether we're on WordPress.com VIP production.
	 *
	 * @return bool
	 */
	public function is_wpcom_vip_prod() {
		return ( defined( '\WPCOM_IS_VIP_ENV' ) && \WPCOM_IS_VIP_ENV );
	}

	/**
	 * Is WP debug mode enabled.
	 *
	 * @return boolean
	 */
	public function is_debug() {
		return ( defined( '\WP_DEBUG' ) && \WP_DEBUG );
	}

	/**
	 * Is WP script debug mode enabled.
	 *
	 * @return boolean
	 */
	public function is_script_debug() {
		return ( defined( '\SCRIPT_DEBUG' ) && \SCRIPT_DEBUG );
	}

	/**
	 * Return the current version of the plugin.
	 *
	 * @return mixed
	 */
	public function version() {
		$args = [
			'Version' => 'Version',
		];
		$meta = get_file_data( $this->dir_path . '/unsplash.php', $args );

		return isset( $meta['Version'] ) ? $meta['Version'] : time();
	}

	/**
	 * Sync the plugin version with the asset version.
	 *
	 * @return string
	 */
	public function asset_version() {
		if ( $this->is_debug() || $this->is_script_debug() ) {
			return time();
		}

		return $this->version();
	}
}
