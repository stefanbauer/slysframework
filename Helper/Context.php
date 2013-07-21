<?php
/**
 * Context.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace Slys\Helper;


class Context {

	private $_context = 'html';

	public function context() {
		return $this;
	}

	public function setContext($context) {
		$this->_context = $context;
	}

	public function isJSON() {
		return $this->_context == 'json';
	}


}