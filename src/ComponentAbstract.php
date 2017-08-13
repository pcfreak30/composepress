<?php


namespace pcfreak30\WordPress\Plugin\Framework;

/*
 * @property $app PluginAbstract
 * @property $parent PluginAbstract
 */
abstract class ComponentAbstract extends BaseObjectAbstract {
	/**
	 * @var PluginAbstract
	 */
	private $app;

	/**
	 * @var ComponentAbstract
	 */
	private $parent;

	/**
	 *
	 */
	abstract public function init();

	/**
	 *
	 */
	public function __destruct() {
		$this->app    = null;
		$this->parent = null;
	}

	/**
	 * @return ComponentAbstract
	 */
	public function get_parent() {
		return $this->parent;
	}

	/**
	 * @param ComponentAbstract $parent
	 */
	public function set_parent( $parent ) {
		$this->parent = $parent;
	}

	/**
	 * @return PluginAbstract
	 */
	public function get_app() {
		if ( null === $this->app ) {
			$parent = $this;
			while ( $parent->has_parent() ) {
				$parent = $this->parent;
			}
			$this->app = $parent;
		}

		return $this->app;
	}

	public function has_parent() {
		return null !== $this->parent;
	}

}