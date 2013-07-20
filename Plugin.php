<?php
/**
 * Plugin.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace System;

class Plugin extends HelperCompatible {

	/**
	 * This method is executed before controller class is created
	 *
	 * @access public
	 * @return void
	 */
	public function preDispatch() {

	}


	/**
	 * This method is executed right after action
	 *
	 * @access public
	 * @return void
	 */
	public function postDispatch() {

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

}