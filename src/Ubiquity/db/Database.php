<?php

/**
 * Database implementation
 */
namespace Ubiquity\db;

use Ubiquity\exceptions\CacheException;
use Ubiquity\db\traits\DatabaseOperationsTrait;
use Ubiquity\exceptions\DBException;
use Ubiquity\db\traits\DatabaseTransactionsTrait;
use Ubiquity\controllers\Startup;
use Ubiquity\db\traits\DatabaseMetadatas;

/**
 * Ubiquity Generic database class.
 * Ubiquity\db$Database
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.1.0
 *
 */
class Database {
	use DatabaseOperationsTrait,DatabaseTransactionsTrait,DatabaseMetadatas;
	public static $wrappers = [ 'pdo' => \Ubiquity\db\providers\pdo\PDOWrapper::class,'tarantool' => '\Ubiquity\db\providers\tarantool\TarantoolWrapper','mysqli' => '\Ubiquity\db\providers\mysqli\MysqliWrapper','swoole' => '\Ubiquity\db\providers\swoole\SwooleWrapper' ];
	private $dbType;
	private $serverName;
	private $port;
	private $dbName;
	private $user;
	private $password;
	private $cache;
	private $options;
	public $quote;

	/**
	 *
	 * @var \Ubiquity\db\providers\AbstractDbWrapper
	 */
	protected $wrapperObject;

	/**
	 * Constructor
	 *
	 * @param string $dbWrapperClass
	 * @param string $dbName
	 * @param string $serverName
	 * @param string $port
	 * @param string $user
	 * @param string $password
	 * @param array $options
	 * @param boolean|string $cache
	 * @param mixed $pool
	 */
	public function __construct($dbWrapperClass, $dbType, $dbName, $serverName = "127.0.0.1", $port = "3306", $user = "root", $password = "", $options = [], $cache = false, $pool = null) {
		$this->setDbWrapperClass ( $dbWrapperClass );
		$this->dbType = $dbType;
		$this->dbName = $dbName;
		$this->serverName = $serverName;
		$this->port = $port;
		$this->user = $user;
		$this->password = $password;
		$this->options = $options;
		if ($cache !== false) {
			if ($cache instanceof \Closure) {
				$this->cache = $cache ();
			} else {
				if (\class_exists ( $cache )) {
					$this->cache = new $cache ();
				} else {
					throw new CacheException ( $cache . " is not a valid value for database cache" );
				}
			}
		}
		if ($pool) {
			$this->wrapperObject->setPool ( $pool );
		}
	}

	private function setDbWrapperClass($dbWrapperClass) {
		$this->wrapperObject = new $dbWrapperClass ( $this->dbType );
		$this->quote = $this->wrapperObject->quote;
	}

	/**
	 * Creates the Db instance and realize a safe connection.
	 *
	 * @throws DBException
	 * @return boolean
	 */
	public function connect() {
		try {
			$this->_connect ();
			return true;
		} catch ( \Exception $e ) {
			throw new DBException ( $e->getMessage (), $e->getCode (), $e->getPrevious () );
		}
	}

	public function getDSN() {
		return $this->wrapperObject->getDSN ( $this->serverName, $this->port, $this->dbName, $this->dbType );
	}

	/**
	 *
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getServerName() {
		return $this->serverName;
	}

	public function setServerName($serverName) {
		$this->serverName = $serverName;
	}

	public function setDbType($dbType) {
		$this->dbType = $dbType;
		return $this;
	}

	/**
	 *
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 *
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getDbName() {
		return $this->dbName;
	}

	/**
	 *
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getUser() {
		return $this->user;
	}

	public static function getAvailableDrivers($dbWrapperClass = \Ubiquity\db\providers\pdo\PDOWrapper::class) {
		return call_user_func ( $dbWrapperClass . '::getAvailableDrivers' );
	}

	/**
	 *
	 * @return mixed
	 * @codeCoverageIgnore
	 */
	public function getDbType() {
		return $this->dbType;
	}

	/**
	 *
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 *
	 * @return array
	 * @codeCoverageIgnore
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 *
	 * @param string $port
	 */
	public function setPort($port) {
		$this->port = $port;
	}

	/**
	 *
	 * @param string $dbName
	 */
	public function setDbName($dbName) {
		$this->dbName = $dbName;
	}

	/**
	 *
	 * @param string $user
	 */
	public function setUser($user) {
		$this->user = $user;
	}

	/**
	 *
	 * @param string $password
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

	/**
	 *
	 * @param array $options
	 */
	public function setOptions($options) {
		$this->options = $options;
	}

	/**
	 * Closes the active connection
	 */
	public function close() {
		$this->wrapperObject->close ();
	}

	/**
	 * Starts and returns a database instance corresponding to an offset in config
	 *
	 * @param string $offset
	 * @param string $dbWrapperClass
	 * @return \Ubiquity\db\Database|NULL
	 */
	public static function start($offset = null, $dbWrapperClass = \Ubiquity\db\providers\pdo\PDOWrapper::class) {
		$config = Startup::$config;
		$db = $offset ? ($config ['database'] [$offset] ?? ($config ['database'] ?? [ ])) : ($config ['database'] ?? [ ]);
		if ($db ['dbName'] !== '') {
			$database = new Database ( $dbWrapperClass, $db ['type'], $db ['dbName'], $db ['serverName'] ?? '127.0.0.1', $db ['port'] ?? 3306, $db ['user'] ?? 'root', $db ['password'] ?? '', $db ['options'] ?? [ ], $db ['cache'] ?? false);
			$database->connect ();
			return $database;
		}
		return null;
	}

	/**
	 * For databases with Connection pool (retrieve a new dbInstance from pool wrapper)
	 */
	public function pool() {
		return $this->wrapperObject->pool ();
	}

	/**
	 * For databases with Connection pool (put a dbInstance in pool wrapper)
	 */
	public function freePool($db) {
		$this->wrapperObject->freePool ( $db );
	}

	public function setPool($pool) {
		$this->wrapperObject->setPool ( $pool );
	}

	public static function getAvailableWrappers() {
		$wrappers = [ ];
		foreach ( self::$wrappers as $k => $wrapper ) {
			if (\class_exists ( $wrapper, true )) {
				$wrappers [$k] = $wrapper;
			}
		}
		return $wrappers;
	}
}
