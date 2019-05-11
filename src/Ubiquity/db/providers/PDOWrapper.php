<?php

namespace Ubiquity\db\providers;

/**
 * Ubiquity\db\providers$PDOWrapper
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.0.0
 *
 */
class PDOWrapper extends AbstractDbWrapper {

	public function fetchAllColumn($statement, array $values = null, string $column = null) {
		$result = false;
		if ($statement->execute ( $values )) {
			$result = $statement->fetchAll ( \PDO::FETCH_COLUMN, $column );
		}
		$statement->closeCursor ();
		return $result;
	}

	public function lastInserId() {
		return $this->dbInstance->lastInsertId ();
	}

	public function fetchAll($statement, array $values = null) {
		$result = false;
		if ($statement->execute ( $values )) {
			$result = $statement->fetchAll ();
		}
		$statement->closeCursor ();
		return $result;
	}

	public static function getAvailableDrivers() {
		return \PDO::getAvailableDrivers ();
	}

	public function prepareStatement(array $sql) {
		return $this->dbInstance->prepare ( $sql );
	}

	public function fetchColumn($statement, array $values = null, int $columnNumber = null) {
		if ($statement->execute ( $values )) {
			return $statement->fetchColumn ( $columnNumber );
		}
		return false;
	}

	public function getStatement($sql) {
		$st = $this->dbInstance->prepare ( $sql );
		$st->setFetchMode ( \PDO::FETCH_ASSOC );
		return $st;
	}

	public function execute($sql) {
		return $this->dbInstance->exec ( $sql );
	}

	public function connect($dsn, $user, $password, array $options) {
		$options [\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
		$this->dbInstance = new \PDO ( $dsn, $user, $password, $options );
		return $this;
	}

	public function bindValueFromStatement($statement, $parameter, $value) {
		return $statement->bindValue ( ":" . $parameter, $value );
	}

	public function query(string $sql) {
		return $this->dbInstance->query ( $sql );
	}

	public function queryAll(string $sql, int $fetchStyle = null) {
		return $this->dbInstance->query ( $sql )->fetchAll ( $fetchStyle );
	}

	public function queryColumn(string $sql, int $columnNumber = null) {
		return $this->dbInstance->query ( $sql )->fetchColumn ( $columnNumber );
	}

	public function executeStatement($statement, array $values = null) {
		return $statement->execute ( $values );
	}

	public function getTablesName() {
		$query = $this->dbInstance->query ( 'SHOW TABLES' );
		return $query->fetchAll ( \PDO::FETCH_COLUMN );
	}

	public function statementRowCount($statement) {
		return $statement->rowCount ();
	}
}
