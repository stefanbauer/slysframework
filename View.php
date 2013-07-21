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

	private $data = [];
	private $_templateFile;

	/**
	 * @param string $file path to template in views folder
	 * @param string $module module where template file can be found. Defaults to index
	 * @throws Application\Exception
	 */
	public function setTemplate( $file, $module = 'index' ) {

		$pathToFile = PATH_MODULES . DS . $module . DS . 'views' . DS . $file;

		if( !file_exists( $pathToFile ) )
			throw new Exception(
				'Template file `'. $file . '` '.
				'in module `'. $module . '` was not found. Path:'.$pathToFile );

		$this->_templateFile = $pathToFile;
	}

	/**
	 * Set some data
	 *
	 * @access public
	 * @param array $data
	 */
	public function setData( array $data ) {
		$this->data = $data;
	}

	public function __get( $name ) {

		if(!empty($this->data[$name]))
			return $this->data[$name];

		return null;

	}

	public function __set( $name, $value ) {
		$this->data[$name] = $value;
	}

	public function __isset( $name ) {
		return !empty($this->data[$name]);
	}


	/**
	 * Render
	 *
	 * @access public
	 * @return void
	 */
	public function render() {

		require_once $this->_templateFile;

	}

}