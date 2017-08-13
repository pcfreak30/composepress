<?php


namespace pcfreak30\WordPress\Plugin\Framework;

class ManagerAbstract extends ComponentAbstract {
	private $modules = [

	];

	/**
	 *
	 */
	public function init() {
		$modules = [];

		$reflect   = new \ReflectionClass( get_called_class() );
		$class     = strtolower( $reflect->getShortName() );
		$namespace = $reflect->getNamespaceName();
		$namespace = str_replace( '\\', '/', $namespace );
		$component = strtolower( basename( $namespace ) );

		$slug         = $this->app->get_safe_slug();
		$filter       = "{$slug}_{$component}_{$class}_modules";
		$modules_list = apply_filters( $filter, $this->modules );

		foreach ( $modules_list as $module ) {
			$class = $module;
			if ( false === strpos( $module, '\\' ) ) {
				$class = $this->namespace . '\\' . $module;
			}
			$modules[ $module ] = $this->app->container->create( $class );
		}
		foreach ( $modules_list as $module ) {
			$modules[ $module ]->parent = $this;
			$modules[ $module ]->init();
		}

		$this->modules = $modules;
	}

	/**
	 * @return array
	 */
	public function get_modules() {
		return $this->modules;
	}

	/**
	 * @param $name
	 *
	 * @return bool|mixed
	 */
	public function get_module( $name ) {
		foreach ( $this->modules as $module ) {
			try {

				if ( is_a( $module, ( new \ReflectionClass( $this ) )->getNamespaceName() . '\\' . $name ) ) {
					return $module;
				}

			} catch ( \ReflectionException $e ) {

			}
		}

		return false;
	}

}