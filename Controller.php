<?php
/**
 * Controller.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace System;

class Controller extends HelperCompatible {

	/**
	 * @var View
	 */
	protected $view;

	/**
	 * Constructor
	 *
	 * @access public
	 * @param View $view
	 */
	final public function __construct( View $view ) {

		$this->view = $view;

	}

	/**
	 * Proxy to the Application getRequest method
	 *
	 * @access public
	 * @return Request
	 */
	public function getRequest() {

		return Application::getInstance()->getRequest();

	}

	/**
	 * Returns the application
	 *
	 * @access public
	 * @return Application
	 */
	public function getApplication() {

		return Application::getInstance();

	}

	/**
	 * Returns the params
	 *
	 * @access protected
	 * @return array
	 */
	protected function _getParams() {

		$request = $this->getRequest();
		return $request->getParams();

	}

}