<?php
/**
 * Request.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace Slys;

class Request {

	protected $_module = 'index';
	protected $_controller = 'index';
	protected $_action = 'index';
	protected $_params = [];
	protected $_isGet = false;
	protected $_isPost = false;

	/**
	 * @return bool
	 */
	public function isPost() {
		return $this->_isPost;
	}

	/**
	 * @return bool
	 */
	public function isXmlHttpRequest() {

		return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
				strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

	}

	public function setMethod( $method ) {
		switch( $method ) {
			case 'POST':
				$this->_isPost = true;
				break;
			default:
				$this->_isGet = true;
		}
	}

	/**
	 * @param mixed $action
	 */
	public function setAction( $action ) {

		$this->_action = $action;
	}

	/**
	 * @return mixed
	 */
	public function getAction() {

		return $this->_action;
	}

	/**
	 * @param mixed $controller
	 */
	public function setController( $controller ) {

		$this->_controller = $controller;
	}

	/**
	 * @return mixed
	 */
	public function getController() {

		return $this->_controller;
	}

	/**
	 * @param mixed $module
	 */
	public function setModule( $module ) {

		$this->_module = $module;
	}

	/**
	 * @return mixed
	 */
	public function getModule() {

		return $this->_module;
	}

	/**
	 * @param array $params
	 */
	public function setParams( $params ) {

		// Append module, controller and action
		$dataToAppend = [
			'module' => $this->getModule(),
			'controller' => $this->getController(),
			'action' => $this->getAction()
		];

		$this->_params = array_merge( $params, $dataToAppend );

	}

	/**
	 * @return array
	 */
	public function getParams() {

		return $this->_params;
	}




}