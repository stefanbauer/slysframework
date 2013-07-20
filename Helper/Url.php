<?php
/**
 * Url.php
 *
 * @author		sbauer <sbauer@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace System\Helper;

use System\Request;

class Url {

	public function url( array $config = [] ) {

		if( empty( $config ) ) {
			return $_SERVER['REQUEST_URI'];
		}

		$url = '/';

		if( array_key_exists( 'module', $config ) )
			$url .= $config['module'].'/';

		if( array_key_exists( 'controller', $config ) )
			$url .= $config['controller'].'/';

		if( array_key_exists( 'action', $config ) )
			$url .= $config['action'].'/';

		return $url;

	}

}