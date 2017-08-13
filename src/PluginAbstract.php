<?php

namespace pcfreak30\WordPress\Plugin\Framework;

use Dice\Dice;
use pcfreak30\WordPress\Plugin\Framework\Exception\ComposerMissing;
use pcfreak30\WordPress\Plugin\Framework\Exception\ContainerInvalid;
use pcfreak30\WordPress\Plugin\Framework\Exception\ContainerNotExists;

/**
 * Class PluginAbstract
 *
 * @package pcfreak30\WordPress\Plugin\Framework
 *
 * @property Dice\Dice $comtainer
 */
abstract class PluginAbstract extends ComponentAbstract {
	/**
	 * Default version constant
	 */
	const VERSION = '';
	/**
	 * Default slug constant
	 */
	const PLUGIN_SLUG = '';

	/**
	 * Path to plugin entry file
	 *
	 * @var string
	 */
	protected $plugin_file;
	/**
	 * Dependency Container
	 *
	 * @var Dice
	 */
	protected $container;

	/**
	 * PluginAbstract constructor.
	 */
	public function __construct() {
		$this->find_plugin_file();
		$this->set_container();

	}

	/**
	 *
	 */
	private function find_plugin_file() {
		$dir  = __DIR__;
		$file = null;
		do {
			$last_dir = $dir;
			$dir      = dirname( $dir );
			$file     = $dir . DIRECTORY_SEPARATOR . static::PLUGIN_SLUG . '.php';
		} while ( ! $this->get_wp_filesystem()->is_file( $file ) && $dir !== $last_dir );
		$this->plugin_file = $file;
	}

	/**
	 * @return \WP_Filesystem_Direct
	 */
	protected function get_wp_filesystem() {
		/** @var \WP_Filesystem_Direct $wp_filesystem */
		global $wp_filesystem;
		if ( is_null( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem;
	}

	/**
	 * @return void
	 */
	abstract public function activate();

	/**
	 * @return void
	 */
	abstract public function deactivate();

	/**
	 * @return void
	 */
	abstract public function uninstall();

	/**
	 * @return string
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * @return Dice
	 */
	public function get_container() {
		return $this->container;
	}

	/**
	 * @throws \pcfreak30\WordPress\Plugin\Framework\Exception\ContainerInvalid
	 * @throws \pcfreak30\WordPress\Plugin\Framework\Exception\ContainerNotExists
	 *
	 * @return void
	 */
	protected function set_container() {
		$slug      = str_replace( '-', '_', static::PLUGIN_SLUG );
		$container = "{$slug}_container";
		if ( ! function_exists( $container ) ) {
			throw new ContainerNotExists( sprintf( 'Container function %s does not exist.', $container ) );
		}
		$this->container = $container();
		if ( ! ( $this->container instanceof Dice ) ) {
			throw new ContainerInvalid( sprintf( 'Container function %s does not return a Dice instance.', $container ) );
		}
	}

	/**
	 * Plugin initialization
	 */
	public function init() {
		if ( ! $this->get_dependancies_exist() ) {
			return;
		}
		$components = ( new \ReflectionClass( $this ) )->getProperties();
		$components = array_filter(
			$components,
			/**
			 * @param \ReflectionProperty $component
			 *
			 * @return bool
			 */
			function ( $component ) {
				$getter = 'get_' . $component->name;

				return method_exists( $this, $getter ) && ( new \ReflectionMethod( $this, $getter ) )->isPublic() && $this->$getter() instanceof ComponentAbstract;
			} );
		$components = array_map(
		/**
		 * @param \ReflectionProperty $component
		 *
		 * @return ComponentAbstract
		 */
			function ( $component ) {
				$getter = 'get_' . $component->name;

				return $this->$getter();
			}, $components );
		/** @var ComponentAbstract $component */
		foreach ( $components as $component ) {
			$component->parent = $this;

		}
		foreach ( $components as $component ) {
			$component->init();
		}

	}

	/**
	 * @return bool
	 */
	protected function get_dependancies_exist() {
		return true;
	}

	/**
	 * @return string
	 */
	public function get_slug() {
		return static::PLUGIN_SLUG;
	}

	/**
	 * @return string
	 */
	public function get_version() {
		return static::VERSION;
	}


	/**
	 * @return string
	 */
	public function get_safe_slug() {
		return strtolower( str_replace( '-', '_', $this->get_slug() ) );
	}

	/**
	 * @param null $field
	 *
	 * @return string|array
	 */
	public function get_plugin_info( $field = null ) {
		$info = get_plugin_data( $this->plugin_file );
		if ( null !== $field && isset( $info[ $field ] ) ) {
			return $info[ $field ];
		}

		return $info;
	}
}