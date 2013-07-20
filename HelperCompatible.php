<?php
/**
 * HelperCompatible.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace System;

/**
 * Class HelperCompatible
 * Defines methods that provide compatibility with plugins
 *
 * @package System
 */
class HelperCompatible {

	/**
	 * This method executes helper
	 *
	 * @param       $name
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($name, array $arguments = null) {

		// find helper with specified name
		$helper = Application::getInstance()->getHelper($name);

		$result = call_user_func_array(array($helper, $name), $arguments);

		return $result;

	}
}