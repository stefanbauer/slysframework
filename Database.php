<?php
/**
 * Database.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace System;

use System\Database\Exception;

class Database extends \mysqli {

	private static $_loginData = [];
	private static $_isConnected = false;
	private static $_instance;

	/**
	 * Returns the singleton instance
	 *
	 * @access public
	 * @return Database
	 */
	public static function getInstance() {

		if( self::$_instance == null )
			self::$_instance = new self;

		return self::$_instance;

	}

	/**
	 * Constructor
	 *
	 * @access public
	 * @return Database
	 */
	public function __construct() {

		// Initialize
		$this->_init();

	}

	/**
	 * Disconnect
	 *
	 * @access public
	 * @return bool
	 */
	public function disconnect() {

		if( !self::$_isConnected )
			return false;

		// Close connection
		$this->close();

	}

	/**
	 * Initialization
	 *
	 * @access	private
	 * @return	Database
	 */
	private function _init() {

		// Get config
		$dbConfig = Application::getInstance()->getConfig('database');

		// Write config to class var
		self::$_loginData = [
			'host'		=> $dbConfig['host'],
			'port'		=> $dbConfig['port'],
			'user'		=> $dbConfig['username'],
			'pass'		=> $dbConfig['password'],
			'dbname'	=> $dbConfig['dbname']
		];

		// Initialize connect
		$this->_initConnect();

	}

	/**
	 * Initialize connet
	 *
	 * @access public
	 * @return bool
	 * @throws Database\Exception
	 */
	private function _initConnect() {

		// Abort if already connected
		if( self::_isConnected() )
			return false;

		// Connect
		parent::__construct(
			self::$_loginData['host'],
			self::$_loginData['user'],
			self::$_loginData['pass'],
			self::$_loginData['dbname'],
			self::$_loginData['port']
		);

		// Check if connection is up
		if( !$this->connect_error )
			self::$_isConnected = true;
		else
			throw new Exception( 'Connecting to ' . self::$_loginData['host'] . ':' . self::$_loginData['port'] . 'failed: '. $this->connect_error );

	}

	/**
	 * Returns if already connected
	 *
	 * @access private
	 * @return bool
	 */
	private function _isConnected() {

		return self::$_isConnected;

	}

	private function __clone(){}

}