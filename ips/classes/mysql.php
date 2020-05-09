<?php

class MySQLResult {
 	/**
  	* $mysql = Instance of MySQL providing database connection
  	* $query = Query resource
  	*/
 	private $sql;
 	private $query;

	/**
	* Returns the number of rows selected
	*/
	function size(){
		return mysql_num_rows($this->query);
	}

 	/**
  	* MySQLResult constructor
  	*/
 	function MySQLResult(&$sql, $query){
   		$this->sql = &$sql;
   		$this->query = $query;
 	}

 	/**
  	* Fetches a row from the result
  	*/
 	function fetch(){
   		if ($row = mysql_fetch_array($this->query, MYSQL_ASSOC)) {
     			return $row;
   		} else if ( $this->size() > 0 ) {
     			mysql_data_seek($this->query, 0);
     			return false;
   		} else {
     			return false;
   		}
 	}

	function fetchArray(){
		for ($i = 0; $i < $this->size(); $i++) {
			$row[$i] = mysql_fetch_array($this->query, MYSQL_ASSOC);
		}
		if (isset($row)) {
			return $row;
		} else {
			return false;
		}
	}
	
	function getId(){
		return mysql_insert_id();
	}

 	/**
  	* Checks for MySQL errors
  	*/
 	function isError(){
   		return $this->sql->isError();
 	}

}

class SQL {
	/**
	* $host = MySQL server hostname
	* $dbUser = MySQL username
	* $dbPass = MySQL user's password
	* $dbName = MySQL database to use
	* $dbConn = MySQL Resource link identifier stored here
	* $connectError = Stores error messages for connection errors
	*/
	public $host;
	public $dbUser;
	public $dbPass;
	public $dbName;
	public $dbConn;
	public $connectError;

	/**
	* MySQL constructor
	*/
	function SQL($host, $dbUser, $dbPass, $dbName){
		$this->host = $host;
		$this->dbUser = $dbUser;
		$this->dbPass = $dbPass;
		$this->dbName = $dbName;
		$this->connectToDb();
	}

	/**
	* Establishes connection to MySQL and selects a database
	*/
	function connectToDb(){
		// Make connection to MySQL server
		if (!$this->dbConn = @mysql_connect($this->host,
		$this->dbUser, $this->dbPass)) {
			trigger_error('Could not connect to server');
			$this->connectError = true;
		// Select database
		} else if (!@mysql_select_db($this->dbName,$this->dbConn)) {
			trigger_error('Could not select database');
			$this->connectError = true;
		} else {
			@mysql_set_charset('utf8', $this->dbConn);
		}
	}

	/**
	* Checks for MySQL errors
	*/
	function isError(){
		if ($this->connectError) {
			return true;
		}
		$error = mysql_error($this->dbConn);
		if (empty($error)) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	* Returns an instance of MySQLResult to fetch rows with
	*/
	public function query($statement){
		if (!$queryResource = mysql_query($statement, $this->dbConn)) {
     			trigger_error('Query failed: ' . mysql_error($this->dbConn)
                   		. ' SQL: ' . $statement);
		} else {
     			return new MySQLResult($this, $queryResource);
		}
			
	}

}

?>
