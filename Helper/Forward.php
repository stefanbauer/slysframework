<?php
/**
 * Forward.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace Slys\Helper;


use Slys\Application;

class Forward {

	public function forward( $action, $controller = null, $module = null ) {
		$request = Application::getInstance()->getRequest();
		$request->setAction($action);

		if(!empty($controller))
			$request->setController($controller);

		if(!empty($module))
			$request->setModule($module);

		Application::getInstance()->addRequest($request);
	}

} 