<?php
/**
 * View.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace Slys;

use Slys\Application\Exception;

class View extends HelperCompatible {

	private $_data = [];
	private $_templateFile;

	/**
	 * @param string $file path to template in views folder
	 * @param string $module module where template file can be found. Defaults to index
	 * @throws Application\Exception
	 */
	public function setTemplate( $file, $module = 'index' ) {

		$pathToFile = PATH_MODULES . DS . $module . DS . 'views' . DS . $file;
		$this->_templateFile = $pathToFile;
	}

	/**
	 * Set some data
	 *
	 * @access public
	 * @param array $data
	 */
	public function setData( array $data ) {
		$this->_data = $data;
	}

	/**
	 * @throws Application\Exception
	 */
	public function render() {
		if( !file_exists( $this->_templateFile ) )
			throw new Exception('Template file `'. $this->_templateFile . '` was not found' );

		require $this->_templateFile;

	}

	public function toJSON() {

		return json_encode( $this->_data );

	}

	public function __get( $name ) {

		if( !empty( $this->_data[$name] ) )
			return $this->_data[$name];

		return null;

	}

	public function __set( $name, $value ) {

		$this->_data[$name] = $value;

	}

	public function __isset( $name ) {

		return !empty( $this->_data[$name] );

	}

	public function __unset($name) {

		unset( $this->_data[$name] );

	}

}