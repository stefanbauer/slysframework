<?php
/**
 * generate_models.php
 *
 * @author		pgalaton <pgalaton@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

function toCamelCase( $string, $camelFirst = false ) {

	$string = lcfirst( str_replace( ' ','', ucwords( strtolower( str_replace( '_', ' ', $string ) ) ) ) );

	if( $camelFirst )
		$string = ucfirst($string);

	return $string;

}

error_reporting( E_ALL );

use Slys\Application;

define( 'DS', DIRECTORY_SEPARATOR );
define( 'PATH_APP', './../../application' );
define( 'PATH_SLYS', './../../slys' );
define( 'PATH_TMP', './../../tmp' );
define( 'PATH_LANG', PATH_APP . DS . 'lang' );
define( 'PATH_VENDORS', PATH_APP . DS . 'vendors' );
define( 'PATH_LAYOUTS', PATH_APP . DS . 'layouts' );
define( 'PATH_MODULES', PATH_APP . DS . 'modules' );

require_once PATH_SLYS . DS . 'Application.php';

$application = Application::getInstance();
$db = \Slys\Database::getInstance();

// relation between table and module
$tableToModule = $application->getConfig( 'table-module-mapping' );

$result = $db->query( 'SHOW TABLES' );

while( $tableInfo = $result->fetch_array()) {

	$tableName = $tableInfo[0];

	if( empty( $tableToModule[$tableName] ) )
		continue;

	$moduleName = $tableToModule[$tableName];

	$className = toCamelCase( $tableName, true );
	$folderPath = PATH_MODULES . DS . $moduleName . DS . 'models';
	$filePath = $folderPath . DS . $className . '.php';

	if( !file_exists( $folderPath ) )
		mkdir( $folderPath, 0775, true );

	// create empty class with no functionality
	if( !file_exists( $filePath ) ) {
		$content = <<<CONTENT
<?php
/**
 * $className
 *
 * @author		ModelGenerator <robot@tritendo.de>
 * @copyright	Copyright (c) Tritendo Media GmbH. (http://www.tritendo.de)
 */

namespace $moduleName\Model;

use Slys\Db\Entity;

/**
 *
 * @package $moduleName\Model
 */
class $className extends Entity {

}
CONTENT;

		file_put_contents( $filePath, $content );
		// some info to display
		echo 'Table `'.$tableName.'` -> '.$moduleName.'\\Model\\'.$className.' file created'.PHP_EOL;

	}

	// prepare doc block
	$phpDocRows = [];

	$columnsResult = $db->query('SHOW COLUMNS FROM `'.$tableName.'`');

	$docString = '';

	$primaryKeys = [];
	while( $columnInfo = $columnsResult->fetch_assoc() ) {

		$columnName = $columnInfo['Field'];

		$methodNameBase = toCamelCase($columnName, true);

		// get method
		$columnType = 'mixed';

		if( strpos($columnInfo['Type'], 'varchar') !== false ) {
			$columnType = 'string';
		}
		else if( strpos($columnInfo['Type'], 'int') !== false ) {
			$columnType = 'int';
		}
		else if( strpos($columnInfo['Type'], "enum('0','1')") !== false ) {
			$columnType = 'bool';
		}

		if($columnInfo['Key'] == 'PRI') {
			$primaryKeys[] = $columnName;
		}

		$returnType = $columnType;

		if( $columnInfo['Null'] == 'YES' )
			$returnType .= '|null';

		$default = '';

		if( $columnInfo['Default'] ) {

			if( is_numeric( $columnInfo['Default'] ) )
				$defaultParam = $columnInfo['Default'];
			else
				$defaultParam = '\''.$columnInfo['Default'].'\'';

			$default = ' = ' . $defaultParam;

		}

		$docInfo = [ 'method' => 'get'.$methodNameBase, 'string' => ' * @method '.$returnType.' get'.$methodNameBase.'()'];
		$phpDocRows[] = $docInfo;

		// set method
		$docInfo = [ 'method' => 'set'.$methodNameBase, 'string' => ' * @method void set'.$methodNameBase.'() set'.$methodNameBase.'( $'.lcfirst($methodNameBase) . $default . ' )'];
		$phpDocRows[] = $docInfo;

	}

	$rc = new ReflectionClass($moduleName.'\\Model\\'.$className);
	$commentRows = explode(PHP_EOL, $rc->getDocComment());

	$docAdded = false;

	$methodDocBlock = '';

	// prepare doc string
	// fill doc block with methods generated from database
	foreach( $phpDocRows as $docRow ) {

		// only add methods that does is not overwritten
		if( $rc->hasMethod($docRow['method']) == false )
			$methodDocBlock .= $docRow['string'] . PHP_EOL;

	}

	foreach( $commentRows as $rowNumber => $row ) {

		// find @method row
		if( strpos( $row, '@method' ) !== false ) {

			// if we have not yet added doc block
			if( $docAdded == false ) {

				$docString .= $methodDocBlock;
				$docAdded = true;

			}

		}
		else
			$docString .= $row . PHP_EOL;

	}

	$docString = trim( $docString );

	if( $docAdded == false )
		$docString = str_replace(' */', ' * ' . PHP_EOL . $methodDocBlock . ' */', $docString);


	// replace old doc block with new one
	$classContent = file_get_contents( $filePath );
	$classContent = str_replace( $rc->getDocComment(), $docString, $classContent );
	file_put_contents( $filePath, $classContent );

	// some info to display
	echo 'Table `'.$tableName.'` -> '.$moduleName.'\\Model\\'.$className.' PHPDoc updated'.PHP_EOL;

	// primary key
	// add primary key definition
	$primaryKeyDefinitionString = "\t".'protected $_primaryKey = [\''.implode('\', \'', $primaryKeys).'\'];';

	$buffer = '';
	$found = false;
	$handle = @fopen($filePath, "rw");

	if( $handle ) {

		while( ( $buffer = fgets( $handle, 4096 ) ) !== false ) {

			if( strstr( $buffer, '$_primaryKey' ) !== false  ) {
				$found = true;
				break;
			}

		}

		fclose($handle);

	}

	if( $found )
		$classContent = str_replace( $buffer, $primaryKeyDefinitionString . PHP_EOL, $classContent );
	else
		$classContent = str_replace( 'Entity {', 'Entity {' . PHP_EOL . PHP_EOL . $primaryKeyDefinitionString . PHP_EOL, $classContent );

	file_put_contents( $filePath, $classContent );

}

echo PHP_EOL;