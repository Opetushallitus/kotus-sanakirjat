<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * app/models/Model.php
 * 
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
*/

abstract class Model {

	// Class level varible to store the SQL handle.
	protected $_sql;
	
	// Class level variable to store the XML (eXist) handle.
	protected $_xml;


	// Class constructor.
	// Will be executed when a class is initialized.
	public function __construct() {

		try {
			
			// Create a new SQL (MySQL) handle.
			$this->_sql = new QuerySQL();
			
			// Create a new XML (eXist) handle.
			$this->_xml = new QueryXML();

		} catch (QuerySQLException $e) {

		} catch (QueryXMLException $e) {

		} catch (SysException $e) {
			
		} catch (DICTException $e) {
			
		}

	}
	
	// Class destructor.
	// Will be executed when a class is destroyed.
	public function __destruct() {
		
		if ($this->_sql) {
			// Disconnect SQL (MySQL) connection.
			$this->_sql->disconnect();
		}
		/* Ei tarvetta en��, kun k�ytet��n RESTi� / 7.2.2020 OL
		if ($this->_xml) {
			// Disconnect XML (eXist) connection.
			$this->_xml->disconnect();
		}
		*/

		// Destroy handles.
		unset($this->_sql, $this->_xml);
	
	}


	public function getXMLHandle() {
		
		// Return XML (eXist) handle.
		return $this->_xml;
		
	}


	public function getSQLHandle() {
		
		// Return SQL (MySQL) handle.
		return $this->_sql;
		
	}
	
	
	public function getFirstWordArticle() {
		
		try {
			
			// Get SQL (MySQL) handle.
			$sql = $this->getSQLHandle();

			// Prepare a query for execution.
			//$query = $sql->prepare("SELECT * FROM hakusanat WHERE tyyppi = 'H' ORDER BY id ASC LIMIT 1");
			// Kaikki hakusanat mukaan aakkosvaliin / 6.11.2015 OL
			$query = $sql->prepare("SELECT * FROM hakusanat ORDER BY id ASC LIMIT 1");
			
			// Set fetch mode to object.
			$query->setFetchMode(PDO::FETCH_OBJ);

			// Execute query.
			$query->execute();

			// Return article data.
			return $query->fetch();
			
		} catch (QuerySQLException $e) {
			
		} catch (DICTException $e) {
			
		}
		
	}
	
	
	public function getLastWordArticle() {
		
		try {
			
			// Get SQL (MySQL) handle.
			$sql = $this->getSQLHandle();
			
			// Prepare a query for execution.
			//$query = $sql->prepare("SELECT * FROM hakusanat WHERE tyyppi = 'H' ORDER BY id DESC LIMIT 1");
			// Kaikki hakusanat mukaan aakkosvaliin / 6.11.2015 OL
			$query = $sql->prepare("SELECT * FROM hakusanat ORDER BY id DESC LIMIT 1");			
			// Set fetch mode to object.
			$query->setFetchMode(PDO::FETCH_OBJ);
			
			// Execute query.
			$query->execute();

			// Return article data.
			return $query->fetch();
			
		} catch (QuerySQLException $e) {
			
		} catch (DICTException $e) {
			
		}
		
	}
	
	
	public function getWordsTotalCount() {
		
		try {
			
			// Get SQL handle.
			$sql = $this->getSQLHandle();

			// Prepare a query for execution.
			$query = $sql->prepare("SELECT COUNT(*) FROM hakusanat");

			// Set fetch mode to object.
			$query->setFetchMode(PDO::FETCH_OBJ);

			// Execute query.
			$query->execute();

			// Return the count of keywords.
			return $query->fetchColumn();
			
		} catch (QuerySQLException $e) {
			
		} catch (DICTException $e) {
			
		}
		
	}


	// Get the area name for a given region or parish code.
	public function getAttrName($code) {

		global $cfg;

		return ($cfg['INITIALS'][$code]) ? _($cfg['INITIALS'][$code]) : $code;

	}

	public function getAttrArray(array $array) {

	    $temp = array();

	    array_walk_recursive($array, function($x) use (&$temp) {
	    	global $cfg;
	    	$temp[$x] = ($cfg['INITIALS'][$x]) ? _($cfg['INITIALS'][$x]) : $x;
	    });

	    asort($temp);
	    
	    return $temp;

	}

}

?>
