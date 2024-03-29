<?php
/**
 * Application.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace Slys;

use Slys\Application\Exception;

class Application {

	protected $_classMap = [];
	protected $_config = [];
	protected $_loadedPlugins = [];
	protected $_loadedHelpers = [];
	protected $_callStack = [];
	/**
	 * @var Layout
	 */
	protected $_layout;

	/**
	 * @var Request
	 */
	protected $_request;

	/**
	 * @var null|Application
	 */
	protected static $_instance = null;

	/**
	 * Constructor
	 *
	 * @access protected
	 * @return Application
	 */
	protected function __construct() {

		// register autoload function as soon as possible
		spl_autoload_register(array($this, '_autoloadClassName'));

		// create tmp folder
		$this->_createTmpFolder();

		// load class map before any functionality
		$this->_loadClassMap();

		$this->_loadConfiguration();

		$this->_loadVendors();

		// load plugins always after configuration is loaded
		$this->_loadPlugins();
		$this->_loadHelpers();

		$this->_routeRequest();

	}

	/**
	 * Retrieve instance of Application
	 *
	 * @access public
	 * @return Application
	 */
	public static function getInstance() {

		if( self::$_instance == null )
			self::$_instance = new self;

		return self::$_instance;

	}

	/**
	 * Adds request to the end of call stack
	 *
	 * @param Request $request
	 */
	public function addRequest( Request $request ) {
		$this->_callStack[] = $request;
	}

	/**
	 * Starts application
	 *
	 * @access public
	 * @return void
	 */
	public function run() {

		// starting application
		$defaultLayout = $this->getConfig('default-layout', 'main');

		// creating layout before processing requests
		$this->_layout = new Layout();
		$this->_layout->setName( $defaultLayout );


		// process "main" request
		$mainView = $this->processRequest($this->getRequest(), true);

		// process remaining requests from stack
		while( $request = array_shift($this->_callStack) ) {
			$mainView = $this->processRequest($request, true);
		}

		$this->_layout->setContent( $mainView );

		// Render layout
		$this->_layout->render();

	}

	/**
	 * Returns the request
	 *
	 * @access public
	 * @return Request
	 */
	public function getRequest() {
		return $this->_request;
	}


	/**
	 * Returns helper object by name
	 *
	 * @access public
	 * @param $name
	 * @return mixed
	 * @throws Application\Exception
	 */
	public function getHelper($name) {

		if( !array_key_exists($name, $this->_loadedHelpers) )
			throw new Exception('Helper `'.$name.'` is not loaded');

		return $this->_loadedHelpers[$name];

	}


	/**
	 * Returns configuration by key or all configuration array if key was not specified
	 * @param null  $name
	 * @param array $default
	 * @return mixed
	 */
	public function getConfig( $name = null, $default = null ) {

		if( $name == null )
			return $this->_config;

		if( !array_key_exists( $name, $this->_config ) )
			return $default;

		return $this->_config[$name];
	}


	/**
	 * Returns the layout
	 *
	 * @access public
	 * @return Layout
	 */
	public function getLayout() {
		return $this->_layout;
	}


	public function processRequest( Request $request, $triggerPlugins = false ) {

		if($triggerPlugins)
			$this->_callPluginsMethod('preDispatch');

		$viewObject = new View();

		//set default template for the view based on request
		$viewObject->setTemplate( $request->getController() . DS . $request->getAction() . '.phtml',
			$request->getModule() );

		$className = $this->_requestToClassName( $request );
		$actionName = $this->toCamelCase( $request->getAction() );

		$instance = new $className( $viewObject, $request );
		$actionReturn = $instance->{ $actionName . 'Action' }();

		// process action return
		if( $actionReturn instanceof View )
			$viewObject = $actionReturn;
		elseif ( is_array($actionReturn) )
			$viewObject->setData( $actionReturn );

		if($triggerPlugins)
			$this->_callPluginsMethod('postDispatch');

		return $viewObject;

	}


	/**
	 * Converts string test-string to testString
	 *
	 * @param        $string
	 * @param bool   $camelFirst
	 * @param string $symbol
	 * @return string
	 */
	public function toCamelCase($string, $camelFirst = false, $symbol = '-') {

		$string = lcfirst( str_replace( ' ','', ucwords( strtolower( str_replace( $symbol, ' ', $string ) ) ) ) );

		if( $camelFirst )
			$string = ucfirst($string);

		return $string;

	}

	/**
	 * Parses original request and stores it in application
	 *
	 * @access private
	 * @return void
	 */
	private function _routeRequest() {

		$this->_request = new Request();
		$this->_request->setMethod( @$_SERVER['REQUEST_METHOD'] );

		// get request uri path and remove trailing slash
		$url = rtrim( parse_url( @$_SERVER['REQUEST_URI'] , PHP_URL_PATH ), '/');

		// create an array of url pars
		$urlParts = explode( '/', $url );

		// check for module, controller and action
		if( !empty( $urlParts[1] ) )
			$this->_request->setModule($urlParts[1]);

		if( !empty( $urlParts[2] ) )
			$this->_request->setController($urlParts[2]);

		if( !empty( $urlParts[3] ) )
			$this->_request->setAction($urlParts[3]);


		// parse the parameters
		$urlParams = array_slice( $urlParts, 4 );

		// params and values
		$params = [];
		$values = [];

		foreach( $urlParams as $key => $param ) {
			// if key is even, then it is a param, otherwise a value
			if( $key % 2 == 0 )
				$params[] = $param;
			else
				$values[] = $param;

		}

		if(isset($key) && $key % 2 == 0)
			$values[] = '';

		// Now we need to combine params and values as save in request object
		// Also don't forget to add the request vars (from post, get ...)
		$this->_request->setParams(
			array_merge(
				array_combine( $params, $values ), $_REQUEST
			)
		);

	}

	/**
	 * Creates full class name based on request object
	 *
	 * @access private
	 * @param Request $request
	 * @return string
	 */
	private function _requestToClassName( Request $request ) {
		return '\\'.
				$this->toCamelCase( $request->getModule(), true ).
				'\\Controller\\'.
				$this->toCamelCase( $request->getController(), true );
	}


	/**
	 * Loads configuration files and merges them together
	 */
	private function _loadConfiguration() {

		$globalConfigPath = PATH_APP . DS . 'config.php';
		$localConfigPath = PATH_APP . DS . 'local-config.php';

		$this->_config = require $globalConfigPath;


		if( file_exists( $localConfigPath ) ) {

			$localConfig = require $localConfigPath;
			$this->_config = array_replace_recursive($this->_config, $localConfig);

		}

	}

	/**
	 * Auto-loading function
	 * @param $name
	 */
	private function _autoloadClassName( $name ) {

		if( !array_key_exists($name, $this->_classMap) || stream_resolve_include_path($this->_classMap[$name]) == false )
			$this->_buildClassMap();

		if( array_key_exists($name, $this->_classMap) )
			require_once $this->_classMap[$name];

	}

	/**
	 * Initializes helpers based on 'helpers' configuration
	 * @throws Application\Exception
	 */
	private function _loadHelpers() {

		$helpersList = $this->getConfig('helpers');

		// merge default helpers with custom ones
		$helpersList = array_merge([
			'translate'	=> 'Slys\Helper\Translate',
			'url'		=> 'Slys\Helper\Url',
			'messages'	=> 'Slys\Helper\Messages',
			'context'   => 'Slys\Helper\Context',
			'forward'   => 'Slys\Helper\Forward'
		], $helpersList);

		foreach( $helpersList as $helperName => $helperClassName ) {

			$helperObject = new $helperClassName();

			if( method_exists($helperObject, $helperName) )
				$this->_loadedHelpers[$helperName] = $helperObject;
			else
				throw new Exception('Helper `'.$helperClassName.'` does not implement method `'.$helperName.'`');


		}

	}

	/**
	 * Initializes plugins based on 'plugins' configuration
	 *
	 * @access private
	 * @throws Application\Exception
	 */
	private function _loadPlugins() {

		$pluginsList = $this->getConfig('plugins');

		if(empty($pluginsList))
			return;

		foreach( $pluginsList as $pluginClassName ) {

			$pluginObject = new $pluginClassName();

			if( $pluginObject instanceof Plugin )
				$this->_loadedPlugins[] = $pluginObject;
			else
				throw new Exception('Plugin `'.$pluginClassName.'` is not instance of Slys\\Plugin');

		}

	}

	/**
	 * Executed specified method for every loaded plugin
	 *
	 * @access private
	 * @param $methodName
	 * @return void
	 */
	private function _callPluginsMethod( $methodName ) {

		foreach( $this->_loadedPlugins as $pluginObject ) {
			$continue = $pluginObject->$methodName();

			if( $continue === false )
				break;
		}

	}

	/**
	 * Loads class map from file or build new class map if file was not found
	 *
	 * @access private
	 * @return void
	 */
	private function _loadClassMap() {

		if( !file_exists( PATH_TMP . DS . 'classmap.php' ) )
			$this->_buildClassMap();

		$this->_classMap = require PATH_TMP . DS . 'classmap.php';

	}

	/**
	 * Builds map of all classes in locations specified
	 *
	 * @access private
	 * @return void
	 */
	private function _buildClassMap() {

		// map of module elements to load
		$moduleElements = ['controllers' => 'Controller',
							'models' => 'Model',
							'plugins' => 'Plugin',
							'helpers' => 'Helper'];

		// clear variables
		$this->_classMap = [];

		$classMapItems = $this->getConfig('classmap', []);
		$classMapItems = array_merge(['Slys' => PATH_SLYS], $classMapItems);

		// load classmap items
		foreach( $classMapItems as $namespace => $path ) {
			$this->_loadFolderToClassMap($path, $namespace);
		}

		// parse modules
		$modules = $this->getConfig('modules', []);

		foreach( $modules as $moduleName => $setup ) {

			$modulePath = PATH_APP . DS . 'modules' . DS . $moduleName;

			$elementsToAdd = [];

			if( !empty( $setup['namespace'] ) && is_array($setup['namespace']) ) {
				$elementsToAdd = $setup['namespace'];
			}

			$elementsToAdd = array_merge($moduleElements, $elementsToAdd);

			foreach( $elementsToAdd as $folderName => $namespace ) {
				$this->_loadFolderToClassMap($modulePath . DS .$folderName, $moduleName.'\\'.$namespace);
			}

		}



		// storing classmap to a classmap.php cache file
		$exportString = var_export( $this->_classMap, true );
		$exportString = str_replace('\\\\', '\\', $exportString);
		$content = "<?php // generated at " . date('Y-m-d H:i:s') . " \r\nreturn $exportString;";

		file_put_contents( PATH_TMP . DS . 'classmap.php' , $content);

	}


	/**
	 * Recursively loads folder to the class map
	 * @param string $folderPath
	 * @param string $namespace namespace to use as prefix
	 */
	private function _loadFolderToClassMap( $folderPath, $namespace ) {

		$baseFolder = $folderPath;

		// "recursively" parse folder for nested folders
		while( $files = glob( $folderPath . DS . '*.php') ) {

			foreach( $files as $filePath ) {
				// get class name with namespace
				$className = str_replace( array($baseFolder, '/', '.php'), array($namespace, '\\', ''), $filePath);

				$this->_classMap[ $className ] = $filePath;
			}

			// append another asterisk to path "recursive"
			$folderPath .= DS . '*';
		}

	}

	/**
	 * Creates temporary folder
	 *
	 * @access private
	 * @return void
	 * @throws Application\Exception
	 */
	private function _createTmpFolder() {

		// if tmp folder is not existing, create it
		if( !file_exists( PATH_TMP ) )
			if( !mkdir( PATH_TMP ) )
				throw new Exception( 'tmp folder could not be created at '. PATH_TMP );

	}

	private function _loadVendors() {

		set_include_path(
			implode(
				PATH_SEPARATOR, array(
					realpath(PATH_VENDORS),
					get_include_path(),
				)
			)
		);

		$vendors = $this->getConfig('vendors', []);

		foreach($vendors as $vendorName => $setup) {

			if(array_key_exists('autoload', $setup)) {
				call_user_func($setup['autoload']);
			}

		}

	}

}