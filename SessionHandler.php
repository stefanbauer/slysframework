<?php
/**
 * SessionHandler.php
 *
 * @author		sbauer <sbauer@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace System;

class SessionHandler {

	/**
	 * @var SessionHandler
	 */
	private static $_instance;

	/**
	 * @var Database
	 */
	private $_database;

	/**
	 * On starting a new session
	 *
	 * @access public
	 * @return bool
	 */
	public function open() {

		$this->_database = Database::getInstance();
		return true;

	}

	/**
	 * On closing a session
	 *
	 * @access public
	 * @return bool
	 */
	public function close() {

		$this->_database->close();
		return true;

	}

	/**
	 * Reads session data from database
	 *
	 * @access public
	 * @param $sessionId
	 * @return string
	 */
	public function read( $sessionId ) {

		$sql = "SELECT data FROM session WHERE session_id = ? LIMIT 1";
		$stmt = $this->_database->prepare( $sql );

		$stmt->bind_param( 's', $sessionId );
		$stmt->execute();

		$stmt->bind_result( $data );
		$stmt->fetch();

		return $data;

	}

	/**
	 * Writes session data to the database
	 *
	 * @access public
	 * @param $sessionId
	 * @param $data
	 * @return bool
	 */
	public function write( $sessionId, $data ) {

		// Query
		$sql = "REPLACE INTO
					session
				( session_id, last_modified, data, ip, agent, user_id ) VALUES
				( ?, ?, ?, ?, ?, ? )";

		$stmt = $this->_database->prepare( $sql );

		// Some defaults
		$time = date( 'Y-m-d H:i:s' );
		$user_id = null;

		// Bind param
		$stmt->bind_param(
			'ssssss',
			$sessionId,
			$time,
			$data,
			$_SERVER['REMOTE_ADDR'],
			$_SERVER['HTTP_USER_AGENT'],
			$user_id
		);

		// Execute
		$stmt->execute();

		return true;

	}

	/**
	 * Destroys a session
	 *
	 * @access public
	 * @param $sessionId
	 * @return bool
	 */
	public function destroy( $sessionId ) {

		$sql = "DELETE FROM session WHERE session_id = ?";

		$stmt = $this->_database->prepare( $sql );
		$stmt->bind_param( 's', $sessionId );
		$stmt->execute();

		return true;

	}

	/**
	 * Garbage collector
	 *
	 * @access public
	 * @param $max
	 * @return bool
	 */
	public function gc( $max ) {

		$config = Application::getInstance()->getConfig( 'session' );

		$sql = "DELETE FROM session where last_modified < DATE_SUB( NOW(), INTERVAL {$config['hourValidity']} HOUR";

		$stmt = $this->_database->prepare( $sql );
		$stmt->execute();

		return true;

	}

	/*
	 * Starts the session
	 *
	 * @access public
	 * @return SessionHandler
	 */
	public static function startSession( $sessionName = 'PHPSESSID', $secure = false ) {

		// Singleton
		if( !empty( self::$_instance ) )
			return self::$_instance;

		self::$_instance = new self();

		// Bits per char
		ini_set( 'session.hash_bits_per_character', 5 );

		// Session only cookies
		ini_set('session.use_only_cookies', 1);

		// Get session cookie parameters
		$cookieParams = session_get_cookie_params();

		// Set the parameters
		session_set_cookie_params(
			$cookieParams['lifetime'],
			$cookieParams['path'],
			$cookieParams['domain'],
			$secure,
			true
		);

		// Change the session name
		session_name( $sessionName );

		// Now really start the session
		session_start();

		// Regenerate everytime the id
		session_regenerate_id( true );

		// Return instance
		return self::$_instance;

	}

	/**
	 * Destroys a session
	 *
	 * @access public
	 * @return void
	 */
	public static function destroySession() {

		if( session_id() != '' ) {

			// Remove cookie
			$params = session_get_cookie_params();

			setcookie(
				session_name(),
				'',
				0,
				$params['path'],
				$params['domain'],
				$params['secure'],
				isset( $params['httponly'] )
			);

			// Remove session
			session_destroy();


		}

	}

	/**
	 * Constructor
	 *
	 * @access public
	 * @return SessionHandler
	 */
	private function __construct() {

		// Define save handler
		session_set_save_handler(
			array( $this, 'open' ),
			array( $this, 'close' ),
			array( $this, 'read' ),
			array( $this, 'write' ),
			array( $this, 'destroy' ),
			array( $this, 'gc' )
		);

		// This line prevents unexpected effects
		register_shutdown_function( 'session_write_close' );

	}

	private function __clone() {}

}