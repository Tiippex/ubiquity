<?php

namespace Ubiquity\db\traits;

use Ubiquity\log\Logger;
use Ubiquity\cache\database\DbCache;
use Ubiquity\exceptions\CacheException;
use Ubiquity\db\SqlUtils;
use Ubiquity\db\providers\PDOWrapper;

/**
 * Ubiquity\db\traits$DatabaseOperationsTrait
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.0.0
 * @property mixed $cache
 * @property array $options
 * @property string $dbWrapperClass
 */
trait DatabaseOperationsTrait {

	/**
	 *
	 * @var \Ubiquity\db\providers\AbstractDbWrapper
	 */
	protected $wrapperObject;
	private $statements = [ ];

	abstract public function getDSN();

	public function getPdoObject() {
		return $this->wrapperObject;
	}

	public function _connect() {
		$dbWrapperClass = self::$dbWrapperClass ?? PDOWrapper::class;
		$this->wrapperObject = (new $dbWrapperClass ())->connect ( $this->getDSN (), $this->user, $this->password, $this->options );
	}

	/**
	 * Executes an SQL statement, returning a result set as a Statement object
	 *
	 * @param string $sql
	 * @return object|boolean
	 */
	public function query($sql) {
		return $this->wrapperObject->query ( $sql );
	}

	/**
	 *
	 * @param string $tableName
	 * @param string $condition
	 * @param array|string $fields
	 * @param array $parameters
	 * @param boolean|null $useCache
	 * @return array
	 */
	public function prepareAndExecute($tableName, $condition, $fields, $parameters = null, $useCache = NULL) {
		$cache = ((DbCache::$active && $useCache !== false) || (! DbCache::$active && $useCache === true));
		$result = false;
		if ($cache) {
			$cKey = $condition;
			if (is_array ( $parameters )) {
				$cKey .= implode ( ",", $parameters );
			}
			try {
				$result = $this->cache->fetch ( $tableName, $cKey );
				Logger::info ( "Cache", "fetching cache for table {$tableName} with condition : {$condition}", "Database::prepareAndExecute", $parameters );
			} catch ( \Exception $e ) {
				throw new CacheException ( "Cache is not created in Database constructor" );
			}
		}
		if ($result === false) {
			if ($fields = SqlUtils::getFieldList ( $fields, $tableName )) {
				$result = $this->prepareAndFetchAll ( "SELECT {$fields} FROM `" . $tableName . "`" . $condition, $parameters );
				if ($cache) {
					$this->cache->store ( $tableName, $cKey, $result );
				}
			}
		}
		return $result;
	}

	public function prepareAndFetchAll($sql, $parameters = null) {
		$statement = $this->getStatement ( $sql );
		return $this->wrapperObject->fetchAll ( $statement, $parameters );
	}

	public function prepareAndFetchAllColumn($sql, $parameters = null, $column = null) {
		$statement = $this->getStatement ( $sql );
		return $this->wrapperObject->fetchAllColumn ( $statement, $parameters, $column );
	}

	public function prepareAndFetchColumn($sql, $parameters = null, $columnNumber = null) {
		$statement = $this->getStatement ( $sql );
		return $this->wrapperObject->fetchColumn ( $statement, $parameters, $columnNumber );
	}

	/**
	 *
	 * @param string $sql
	 * @return object
	 */
	private function getStatement($sql) {
		if (! isset ( $this->statements [$sql] )) {
			$this->statements [$sql] = $this->wrapperObject->getStatement ( $sql );
		}
		return $this->statements [$sql];
	}

	/**
	 * Execute an SQL statement and return the number of affected rows (INSERT, UPDATE or DELETE)
	 *
	 * @param string $sql
	 * @return int the number of rows that were modified or deleted by the SQL statement you issued
	 */
	public function execute($sql) {
		return $this->wrapperObject->execute ( $sql );
	}

	/**
	 * Prepares a statement for execution and returns a statement object
	 *
	 * @param String $sql
	 * @return object|boolean
	 */
	public function prepareStatement($sql) {
		return $this->wrapperObject->prepareStatement ( $sql );
	}

	public function executeStatement($statement, array $values = null) {
		return $this->wrapperObject->executeStatement ( $statement, $values );
	}

	public function statementRowCount($statement) {
		return $this->wrapperObject->statementRowCount ( $statement );
	}

	/**
	 * Sets $value to $parameter
	 *
	 * @param object $statement
	 * @param String $parameter
	 * @param mixed $value
	 * @return boolean
	 */
	public function bindValueFromStatement($statement, $parameter, $value) {
		return $this->wrapperObject->bindValueFromStatement ( $statement, $parameter, $value );
	}

	/**
	 * Returns the last insert id
	 *
	 * @return string
	 */
	public function lastInserId() {
		return $this->wrapperObject->lastInsertId ();
	}

	public function getTablesName() {
		return $this->wrapperObject->getTablesName ();
	}

	/**
	 * Returns the number of records in $tableName that respects the condition passed as a parameter
	 *
	 * @param string $tableName
	 * @param string $condition Part following the WHERE of an SQL statement
	 */
	public function count($tableName, $condition = '') {
		if ($condition != '')
			$condition = " WHERE " . $condition;
		return $this->wrapperObject->queryColumn ( "SELECT COUNT(*) FROM " . $tableName . $condition );
	}

	public function queryColumn($query, $columnNumber = null) {
		return $this->wrapperObject->queryColumn ( $query, $columnNumber );
	}

	public function fetchAll($query) {
		return $this->wrapperObject->queryAll ( $query );
	}

	public function isConnected() {
		return ($this->wrapperObject !== null && $this->ping ());
	}

	public function ping() {
		return ($this->wrapperObject && 1 === intval ( $this->wrapperObject->queryColumn ( 'SELECT 1', 0 ) ));
	}
}
