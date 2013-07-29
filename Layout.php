<?php
/**
 * Layout.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace Slys;

use Slys\Application\Exception;

class Layout extends HelperCompatible {

	protected $_title;

	/** @var callable[]  */
	protected $_placeholders = [];

	/** @var View[] */
	protected $_views = [];

	protected $_name;

	/** @var  View */
	protected $_content;


	/**
	 * Render
	 *
	 * @access public
	 * @return void
	 */
	public function render() {

		if( Application::getInstance()->getHelper('context')->isJSON() ) {
			echo $this->_content->toJSON();
		}
		else {

			$this->_processPlaceholders();
			require_once PATH_LAYOUTS . DS . $this->getName() . '.phtml';
		}

	}

	/**
	 * @param $name
	 * @return View
	 * @throws Application\Exception
	 */
	public function placeholder( $name ) {

		if( empty( $this->_views[$name] ) )
			throw new Exception( 'View with the name '.$name.' is not prepared for the layout '.$this->_name.
			                     '. Please check configuration.' );

		return $this->_views[$name];

	}

	/**
	 * @return View
	 */
	public function content() {
		return $this->_content;
	}

	/**
	 * Sets a title
	 *
	 * @access public
	 * @param $title
	 */
	public function setTitle( $title ) {

		$this->_title = $title;

	}

	public function setName( $name ) {

		$this->_name = $name;

	}

	/**
	 * Gets a name
	 *
	 * @access public
	 * @return string
	 */
	public function getName() {

		return $this->_name;

	}


	/**
	 * @param View $content
	 */
	public function setContent( View $content ) {

		$this->_content = $content;
	}

	/**
	 * @throws Application\Exception
	 */
	private function _processPlaceholders() {

		$application = Application::getInstance();

		$layoutsConfig = $application->getConfig( 'layouts', array() );

		if( array_key_exists($this->_name, $layoutsConfig) ) {

			$config = $layoutsConfig[$this->_name];

			if( array_key_exists('placeholders', $config) && is_array($config['placeholders']) ) {

				foreach($config['placeholders'] as $placeholderName => $callable) {

					// each and every placeholder should have valid callable object
					if( !is_callable($callable) )
						throw new Exception('Placeholder '.$placeholderName.' in the layout '.$this->_name.
						' must be valid callable');

					// processing callable with its own clone of main request
					$request = clone $application->getRequest();

					// send to callable request and placeholder name, who knows when we will need this
					$viewObject = call_user_func_array($callable, array($request, $placeholderName));

					// if callable returned us something, and this something is View object
					// use it as result
					if( false === $viewObject instanceof View)
						$viewObject = $application->processRequest( $request );

					// store view object
					$this->_views[$placeholderName] = $viewObject;
				}
			}
		}

	}

}