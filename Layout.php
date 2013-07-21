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


	/**
	 * Render
	 *
	 * @access public
	 * @return void
	 */
	public function render() {

		if( Application::getInstance()->getHelper('context')->isJSON() ) {
			echo $this->_views['content']->toJSON();
		}
		else {
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
			throw new Exception( 'Placeholder with name '.$name.' is not prepeared for the layout '.$this->_name.
			'. Please check configuration.' );

		return $this->_views[$name];

	}


	/**
	 * Returns a placeholder
	 *
	 * @access public
	 * @param $name
	 * @return callable
	 * @throws Application\Exception
	 */
	public function getPlaceholder($name) {

		if( empty( $this->_placeholders[$name] ) )
			throw new Exception( 'Placeholder with name '.$name.' is not prepeared for the layout '.$this->_name.
			'. Please check configuration.' );

		return $this->_placeholders[$name];
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

		if($this->_name == $name)
			return;

		$this->_name = $name;


		// when name of layouts is changed
		// reset placeholders
		$this->_placeholders = [
			// content placeholder is default one
			// can be overwritten in layouts config, by creating placeholder with name 'content'
			'content' => function(Request $request) {}
		];

		// reset all views
		$this->_views = [];

		// load placeholders based on new layout name
		$layoutsConfig = Application::getInstance()->getConfig( 'layouts', array() );

		if( array_key_exists($this->_name, $layoutsConfig) ) {

			$config = $layoutsConfig[$this->_name];

			if( array_key_exists('placeholders', $config) && is_array($config['placeholders']) ) {

				foreach($config['placeholders'] as $placeholderName => $closure) {

					// each and every placeholder should have valid callable object
					if( !is_callable($closure) )
						throw new Exception('Placeholder '.$placeholderName.' in layout '.$this->_name.' must be valid callable');

					$this->_placeholders[$placeholderName] = $closure;
				}

			}

		}

		$this->_processPlaceholders();

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
	 * Evaluates placeholders callable objects
	 */
	private function _processPlaceholders() {

		$application = Application::getInstance();

		// while there is placeholders to process
		while( count($this->_placeholders) > 0 ) {

			$placeholderName = current( array_keys( $this->_placeholders ) );
			$callable = array_shift( $this->_placeholders );

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

			if( $application->getHelper('context')->isJSON() ) {
				$this->_placeholders = [];
				return;
			}

		}

	}

}