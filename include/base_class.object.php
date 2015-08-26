<?php
if ( ! class_exists('WPDK_BaseClass_Object') ) {

	/**
	 * Class WPDK_BaseClass_Object
	 *
	 * @property-read	wpdkPlugin		$addon;
	 *
	 * @package wpdkPlugin\BaseClass\Object
	 * @author Lance Cleveland <lance@charlestonsw.com>
	 * @copyright 2015 Charleston Software Associates, LLC
	 */
	class WPDK_BaseClass_Object {
		protected $addon;

		/**
		 * @param array $options
		 */
		function __construct( $options = array() ) {
			if ( is_array( $options ) && ! empty( $options ) ) {
				foreach ( $options as $property => $value ) {
					if ( property_exists( $this, $property ) ) {
						$this->$property = $value;
					}
				}
			}
			$this->addon = wpdkPlugin::init();
		}
	}

}