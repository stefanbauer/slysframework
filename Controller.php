<?php
/**
 * Controller.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace Slys;

class Controller extends HelperCompatible {

	/**
	 * @var View
	 */
	protected $view;

	/**
	 * @var Request
	 */
	private $_request;

	/**
	 * Constructor
	 *
	 * @access public
	 * @param View $view
	 * @param Request $request
	 */
	final public function __construct( View $view, Request $request ) {

		$this->view = $view;
		$this->_request = $request;

	}

	/**
	 * Get request
	 *
	 * @access public
	 * @return Request
	 */
	public function getRequest() {

		return $this->_request;

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

		return $this->getRequest()->getParams();

	}

}