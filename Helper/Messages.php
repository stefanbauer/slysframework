<?php
/**
 * Messages.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */
namespace Slys\Helper;

class Messages {

	public function messages() {
		return $this;
	}

	public function add( $message, $type ) {
		var_dump($message, $type, 'added');
	}

}