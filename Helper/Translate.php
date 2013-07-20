<?php
/**
 * Translate.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace System\Helper;

class Translate {

	private $_language = 'en';
	private $_translations = [];

	private function _loadTranslations() {

		$translationFile = PATH_LANG . DS . $this->_language . '.lang.php';

		if( file_exists($translationFile) )
			$this->_translations = require $translationFile;


	}

	/**
	 * @param string $language
	 */
	public function setLanguage( $language ) {

		if($this->_language == $language)
			return;

		$this->_language = $language;
		$this->_loadTranslations();
	}

	/**
	 * @return string
	 */
	public function getLanguage() {

		return $this->_language;
	}

	public function translate($label) {

		$translation = $label;

		if( array_key_exists($label, $this->_translations) )
			$translation = $this->_translations[$label];

		return $translation;

	}

}