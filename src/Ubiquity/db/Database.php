<?php

/**
 * Database implementation
 */
namespace Ubiquity\db;

use Ubiquity\exceptions\CacheException;
use Ubiquity\db\traits\DatabaseOperationsTrait;
use Ubiquity\exceptions\DBException;
use Ubiquity\db\providers\PDOWrapper;

/**
 * Ubiquity Database class.
 * Ubiquity\db$Database
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.1.0
 *
 */
class Database {
	use DatabaseOperationsTrait;
	public static $dbWrapperClass;
	private $dbType;
	private $serverName;
	private $port;
	private $dbName;
	private $user;
	private $password;
	private $cache;
	private $options;

	/**
	 * Constructor
	 *
	 * @param string $dbName
	 * @param string $serverName
	 * @param string $port
	 * @param string $user
	 * @param string $password
	 * @param array $options
	 * @param boolean|string $cache
	 */
	public function __construct($dbType, $dbName, $serverName = "127.0.0.1", $port = "3306", $user = "root", $password = "", $options = [], $cache = false) {
		$this->dbType = $dbType;
		$this->dbName = $dbName;
		$this->serverName = $serverName;
		$this->port = $port;
		$this->user = $user;
		$this->password = $password;
		if (isset ( $options ["quote"] ))
			SqlUtils::$quote = $options ["quote"];
		$this->options = $options;
		if ($cache !== false) {
			if (\is_callable ( $cache )) {
				$this->cache = $cache ();
			} else {
				if (\class_exists ( $cache )) {
					$this->cache = new $cache ();
				} else {
					throw new CacheException ( $cache . " is not a valid value for database cache" );
				}
			}
		}
	}

	/**
	 * Creates the DB instance and realize a safe connection.
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

	public static function getAvailableDrivers() {
		$dbWrapperClass = self::$dbWrapperClass ?? PDOWrapper::class;
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
}
