<?php

namespace Cleantalk\USP\Variables;

/**
 * Class Get
 * Safety handler for $_GET
 *
 * @usage \Cleantalk\USP\Variables\Get::get( $name );
 *
 * @package Cleantalk\USP\Variables
 */
class Get extends SuperGlobalVariables{
	
	static $instance;
	
	/**
	 * Gets given $_GET variable and save it to memory
	 *
	 * @param string $name
	 * @param bool $do_decode
	 *
	 * @return mixed|string
	 */
	protected function get_variable( $name, $do_decode = true ){
		
		// Return from memory. From $this->variables
		if(isset(static::$instance->variables[$name]))
			return static::$instance->variables[$name];
		
		if( function_exists( 'filter_input' ) )
			$value = filter_input( INPUT_GET, $name );
		
		if( empty( $value ) )
			$value = isset( $_GET[ $name ] ) ? $_GET[ $name ]	: '';
		
		$value = $do_decode ? urldecode( $value ) : $value;
		
		// Remember for thurther calls
		static::getInstance()->remember_variable( $name, $value );
		
		return $value;
	}
}