<?php

/**
 * inc/QuerySQL.php
 *
 * Class for all communication between PDO and PostgreSQL.
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author:		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				PostgreSQL-migraatio / 15.10.2026 OL
*/

final class QuerySQL {

	// Store here opened PDO instance.
	protected $_pdo;

	// Class constructor, to be executed when invoking a new instance.
	public function __construct() {

		global $cfg;

		$dsn = sprintf("pgsql:host=%s;port=%d;dbname=%s",
    	$cfg['SQL']['HOSTNAME'], $cfg['SQL']['PORTNUM'], $cfg['SQL']['DATABASE']);
	
		$skeema = sprintf("SET search_path TO %s", $cfg['SQL']['DATABASE']);

		try {
			// Make a new database object of PDO (PHP Database Object) class and store
			// opened instance into a class variable.	
			$this->_pdo = new PDO($dsn, $cfg['SQL']['USERNAME'], $cfg['SQL']['PASSWORD'],
				[
                	PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                	PDO::ATTR_EMULATE_PREPARES   => false,
                	PDO::ATTR_STRINGIFY_FETCHES  => false,
            	]);

			$this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
			$this->_pdo->exec("SET client_encoding TO 'UTF8'");
			//$this->query("SET NAMES 'utf8'");
			$this->_pdo->exec($skeema);
		
			return $this->_pdo;
	
		} catch (PDOException $e) {

			throw new QuerySQLException($e);

		}
	}


	// This magic method is executed when trying to clone an object.
	// Declared as final so it cannot be extended and as private that
	// it cannot be accessed without object instantiation. 
	// final poistettu 28.10.2024 / OL
	private function __clone() { }


	// Initiates a transaction.
	public function beginTransaction() {

		return $this->_pdo->beginTransaction();

	}


	// Commits a transaction.
	public function commit() {

		return $this->_pdo->commit();

	}


	// Fetch the SQLSTATE associated with the last operation on the database handle.
    public function errorCode() {

    	return $this->_pdo->errorCode();

    }


    // Fetch extended error information associated with
    // the last operation on the database handle.
    public function errorInfo() {

    	return $this->_pdo->errorInfo();

    }


    // Execute an SQL statement and return the number of affected rows.
    public function exec($query) {

    	return $this->_pdo->exec($query);

    }


    // Retrieve a database connection attribute.
    public function getAttribute($attr) {

    	return $this->_pdo->getAttribute($attr);

    }


    // Return an array of available PDO drivers.
    public function getAvailableDrivers(){

    	return $this->_pdo->getAvailableDrivers();

    }


    // Returns the ID of the last inserted row or sequence value.
	public function lastInsertId($name) {

		return $this->_pdo->lastInsertId($name);

	}


   	// Prepares a statement for execution and returns a statement object.
    public function prepare($query, $driver_options = array()) {

    	return $this->_pdo->prepare($query, $driver_options);

    }


    // Executes an SQL statement.
    // Return results as a PDOStatement object.
    public function query($query) {

    	return $this->_pdo->query($query);

    }


    // Execute query and return all rows in associative array.
    public function queryFetchAll($query) {

    	return $this->_pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

    }


    // Execute query and return the first row only in associative array.
    public function queryFetchOneRow($query) {

    	return $this->_pdo->query($query)->fetch(PDO::FETCH_ASSOC);

    }


    // Execute query and return the first column only.
    public function queryFetchOneCol($query) {

		return $this->_pdo->query($query)->fetchColumn();

    }


    // Quotes a string for use in a query.
    public function quote($input, $param_type = 0) {

		return $this->_pdo->quote($input, $param_type);

    }


    // Rollback for a transaction in progress.
    public function rollBack() {

		return $this->_pdo->rollBack();

    }


    // Set an attribute for the instance.
    public function setAttribute($attr, $value) {

		return $this->_pdo->setAttribute($attr, $value);

	}
	
	public function disconnect() {
		
		$this->_pdo = null;
		
	}

}

?>
