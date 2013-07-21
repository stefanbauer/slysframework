<?php
/**
 * Entity.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace Slys\Db;

use Slys\Application;
use Slys\Database;

class Entity {

	protected $_tableName = null;
	protected $_primaryKey = [];
	protected $_className = null;
	protected $_data = [];
	private $_loaded = false;

	public final function __construct( $id = null ) {

		if( $this->_tableName == null )
			$this->_detectTableName();

		if( !is_array($this->_primaryKey) )
			$this->_primaryKey = [$this->_primaryKey];

		if( $id != null )
			$this->_load($id);

	}

	public function fromArray(array $values) {

		foreach( $values as $fieldName => $value ) {

			$methodName = 'set'.Application::getInstance()->toCamelCase($fieldName, true, '_');
			$this->$methodName($value);

		}

	}

	/**
	 * @return array
	 */
	public function toArray() {

		return $this->_data;

	}

	public function save() {

		$db = Database::getInstance();

		if( $this->_loaded ) {
			$query = "UPDATE `".$this->_tableName."` SET ";
		} else {
			$query = "INSERT INTO `".$this->_tableName."` SET ";
		}

		$set = [];

		foreach($this->_data as $columnName => $value) {

			// skip primary keys
			if( in_array($columnName, $this->_primaryKey) )
				continue;

			$value = is_numeric($value) ? (int)$value : "'".$db->real_escape_string($value)."'";
			$set[] = '`'.$columnName.'` = ' . $value;
		}

		$query .= join(', ', $set);

		if( $this->_loaded ) {
			$query .= ' WHERE '. $this->_getWherePart();
		}

		$result = $db->query($query);

		var_dump($query);

//		$query = "REPLACE INTO `".$this->_tableName."` (`".implode('`,`', array_keys($this->_data))."`) VALUES (".implode(',', $values).")";
//		$result = $db->query($query);

	}

	function __call( $name, $arguments ) {

		// convert from getColumnName to get_column_name
		$underscoredName = strtolower( preg_replace('/([A-Z])/', '_$1', $name ) );

		if(strpos( $underscoredName,'get_' ) === 0) {

			$columnName = str_replace('get_', '', $underscoredName );
			return $this->_data[$columnName];

		}
		elseif(strpos( $underscoredName,'set_' ) === 0) {

			$columnName = str_replace('set_', '', $underscoredName );
			$this->_data[$columnName] = $arguments[0];

		}

		return null;

	}

	private function _detectTableName() {

		$className = get_class($this);
		$tableName = lcfirst( substr($className, strrpos($className, '\\') + 1) );
		$normalizedName = strtolower( preg_replace('/([A-Z])/', '_$1', $tableName ) );
		$this->_tableName = $normalizedName;

	}

	/**
	 * @param mixed $id
	 */
	protected function _load( $id ) {

		if( !is_array($id) && !empty($id) ) {
			$id = [$id];
		}

		$query = "SELECT * FROM `".$this->_tableName."`";

		// building where part
		$whereParts = [];

		$db = Database::getInstance();

		foreach( $this->_primaryKey as $k => $columnName ) {
			$value = (is_numeric($id[$k])) ? (int)$id[$k] : '\''.$db->real_escape_string($id[$k]).'\'';
			$whereParts[] = '`'.$columnName.'` = '. $value;
		}

		$whereString = implode(' AND ', $whereParts);

		$query .= ' WHERE ' . $whereString;
		$query .= ' LIMIT 1';
		$result = $db->query($query);
		$values = $result->fetch_assoc();

		$this->fromArray($values);

		$this->_loaded = true;

	}


	/**
	 * Return where part consisting from primary keys and its values
	 * will throw error if executed on not loaded object
	 *
	 * @return string
	 * @throws \Slys\Database\Exception
	 */
	private function _getWherePart() {

		if( !$this->_loaded )
			throw new Database\Exception('Can not get where part from not loaded object');

		$db = Database::getInstance();

		// building where part
		$whereParts = [];

		foreach( $this->_primaryKey as $columnName ) {

			$value = $this->_data[$columnName];

			$value = (is_numeric($value)) ? (int)$value : '\''.$db->real_escape_string($value).'\'';

			$whereParts[] = '`'.$columnName.'` = '. $value;
		}

		return join(' AND ', $whereParts);

	}

}